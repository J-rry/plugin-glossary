<?php

namespace Glossary;

class Glossary {

  protected $glossaryPath;
  protected $data;

  public function __construct() {
    $this->data = $this->getData();
    $this->glossaryPath = self::getPath();
  }

  static public function getPath() {
    $configData = include __DIR__ . '/../../glossary_config.php';
    return $configData['GLOSSARY_PATH'];
  }

  public function getDataByAlias($alias) {
    $data = $this->data;
    $termData = array_values(array_filter($data, function($term) use ($alias) {
      return $alias === $this->toAlias($term[0]);
    }));
    if(!count($termData)) {
      return null;
    }
    $termData = $termData[0];
    $termData[3] = $this->findTermReference($termData);

    return $termData;
  }

  public function createDataForJS($data) {
    $isGlossaryPageExist = strlen($this->glossaryPath) !== 0;

    if($isGlossaryPageExist) {
      $newData = array_map(function($term) {
        return [$term['term'], $term['specification'], $this->glossaryPath . $this->toAlias($term['term'])];
      }, $data);
    } else {
      $newData = array_map(function($term) {
        return [$term['term'], $term['specification']];
      }, $data);
    }

    return json_encode($newData);
  }

  public function getGlossaryWidget() {
    $a = \Cetera\Application::getInstance();
    $widget = $a->getWidget('Glossary', array(
      'struct' => $this->createTemplateGlossaryData()
    ));
    return $widget;
  }

  public function getTermWidget($term) {
    $a = \Cetera\Application::getInstance();
    $widget = $a->getWidget('Term', array(
      'term'        => $term[0],
      'description' => $term[1],
      'synonyms'    => $this->createSynonymsData($term[2]),
      'links'       => $this->createLinksData($term[3])
      ));
    return $widget;
  }

  protected function toAlias($name) {
    $alias = implode('-', mb_split(' ', mb_strtolower($name)));
    if(preg_match('/[а-яё]/u', $alias)) {
      $ruAlphabet = [
        'а' => 'a', 
        'б' => 'b', 
        'в' => 'v', 
        'г' => 'g', 
        'д' => 'd', 
        'е' => 'e', 
        'ё' => 'yo', 
        'ж' => 'j', 
        'з' => 'z', 
        'и' => 'i', 
        'й' => 'y', 
        'к' => 'k', 
        'л' => 'l', 
        'м' => 'm', 
        'н' => 'n', 
        'о' => 'o', 
        'п' => 'p', 
        'р' => 'r', 
        'с' => 's', 
        'т' => 't', 
        'у' => 'u', 
        'ф' => 'f', 
        'х' => 'h', 
        'ц' => 'c', 
        'ч' => 'ch', 
        'ш' => 'sh', 
        'щ' => 'shi', 
        'ъ' => 'w', 
        'ы' => 'ii', 
        'ь' => 'q', 
        'э' => 'ee', 
        'ю' => 'yu', 
        'я' => 'ya'
      ];
      $translitAlias = 'ru-' . strtr($alias, $ruAlphabet);
      return $translitAlias;
    } 
    return $alias;
  }
  
   //Получает данные из бд (и приводит к необходимому виду)
  protected function getData() {
    $dataFromDB = include __DIR__ . '/../../g_data.php';

    $data = array_reduce($dataFromDB, function($dataArray, $term) {
      $dataArray[] = [$term['term'], $term['specification'], $term['synonyms']];
      return $dataArray;
    }, []);

    return $data;
  }

  //Получаем алфавит, на основании существующих терминов
  protected function getAlphabet() {
    $data = $this->data;
    $alphabet = array_unique(array_map(
      function($term) { 
        return mb_strtoupper(mb_substr($term[0], 0, 1));
      }, $data));

    sort($alphabet);

    return $alphabet;
  }

  //Получает структуру главной страницы глоссария в виде массива
  public function createTemplateGlossaryData() {
    $data = $this->data;
    $alphabet = $this->getAlphabet();

    $dataStruct = array_reduce($alphabet, 
      function($struct, $char) use ($data) {
        $item = [];
        $item['char'] = $char;
        $item['data'] = array_reduce($data, 
          function($newData, $term) use($char) {
            if(mb_strtoupper(mb_substr($term[0], 0, 1)) === mb_strtoupper($char)) {
              $newData[] = [
                'term'  => $term[0], 
                'path' => $this->glossaryPath . $this->toAlias($term[0])
              ];
            }  
            return $newData;
          }, []);
        $struct[] = $item;
        return $struct;
    }, []);
    return $dataStruct;
  }

  protected function createSynonymsData($synonyms) {
    if(strlen($synonyms) === 0) {
      return [];
    }
    $synonymsArray = mb_split(", ?", $synonyms);
    $synonymsData = array_reduce($synonymsArray, function($data, $synonym) {
      $dataSynonym = $this->getSynonymData($synonym);
      $dataSynonym['separator'] = count($data) === 0 ? '' : ', ';
      $data[] = $dataSynonym;
      return $data;      
    }, []);

    return $synonymsData;
  }

  protected function getSynonymData($synonym) {
    $alias = $this->toAlias($synonym);
    $isHavePage = count(array_filter($this->data, 
      function($term) use ($alias) {
        return $this->toAlias($term[0]) === $alias;
      }));

    $path = $this->glossaryPath . $alias;
    return [
      'term' => $synonym,
      'link' => $isHavePage ? $path : ''
    ];
  }

  protected function createLinksData($links) {
    if(count($links) === 0) {
      return [];
    }
    $linksData = array_reduce($links, function($data, $link) {
      $dataLink = $link;
      $dataLink['separator'] = count($data) === 0 ? '' : ', ';
      $data[] = $dataLink;
      return $data;      
    }, []);

    return $linksData;
  }

  protected function findTermReference($term) {
    $mainCatalog = \Cetera\Application::getInstance()->getServer();
    $catalogs = $mainCatalog->getSubs();
    $data = array_reduce($catalogs, function($links, $id) use($term, $mainCatalog) { 
      $catalog = $mainCatalog->getById($id);
      if(!$catalog->isHidden() && !$catalog->getParent()->isHidden()) {
        $materials = $catalog->getMaterials();
        for($i = 0; $i < count($materials); $i++) {
          $html = $materials[$i]['text'];
          $onlyText = implode("", mb_split("</?.*?>", $html));
          $regExp = '/([^a-zа-яА-ЯЁё\.-]' . $term[0] . '$|^' . $term[0] . '[^a-zа-яА-ЯЁё\.-]|[^a-zа-яА-ЯЁё\.-]'. $term[0] . '[^a-zа-яА-ЯЁё-])/ui';
          $isHaveTerm = preg_match($regExp, $onlyText);
    
          if($isHaveTerm === 1) {
            $links[] = ['title' => $materials[$i]['name'], 'link' => $materials[$i]->getUrl()];
          }
        }
      }
      return $links;
    }, []);
    
    return $data;
  }
}