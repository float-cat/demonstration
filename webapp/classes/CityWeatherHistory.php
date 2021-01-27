<?php

#####################################################################
#               [  Класс История погоды в городе  ]                 #
#  Задача: получить информацию по городу из базы данных             #
#  Поля информации должны иметь публичные методы отображения        #
#  Экземпляр инициализируется коллекцией документов и алиасом       #
#####################################################################

/* Cодержит погоду в определенное время 
    Конструктор принимает массив:'{date, temperature}' */
class CityWeatherHistoryItem
{
    private $date;
    private $t_min;
    private $t_max;
    function __construct($moment_info)
    {
        $this->date = $moment_info['date'];
        $this->t_min = $moment_info['t_min'];
        $this->t_max = $moment_info['t_max'];
    }

    /* Методы для нативного шаблонизатора */
    /*
        getDate()
        getTemperature()
        getTemperatureMin()
        getTemperatureMax()
    */
    public function getDate()
    {
        /* Вместо date_default_timezone_set( string );
            просто уберем приписку смещения времени
            по часовому поясу */
        $strdate = substr($this->date, 0, 18);
        $timestamp = strtotime($strdate);
        /* Стандартный набор */
        return date('d/m/y', $timestamp);
    }

    public function getTemperature()
    {
        if($this->t_min == $this->t_max) return ($this->t_min>=0?'+':'&ndash;').
            abs($this->t_min).'&deg;';
        return ($this->t_min>=0?'+':'&ndash;').
            abs($this->t_min).'&deg; &ndash; '.($this->t_max>=0?'+':'&ndash;').
            abs($this->t_max).'&deg;';
    }

    public function getTemperatureMin()
    {
        return ($this->t_min>=0?'+':'&ndash;').
            abs($this->t_min).'&deg;';
    }

    public function getTemperatureMax()
    {
        return ($this->t_max>=0?'+':'&ndash;').
            abs($this->t_max).'&deg;';
    }
}

class CityWeatherHistory
{
    private $moments;
    private $error;
    function __construct($cities, $alias)
    {
        /* Выбираем статистику для конкретного города */
        $result = $cities->findOne(
            [ 'alias' => $alias ]
        );
        /* Если нет weather_history - устанавливаем признак ошибки */
        if(!isset($result['weather_history']))
        {
            $this->error = 1;
            return;
        }
        /* Разворачиваем наоборот - более новые
            к началу массива $moments для удобства */
        for($i=count($result['weather_history'])-1; $i>=0; $i--)
            $this->moments[] = new CityWeatherHistoryItem($result['weather_history'][$i]);
    }

    /* Методы для нативного шаблонизатора */
    /*
        getMoments()
        getMoment($moment_id)
        getMomentsNumber()
        isError()
    */
    public function getMoments()
    {
        return $this->moments;
    }

    public function getMoment($moment_id)
    {
        return $this->moments[$moment_id];
    }

    public function getMomentsNumber()
    {
        return count($this->moments);
    }

    public function isError(){
        return isset($this->error);
    }
}

?>
