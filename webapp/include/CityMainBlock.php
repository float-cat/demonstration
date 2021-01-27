  <!-- Блок в котором отображается информация о городе -->
  <div id='city-block'>
   <span class='title'><?php print $current->getTitle();?></span><br />
   <?php if($current->isError()) {?>
    <div class='text-standart'>Извините, но информация недоступна!</div>
   <?php } else { ?>
    <span class='text-bold'><?php print $current->getTemperature();?> <?php print $current->getTemperatureTrend();?></span><br />
    <span class='text-small'>По ощущениям <?php print $current->getFeelLikeTemperature();?></span><br />
    <div class='text-standart'>
     <?php print $current->getCloud();?>, <?php print $current->getPrecipitation();?><br />
     <div class='icon-direction icon-direction-<?php print $current->getWindDirectionType();?>'></div>
     Ветер: <?php print $current->getWindSpeed();?> м/с, <?php print $current->getWindDirection();?><br />
     Атмосферное давление: <?php print $current->getPressure();?> мм<br />
     Влажность: <?php print $current->getHumidity();?>
    </div>
   <?php } ?>
  </div>
