<?php

namespace Glossary;

class Data {
  
  static public function getTermDataByAlias($alias) {
    $application = \Cetera\Application::getInstance();
    $data = $application->getConn()->fetchAssoc('SELECT * FROM glossary_data WHERE alias=?', array($alias)); 
	  return $data;
  }

  static public function getData() {
    $application = \Cetera\Application::getInstance();
    $dataFromDB = $application->getConn()->executeQuery('SELECT term, specification, synonyms, alias FROM glossary_data');
    $data = [];
    while ($term = $dataFromDB->fetch()) {
      $data[] = $term;
    }
    return $data;
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