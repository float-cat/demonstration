<?php

    ################################################
    #                                              #
    #    [ Погодный профиль выбранного города ]    #
    #                                              #
    ################################################

    /* Для этой страницы требуется подключение к БД */
    include 'include/ConnectBD.php';
    /* Классы, реализующие представление данных */
    include 'classes/WeatherInfo.php';
    include 'classes/CityWeatherCurrent.php';
    include 'classes/CityWeatherForecast.php';
    include 'classes/CityWeatherHistory.php';
    /* Проверяем COOKIE */
    if(isset($_COOKIE['show']))
    {
        $alias_city = $_COOKIE['show'];
    }
    else
    {
        $alias_city = 'novosibirsk';
    }
    /* Собираем информацию из REST API pogoda.ngs.ru и архива погоды */
    $current = new CityWeatherCurrent($collection, $alias_city);
    $forecast = new CityWeatherForecast($alias_city);
    $history = new CityWeatherHistory($collection, $alias_city);
    /* БД нам больше не нужна */
    include 'include/DisconnectBD.php';
?>

<!-- Начало документа HTML -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
 <head>
  <meta charset="utf-8" />
  <meta http-equiv="refresh" content="120"> 
  <title>Информация о погоде в городе <?php print $current->getTitle();?> </title>
  <link rel='stylesheet' type='text/css' href='css/template.css'>
 </head>
 <body>
  <div id='back-to-main' class='goto-span' onclick='document.location = "index.php";'><<</div>
  <?php
    /* Блок инфомации о городе */
    include 'include/CityMainBlock.php';
  ?>
  <!-- Блок прогнозов на 3 дня -->
  <?php if(!$forecast->isError()) {?>
   <div id='forecast-block'>
    <span class="title-block">Прогноз на три дня:</span>
    <?php foreach($forecast->getDays() as $day){?>
     <div class='day-block'>
      <div class='day-date'><?php print $day->getDate();?></div> 
      <?php foreach($day->getMoments() as $moment){?>
       <div class='day-info'>
        <span class='stage'>
         <?php print $moment->getHour();?><br />
         <?php print $moment->getTemperature();?><br />
         <div class='icon-direction icon-direction-<?php print $moment->getWindDirectionType();?>'></div>
         <?php print $moment->getWindSpeed();?> м/с<br />
	     <img src='http:<?php print $moment->getIcon();?>' />
         <?php print $moment->getPressure();?> мм
        </span>
       </div>
      <?php } ?>
     </div>
    <?php } ?>
   </div>
  <?php } ?>
  <!-- Блок истории погоды -->
  <?php if(!$history->isError()) {?>
   <span class="title-block">История погоды:</span>
   <table id='history-block'>
    <tr>
     <td id='history-block-date'>Дата:</td>
     <td id='history-block-temperature'>Температура:</td>
    </tr>
    <?php foreach($history->getMoments() as $moment){?>
     <tr>
      <td><?php print $moment->getDate();?></td>
      <td><?php print $moment->getTemperature();?></td>
     </tr>
    <?php } ?>
   </table>
  <?php } ?>
 </body>
</html>
