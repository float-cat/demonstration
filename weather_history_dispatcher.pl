#!/usr/bin/perl

######################################################
#                                                    #
#           [ Диспетчер истории погоды ]             #
#                                                    #
#  Автор: float.cat                                  #
#  Версия: 0.24b                                     #
#                                                    #
#  Структура данных коллекции:                       #
#  @cities:                                          #
#  [1                                                #
#      dispatcher_version                            #
#  ],                                                #
#  [*                                                #
#      alias, title, count,                          #
#      date, t_min, t_max],                          #
#      @weather_history: # Сохраненные температуры   #
#      [*                                            #
#          date,                                     #
#          t_min, t_max                              #
#      ]                                             #
#  ]                                                 #
#                                                    #
#  Несколько раз в день, в зависимости от времени    #
#   установленном для sleep, мы проверяем значение   #
#   температуры, и вносим изменения в мин, макс      #
#   показателей за день, после того, как день        #
#   сменился - мы фиксируем результат в массиве      #
#   истории, который ограничен DAY_STORED_NUMBER.    #
#                                                    #
######################################################

# Подключаем модули
use MongoDB::MongoClient;
use MongoDB::Database;
use MongoDB::Collection;
use LWP::Simple;
use JSON;

# Объявляем константы
use constant THIS_DISPATCHER_VERSION => '0.24b';
use constant DB_COLLECTION_NAME => 'weather.cities';
use constant CITIES_REST_API_URL => 'http://pogoda.ngs.ru/api/v1/cities';
use constant WEATHER_NOW_REST_API_URL => 'http://pogoda.ngs.ru/api/v1/forecasts/current?city=';
# Ограничивает размер запоминаемых значений погоды
# Количество последних дней, которые сохраняются в истории
use constant DAYS_STORED_NUMBER => 7;
# Время паузы между опросами REST API в секундах!
use constant UPDATE_SLEEP_SECONDS => 1200;

# Возвращает 0, если версия не совпадает, иначе 1
sub check_version
{
    my ($collection) = @_;
    my $result = $collection->find_one(
        { },
        {'version' => 1, '_id' => 0}
    );
    return $result->{'version'} eq THIS_DISPATCHER_VERSION ? 1 : 0;
}

# Устанавливает свою версию в коллекцию
sub set_version
{
    my ($collection) = @_;
    $collection->insert_one({'version' => THIS_DISPATCHER_VERSION});
}

# Функция создает документ для города и возвращает строку таблицы алиасов
sub create_city_doc
{
    my ($collection, $city_info) = @_;
    my $ret_val;
    # Создаем документ и заполняем его начальными данными
    $collection->insert_one(
        {
            'alias' => $city_info->{'alias'},
            'title' => $city_info->{'title'},
            'count' => 0,
            'date' => 'NONE',
            't_min' => -0xFF,
            't_max' => 0xFF
        }
    );
    # Заполняем хеш табличку алиасов, которая потом используется в майне
    $ret_val->{'alias'} = $city_info->{'alias'};
    # Счетчик храним, чтобы каждый раз не спрашивать базу лишний раз
    $ret_val->{'count'} = 0;
    # Данные инициализированы заведомо некорректными значениями
    #   чтобы различать начало сбора статистики для нового дня
    $ret_val->{'date'} = 'NONE';
    $ret_val->{'t_min'} = -0xFF;
    $ret_val->{'t_max'} = 0xFF;
    # Возвращаем строку таблицы алиасов
    return $ret_val;
}

# Функция готовит таблицу алиасов, возвращает саму таблицу и ее размер
sub prepare_aliases
{
    my ($collection, $aliases_count, $create_flag) = @_;
    my $ret_val;
    my $contents = get CITIES_REST_API_URL;
    my $array_tmp = decode_json($contents);
    my $aliases_count = $array_tmp->{'metadata'}{'resultset'}{'count'};
    # Если версия диспетчера не совпадает с весией в базе
    # Флаг создания будет true, создаем и заполняем коллекцию
    if($create_flag)
    {
        for($i=0; $i<$aliases_count; $i++)
        {
            # Создаем документ для каждого города и заполняем данными
            $ret_val->{$i} = create_city_doc($collection, $array_tmp->{'cities'}[$i]);
        }
    }
    else # Загружаем информацию из коллекции
    {
        for($i=0; $i<$aliases_count; $i++)
        {
            # Заполняем хеш табличку алиасов значениями из БД
            my $result = $collection->find_one(
                {'alias' => $array_tmp->{'cities'}[$i]{'alias'}},
                {
                    'count' => 1, 'date' => 1,
                    't_min' => 1, 't_max' => 1,
                    '_id' => 0
                }
            );
            # Если результата от базы нет, значит город новый
            if($result eq undef)
            {
                # Создаем ему документ и заполняем данными
                $ret_val->{$i} = create_city_doc($collection, $array_tmp->{'cities'}[$i]);
            }
            else
            {   # Иначе - загружаем данные в таблицу алиасов
                $ret_val->{$i}{'alias'} = $array_tmp->{'cities'}[$i]{'alias'};
                $ret_val->{$i}{'count'} = $result->{'count'};
                $ret_val->{$i}{'date'} = $result->{'date'};
                $ret_val->{$i}{'t_min'} = $result->{'t_min'};
                $ret_val->{$i}{'t_max'} = $result->{'t_max'};
            }
        }
    }
    # Возвращаем размер по ссылке на аргумент
    $_[1] = $aliases_count;
    # Возвращаем таблицу алиасов
    return $ret_val;
}

# Сохраняет накопленные минимум и максимум прошедшего дня
# Удаляет самый давний день, если лимит достигнут
sub store_day
{
    my ($collection, $alias_info) = @_;
    if($alias_info->{'count'} eq DAYS_STORED_NUMBER)
    {
        # Удаляем первый элемент, чтобы история была не больше DAYS_STORED_NUMBER
        $collection->update_one(
            {'alias' => $alias_info->{'alias'}},
            {'$pop' => {'weather_history' => -1}}
        );
    }
    else
    {
        # Меняем по ссылке, чтобы использовать в майне
        $_[1]->{'count'}++;
        # Обновляем это значение в базе данных
        $collection->update_one(
            {'alias' => $alias_info->{'alias'}},
            {'$inc' => {'count' => 1}}
        );
    }
    # Записываем статистику сохраняемого дня в конец weather_history
    $collection->update_one(
        {'alias' => $alias_info->{'alias'}},
        {'$push' => 
            {'weather_history' =>
                {
                    'date' => $alias_info->{'date'},
                    't_min' => $alias_info->{'t_min'},
                    't_max' => $alias_info->{'t_max'}
                }
            }
        }
    );
}

sub start_new_day
{
    my ($collection, $alias_info, $city_info) = @_;
    # Записываем заведомо некорректную информацию
    # Это позволит процедуре update_data правильно
    #   выполниться в первый раз для нового дня
    $collection->update_one(
        {'alias' => $alias_info->{'alias'}},
        {'$set' => 
            {
                'date' => $city_info->{'date'},
                't_min' => $city_info->{'temperature'},
                't_max' => $city_info->{'temperature'}
            }
        }
    );
    # Работаем со ссылкой на аргумент, чтобы использовать в майне
    $_[1]->{'date'} = $city_info->{'date'};
    $_[1]->{'t_min'} = $city_info->{'temperature'};
    $_[1]->{'t_max'} = $city_info->{'temperature'};
}

sub update_data
{
    my ($collection, $alias_info, $city_info) = @_;
    # Если у нас некорректные данные, значит инициализируем новую дату,
    #   а минимум и максимум инициализируем текущей температурой
    # Все значения меняем по ссылкам, чтобы использовать в майне
    if($alias_info->{'date'} eq 'NONE'){
        $_[1]->{'date'} = $city_info->{'date'};
        $_[1]->{'t_min'} = $city_info->{'temperature'};
        $_[1]->{'t_max'} = $city_info->{'temperature'};
        # Установим дату для документа города, это дата активного сбора данных
        $collection->update_one(
            {'alias' => $alias_info->{'alias'}},
            {'$set' => {'date' => $alias_info->{'date'}}}
        );
    }
    # В ином случае сравниваем текущую температуру с минимумом и максимумом
    #   и корректируем их значения соответствующим образом, если надо
    else
    {
        # Если текущая температура меньше минимума
        if($city_info->{'temperature'}<$alias_info->{'t_min'})
        {
            # Текущая температура теперь новый минимум
            $_[1]->{'t_min'} = $city_info->{'temperature'};
        }
        # Иначе, если больше максимума
        else 
        {
            if($city_info->{'temperature'}>$alias_info->{'t_max'})
            {
                $_[1]->{'t_max'} = $city_info->{'temperature'};
            }
            # Иначе, все остается как было, текущая температура в диапазоне {min:max}
            else
            {
                return;
            }
        }
    }
    # Сохраняем новые значения
    $collection->update_one(
        {'alias' => $alias_info->{'alias'}},
        {'$set' => 
            {
                't_min' => $_[1]->{'t_min'},
                't_max' => $_[1]->{'t_max'}
            }
        }
    );
}

sub main
{
    # Таблица алиасов уменьшает частоту обращения к ДБ
    my $table_aliases;
    my $table_aliases_size = 0;
    my $api_query_result;
    my $array_tmp;
    my $days_number;
    my $version_check;
    my $alias_info;
    my $city_info;

    # Соединяемся с базой данных
    my $client = MongoDB->connect();
    my $cities = $client->ns(DB_COLLECTION_NAME);

    # Проверяем версию диспетчера, создавшего таблицу
    if(!($version_check = check_version($cities)))
    { # Если версия не та, или ее вообще нет, то пересоздаем
        $cities->drop();
        set_version($cities);
    }
    # Готовим таблицу алиасов, если надо - заполняем коллекцию городов.
    # Если $version_check = 0, значит коллекция не создана, prepare_aliases обработает это
    $table_aliases = prepare_aliases($cities, $table_aliases_size, !$version_check);

    while(true)
    {
        # Опрос конкретных городов по алиасу и сохранение статистики
        for($i=0; $i<$table_aliases_size; $i++)
        {
            # Для удобства
            $alias_info = $table_aliases->{$i};
            # Спрашиваем у REST API погоду
            $api_query_result = get WEATHER_NOW_REST_API_URL . $alias_info->{'alias'};
            # Если ответ не пришел, или содержит ошибку - нечего обновлять
            if(!($api_query_result eq undef or $api_query_result=~/error/))
            {
                $array_tmp = decode_json($api_query_result);
                $city_info = $array_tmp->{'forecasts'}[0];
                # Начался новый день?
                if($alias_info->{'date'} ne $city_info->{'date'} and $alias_info->{'date'} ne 'NONE')
                {
                    # Заносим старый день в историю
                    store_day($cities, $alias_info);
                    # Сбрасываем минимум и максимум, новый день
                    start_new_day($cities, $alias_info, $city_info);
                }
                # Иначе - переопределяем минимум и максимум
                else
                {
                    update_data($cities, $alias_info, $city_info);
                }
            }
        }
        sleep(UPDATE_SLEEP_SECONDS);   
    }

    $client->disconnect();
}

main();
