<?php

#####################################################################
#               [  Класс Прогноз погоды в городе  ]                 #
#  Задача: получить информацию о прогнозах по городу на 3 дня       #
#  Поля информации должны иметь публичные методы отображения        #
#  Экземпляр инициализируется аргументом содержащим алиас города    #
#####################################################################

/* Константы */
define('WEATHER_FUTURE_REST_API_URL', 'http://pogoda.ngs.ru/api/v1/forecasts/forecast?city=', true);
define('FORECAST_DAYS_NUMBER', 3, true);
define('FORECAST_HOURS_NUMBER', 4, true);

/* ForecastHour содержит прогноз на определенный момент.
    Конструктор принимает элемент списка hours из API */
class CityWeatherForecastHour extends WeatherInfo
{
    private $hour;
    private $icon_path;

    function __construct($hour_info)
    {
    	/* Инициализируем наследуемое */
    	WeatherInfo::__construct($hour_info);
        $this->hour = $hour_info['hour'];
        $this->icon_path = $hour_info['icon_path'];
    }

    /* Методы для нативного шаблонизатора */
    /*
        getHour()
        getIcon()
    */
    public function getHour()
    {
        switch($this->hour)
        {
            case 0: return 'ночь';
            case 6: return 'утро';
            case 12: return 'день';
            case 18: return 'вечер';
        }
    }

    public function getIcon()
    {
        return $this->icon_path;
    }
}

/* ForecastDay содержит прогноз на определенные
    сутки, и включает в себя ночь, утро, день, вечер.
    Конструктор принимает элемент списка forecasts из API */
class CityWeatherForecastDay
{
    private $date;
    protected $hours;
    function __construct($day_info)
    {
        $this->date = $day_info['date'];
        foreach($day_info['hours'] as $value)
            $this->hours[] = new CityWeatherForecastHour($value);
    }

    /* Методы для нативного шаблонизатора */
    /*
        getDate()
        getMoment($moment_id)
        getMomentsNumber()
        getMoments()
    */
    public function getDate()
    {
        /* Вместо date_default_timezone_set( string );
            просто уберем приписку смещения времени
            по часовому поясу */
        $strdate = substr($this->date, 0, 18);
        $timestamp = strtotime($strdate);
        /* Стандартный набор */
        return date('l, dS \o\f F', $timestamp);
    }

    public function getMoment($moment_id)
    {
        return $this->hours[$moment_id];
    }

    public function getMomentsNumber()
    {
        return FORECAST_HOURS_NUMBER;
    }

    public function getMoments()
    {
        return $this->hours;
    }
}

class CityWeatherForecast
{
    private $days;
    private $error;
    function __construct($alias)
    {
        $api_query_result = file_get_contents(WEATHER_FUTURE_REST_API_URL . $alias);
        if($api_query_result===false)
        {
            $this->error = 1;
            return;
        }
        /* Ассоциированный массив помедленнее, но поудобнее,
            кроме того он страхует от изменения карты данных 
            (формата) api на одном уровне. */
        $assoc_result = json_decode($api_query_result, true);
        /* Заполняем поля */
        /* Выбираем информацию про ближайшие три дня */
        for($i=1; $i<FORECAST_DAYS_NUMBER+1; $i++)
            $this->days[] = new CityWeatherForecastDay($assoc_result['forecasts'][$i]);
    }

    /* Методы для нативного шаблонизатора */
    /*
        getDay($day_id)
        getDaysNumber()
        getDays()
        isError()
    */
    public function getDay($day_id)
    {
        return $this->days[$day_id];
    }

    public function getDaysNumber()
    {
        return FORECAST_DAYS_NUMBER;
    }

    public function getDays()
    {
        return $this->days;
    }

    public function isError(){
        return isset($this->error);
    }
}

?>
