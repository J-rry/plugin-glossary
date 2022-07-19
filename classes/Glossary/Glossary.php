<?php

namespace Glossary;

class Glossary {

  static public function getConfigData() {
    return include __DIR__ . '/../../glossary_config.php';
  }

  //Получает данные из бд и приводит к необходимому виду
  static public function getData() {
    return include __DIR__ . '/../../g_data.php';
  }

  static public function toFormatedData($term) {
    return [$term['term'], $term['specification'], $term['synonyms']];
  }

  static public function createDataForJS($data) {
    $glossaryPath = self::getConfigData()['GLOSSARY_PATH'];
    $isGlossaryPageExist = strlen($glossaryPath) !== 0;

    if($isGlossaryPageExist) 
      $newData = array_map(fn($term) => [$term['term'], $term['specification'], $glossaryPath . self::toAlias($term['term'])], $data);
    else 
      $newData = array_map(fn($term) => [$term['term'], $term['specification']], $data);

    return json_encode($newData);
  }

  static public function toAlias($name) {
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
}