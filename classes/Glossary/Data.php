<?php

namespace Glossary;

class Data {
  
  static public function catalogUrl() {
    $catalogUrl = \Cetera\Application::getInstance()->getCatalog()->getUrl();
    $typeId = \Cetera\ObjectDefinition::findByAlias('glossary')->getId();
    $glossaryCatalogs = \Cetera\ObjectDefinition::findById($typeId)->getCatalogs();

    for($i = 0; $i < count($glossaryCatalogs); $i++) {
      if($glossaryCatalogs[$i]->getUrl() === $catalogUrl)
        return $catalogUrl;
    }

    return false;
  }

  static public function getTermDataFromMaterial($material) {
    return [
      'term' => $material['name'],
      'specification' => $material['specification'],
      'synonyms' => $material['synonyms'],
      'url' => $material->getUrl(),
      'meta_title' => $material['meta_title'],
      'meta_description' => $material['meta_description'],
      'meta_keywords' => $material['meta_keywords'],
    ];
  }
}