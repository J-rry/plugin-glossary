<?php

namespace Glossary;

use \Laminas\Router\Http\Segment;

class WidgetTerm extends \Cetera\Widget\Templateable
{
	use \Cetera\Widget\Traits\Material;

  static protected $links = [];

  protected $_params = array(
  'term'           => '',
  'specification'  => '',
  'synonyms'       => '',
  'links'          => '',
  'css_class'      => 'widget-glossary-term',
  'template'       => 'default.twig',
  );

  static public function initPage($glossaryPath) {
    $router = \Cetera\Application::getInstance()->getRouter();
    $router->addRoute('glossary_term', Segment::factory([
			'route' => $glossaryPath . ':id[/]',
			'defaults' => ['controller' => '\Glossary\WidgetTerm', 'action' => 'index'],
	  ]), 1);
  }

  static public function index() {
    $a = \Cetera\Application::getInstance();
    $termData = self::getTermData();

    $title = $termData['meta_title'];
    $description = $termData['meta_description'];
    $keywords = $termData['meta_keywords'];

    if(!empty($title)) {
      $a->setPageProperty('title', $title);
      $a->addHeadString('<meta property="og:title" content="'.$title.'"/>', 'og:title');
    }
    if(!empty($description)) {
      $a->setPageProperty('description', $description);
      $a->addHeadString('<meta property="og:description" content="'.htmlspecialchars($description).'"/>', 'og:description');
    }
    if(!empty($keywords)) {
      $a->setPageProperty('keywords', $keywords);
    }

    $a->getWidget('Term', array(
      'term'          => $termData['term'],
      'specification' => $termData['specification'],
      'synonyms'      => $termData['synonyms'],
      'links'         => self::findTermReference($termData)
      ))->display();
  }

  static public function getMaterialAlias() {
    $address = explode("/", $_SERVER['REQUEST_URI']);
    $alias = substr($_SERVER['REQUEST_URI'], -1) !== '/' ? $address[count($address) - 1] : $address[count($address) - 2];

    return $alias;
  }

  protected function getTermData() {
    $alias = self::getMaterialAlias();
    $catalog = \Cetera\Application::getInstance()->getCatalog();
    $materials = $catalog->getMaterials()->where("alias='$alias'");
    $termData = Data::getMaterialData($materials[0]);

    return $termData;
  }

  protected function findTermReference($term) {
    $termAndSynonyms = empty($term['synonyms']) ? [$term['term']] : [$term['term'], ...mb_split(", ?", $term['synonyms'])];
    $mainCatalog = \Cetera\Application::getInstance()->getServer();
    self::findReferenceInChildrenCatalogs($mainCatalog, $termAndSynonyms);

    return self::$links;
  }

  protected function findReferenceInMaterials($catalog, $termAndSynonyms) {
    $materials = $catalog->getMaterials();
    for($i = 0; $i < count($materials); $i++) {
      $html = $materials[$i]['text'];
      foreach($termAndSynonyms as $term) {
        $termPos = mb_stripos($html, $term);

        if($termPos === false)
          continue;

        $onlyText = implode("", mb_split("</?.*?>", $html));
        $regExp = PageHandler::termFindRegExp($term);
        $isHaveTerm = preg_match($regExp, $onlyText);
  
        if($isHaveTerm === 1) {
          self::$links[] = ['title' => $materials[$i]['name'], 'link' => $materials[$i]->getUrl()];
          break;
        }
      }
    }
  }

  public function findReferenceInChildrenCatalogs($catalog, $termAndSynonyms) {
    $childrenCatalogs = $catalog->getChildren();
    for($i = 0; $i < count($childrenCatalogs); $i++) {
      self::findReferenceInMaterials($childrenCatalogs[$i], $termAndSynonyms);
      self::findReferenceInChildrenCatalogs($childrenCatalogs[$i], $termAndSynonyms);
    }
  }
}