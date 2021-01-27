<?php

#####################################################################
#               [  Класс Текущая погода в городе  ]                 #
#  Задача: получить информацию о погоде по городу                   #
#  Поля информации должны иметь публичные методы отображения        #
#  Экземпляр инициализируется коллекцией документов и алиасом       #
#####################################################################

/* Константы */
define('WEATHER_NOW_REST_API_URL', 'http://pogoda.ngs.ru/api/v1/forecasts/current?city=', true);

class CityWeatherCurrent extends WeatherInfo
{
    /* Содержит название города, ассоциированное с алиасом
        берется из базы данных, т.к. нет в REST API */
    private $title;
    /* $temperature_info содержит feel_like_temperature и
        temperature_trend, так как этого нет в WeatherInfo */
    private $temperature_info;
    private $source;
    private $error;
    function __construct($cities, $alias)
    {
        /* Забираем из MongoDB название города */
        $result = $cities->findOne(['alias' => $alias], ['title' => 1, '_id' => 0]);
        $this->title = $result['title'];
        /* Обрашиваем REST API */
        $api_query_result = file_get_contents(WEATHER_NOW_REST_API_URL . $alias);
        if($api_query_result===false)
        {
            $this->error = 1;
            return;
        }
        /* Ассоциированный массив помедленнее, но поудобнее,
            кроме того он страхует от изменения карты данных 
            (формата) api на одном уровне. */
        $assoc_result = json_decode($api_query_result, true);
        $array_tmp = $assoc_result['forecasts'][0];
        /* Инициализируем наследуемое */
        WeatherInfo::__construct($array_tmp);
        /* Заполняем поля */
        $this->temperature_info['feel_like_temperature'] = $array_tmp['feel_like_temperature'];
        $this->temperature_info['temperature_trend'] = $array_tmp['temperature_trend'];
        $this->source = $array_tmp['source'];
    }

    /* Методы для нативного шаблонизатора */
    /*
        getTitle()
        getFeelLikeTemperature()
        getTemperatureTrend()
        getSource()
        isError()
    */

    public function getTitle()
    {
        return $this->title;
    }

    public function getFeelLikeTemperature()
    {
        return ($this->temperature_info['feel_like_temperature']>=0?'+':'&ndash;').
            abs($this->temperature_info['feel_like_temperature']).'&deg;';
    }

    public function getTemperatureTrend()
    {
        return ($this->temperature_info['temperature_trend']>=0)?
            ($this->temperature_info['temperature_trend']==0?'':'&uArr;'):'&dArr;';
    }

    public function getSource()
    {
        return $this->source;
    }

    public function isError(){
        return isset($this->error);
    }
}

?>
