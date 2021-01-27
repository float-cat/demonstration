<?php

#####################################################################
#                   [  Класс Список алиасов  ]                      #
#  Задача: загрузить алиасы из базы                                 #
#  Уметь соотносить между собой название города и алиас             #
#  Экземпляр инициализируется аргументом коллекция документов       #
#####################################################################

class AliasesList
{
    private $aliases;
    function __construct($cities)
    {
        $result = $cities->find(
            [ ],
            ['alias' => 1, 'title' => 1, '_id' => 0]
        );
        foreach($result as $doc)
        {
            $this->aliases[$doc['alias']] = $doc['title'];
        }
    }

    public function getTitleByAlias($alias)
    {
        return $this->aliases[$alias];
    }

    public function getAliasByTitle($title)
    {
        $array_tmp = array_flip($this->aliases);
        return $array_tmp[$title];
    }

    public function checkAliasInList($alias)
    {
        return isset($this->aliases[$alias]);
    }
}

?>
