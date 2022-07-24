<?php

namespace Glossary\Traits;

trait GlossaryTraits {
  
  static public function getGlossaryConfigData() {
    $application = \Cetera\Application::getInstance();
    $dataFromDB = $application->getConn()->executeQuery('SELECT glossary_path, glossary_title, glossary_description, glossary_keywords, term_title_mask, term_description_mask, term_keywords_mask FROM glossary_options');
    $data = $dataFromDB->fetch();
    return $data;
  }

  static public function getGlossaryData() {
    $application = \Cetera\Application::getInstance();
    $dataFromDB = $application->getConn()->executeQuery('SELECT term, specification, synonyms FROM glossary_data');
    $data = [];
    while ($term = $dataFromDB->fetch()) {
      $data[] = [$term['term'], $term['specification'], $term['synonyms']];
    }
    return $data;
  }

  static public function toGlossaryAlias($name) {
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