<?php

#####################################################################
#               [  Класс Информация о погоде  ]                     #
#  Задача: структура хранит информацию о погоде                     #
#  Поля информации должны иметь публичные методы отображения        #
#  Экземпляр инициализируется JSON с информацией о погоде           #
#####################################################################

class WeatherInfo
{
    private $temperature;
    private $cloud;
    private $precipitation;
    /* $wind_info содержит speed, direction и value */
    private $wind_info;
    private $pressure;
    private $humidity;
    function __construct($weather_info)
    {
        $api_query_result = file_get_contents(WEATHER_NOW_REST_API_URL . $alias);
        /* Мы исходим из того, что карта данных api меняется
            с предварительным информированием, а значит
            можно быть уверенным в том, что фиксированная
            выборка элементов будет давать предсказуемый
            результат. На всякий случай можно проверять
            наличие элементов. */
        /* Заполняем поля */
        /* Для совместимости с forecasts */
        if(isset($weather_info['temperature']['avg']))
            $this->temperature = $weather_info['temperature']['avg'];
        else $this->temperature = $weather_info['temperature'];
        $this->cloud = $weather_info['cloud']['title'];
        $this->precipitation = $weather_info['precipitation']['title'];
        if(isset($weather_info['wind']['speed']['avg']))
            $this->wind_info['speed'] = $weather_info['wind']['speed']['avg'];
        else $this->wind_info['speed'] = $weather_info['wind']['speed'];
        $this->wind_info['direction'] = $weather_info['wind']['direction']['title'];
        /* Английское значение удобно использовать для указания класса стиля */
        $this->wind_info['value'] = $weather_info['wind']['direction']['value'];
        if(isset($weather_info['pressure']['avg']))
            $this->pressure = $weather_info['pressure']['avg'];
        else $this->pressure = $weather_info['pressure'];
        if(isset($weather_info['humidity']['avg']))
            $this->humidity = $weather_info['humidity']['avg'];
        else $this->humidity = $weather_info['humidity'];
        /* Можно было определить $weather_info как поле и
            использовать его вместо кучи переменных,
            но там много лишних данных */
    }

    /* Методы для нативного шаблонизатора */
    /*
        getTemperature()
        getCloud()
        getPrecipitation()
        getWindDirection()
        getWindSpeed()
        getWindDirectionType()
        getPressure()
        getHumidity()
    */

    public function getTemperature()
    {
        return ($this->temperature>=0?'+':'&ndash;').
            abs($this->temperature).'&deg;';
    }

    public function getCloud()
    {
        return $this->cloud;
    }

    public function getPrecipitation()
    {
        return $this->precipitation;
    }

    public function getWindDirection()
    {
        return $this->wind_info['direction'];
    }

    public function getWindSpeed()
    {
        return $this->wind_info['speed'];
    }

    public function getWindDirectionType()
    {
        return $this->wind_info['value'];
    }

    public function getPressure()
    {
        return $this->pressure;
    }

    public function getHumidity()
    {
        return $this->humidity.'&#37;';
    }
}

?>
