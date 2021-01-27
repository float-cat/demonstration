<?php

    ############################################################
    #                                                          #
    #   [ Отображает alias по запросу GET, получает title ]    #
    #                                                          #
    ############################################################

    /* Для этой страницы требуется подключение к БД */
    include 'include/ConnectBD.php';
    /* Забираем из MongoDB алиас города */
    $result = $collection->findOne(['title' => $_GET['title']], ['alias' => 1, '_id' => 0]);
    /* Если название города есть в БД */
    if(isset($result['alias']))
        print $result['alias'];
    /* Возможно вместо названия у нас alias */
    else 
    {
        /* Забираем из MongoDB название города */
        $result = $collection->findOne(['alias' => $_GET['title']], ['alias' => 1, '_id' => 0]);
        if(isset($result['alias']))
            print $result['alias'];
        /* Если ничего не нашли - сигнализируем об ошибке */
        else
            print 'error';
    }
    /* БД нам больше не нужна */
    include 'include/DisconnectBD.php';
?>
