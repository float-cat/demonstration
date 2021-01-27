<?php

    ################################################
    #                                              #
    #     [ Главная страница, список городов ]     #
    #                                              #
    ################################################

    /* Для этой страницы требуется подключение к БД */
    include 'include/ConnectBD.php';
    /* Классы, реализующие представление данных */
    include 'classes/WeatherInfo.php';
    include 'classes/CityWeatherCurrent.php';
    /* Защита от случайных алиасов */
    include 'classes/AliasesList.php';
    /* Готовим список Алиасов */
    $aliases = new AliasesList($collection);
    /* Проверяем COOKIE */
    $alias_cookie = $aliases->getTitleByAlias($_COOKIE['show']);
    if(isset($_COOKIE['show']) && isset($alias_cookie))
    {
        $alias_city = $_COOKIE['show'];
    }
    else
    {
        $alias_city = 'novosibirsk';
    }
    /* Собираем информацию из REST API pogoda.ngs.ru и архива погоды */
    $current = new CityWeatherCurrent($collection, $alias_city);
?>

<!-- Начало документа HTML -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
 <head>
  <meta charset='utf-8' />
  <meta http-equiv="refresh" content="600"> 
  <title>Информация о погоде в городе <?php print $current->getTitle();?> </title>
  <link rel='stylesheet' type='text/css' href='css/template.css'>
  <script type='text/javascript' src='js/cookies.js'></script>
  <script type='text/javascript' src='js/script.js'></script>
 </head>
 <body>
  <?php
    /* Блок инфомации о городе */
    include 'include/CityMainBlock.php';
  ?>
  <!-- Список городов -->
  <?php /* Список городов содержится в куках */ ?>
  <div id='list-block'>
   <?php
    for($alias_str = strtok($_COOKIE['list'], "|"); $alias_str !== false; $alias_str = strtok("|"))
    {
        $check_alias = $aliases->getTitleByAlias($alias_str);
        if(!isset($check_alias)) continue;
        $data_current = new CityWeatherCurrent($collection, $alias_str);
   ?>
   <div class='city-list-item'>
    <span class='goto-span' onclick='goto_city("<?php print $alias_str;?>");'><?php print $data_current->getTitle()?></span>
     <?php if(!$data_current->isError()) {?>
      <span class='city-mini-info'><?php print $data_current->getTemperature().' '.
       $data_current->getTemperatureTrend();?>
     <?php } else { ?>
     <!-- Отступ между выводом температуры и кнопкой -->
     <span>
     <?php } ?>
     </span><span class='goto-span manager-button'
      onclick='open_select_form("<?php print $alias_str;?>", "<?php print $data_current->getTitle();?>")'>=</span>
   </div>      
   <?php
        unset($datacurrent);
    }
   ?>
   <div id='city-list-add' class='goto-span' onclick='open_add_form()'>+</div>
  </div>
  <!-- Конец списка городов -->
  <!-- Диалоговые окна -->
  <form id='dialog_select' class='dialog'>
   Что хотите сделать?<br />
   <input id='select_title' class='text-input shadow' type='text' value='' /><br />
   <input id='select_alias' class='text-input shadow' type='text' value='' /><br />
   <input id='select_cancel' class='button' type='button' value='Отмена' onclick='close_form(this.form);' />
   <input id='select_edit' class='button' type='button' value='Изменить' onclick='open_edit_form();' />
   <input id='select_delete' class='button' type='button' value='Удалить' onclick='open_delete_form();' />
  </form>
  <form id='dialog_delete' class='dialog'>
   Вы действительно хотите удалить?<br />
   <input id='delete_ok' class='button' type='button' value='Ок' onclick='delete_city();' />
   <input id='delete_cancel' class='button' type='button' value='Отмена' onclick='close_form(this.form);' />
  </form>
  <form id='dialog_edit' class='dialog'>
   <input id='edit_title' class='text-input' type='text' value='Город' onfocus='clear_once(this)' /><br />
   <input id='edit_alias' class='text-input shadow' type='text' value='' /><br />
   <input id='edit_ok' class='button' type='button' value='Ок' onclick='change_city();' />
   <input id='edit_cancel' class='button' type='button' value='Отмена' onclick='close_form(this.form);' />
  </form>
  <form id='dialog_add' class='dialog'>
   <input id='add_title' class='text-input' type='text' value='Город' onfocus='clear_once(this)' /><br />
   <input id='add_alias' class='text-input  shadow' type='text' value='' /><br />
   <input id='add_ok' class='button' type='button' value='Ок' onclick='add_city();' />
   <input id='add_cancel' class='button' type='button' value='Отмена' onclick='close_form(this.form);' />
  </form>
  <form id='dialog_alert' class='dialog'>
   <span id='message_text' class='message'></span><br />
   <input id='alert_ok' class='button' type='button' value='Ок' onclick='close_form(this.form);' />
  </form>
 </body>
</html>
<!-- Конец документа HTML -->

<?php
    /* БД нам больше не нужна */
    include 'include/DisconnectBD.php';
?>
