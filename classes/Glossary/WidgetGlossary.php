<?php

namespace Glossary;

use \Laminas\Router\Http\Regex;

class WidgetGlossary extends \Cetera\Widget\Templateable
{
	use \Cetera\Widget\Traits\Material;

  protected $_params = array(
  'struct'         => '',
  'page_h1'        => '',
  'css_class'      => 'widget-glossary',
  'template'       => 'default.twig',
  );

  static public function initPage($glossaryPath) {
    $router = \Cetera\Application::getInstance()->getRouter();
    $router->addRoute('glossary', Regex::factory([
      'regex' => $glossaryPath . '?',
      'defaults' => ['controller' => '\Glossary\WidgetGlossary', 'action' => 'index'],
      'spec' => $glossaryPath,
    ]), 1);
  }

  static public function index() {
    $a = \Cetera\Application::getInstance();
    $catalog = $a->getCatalog();
    $materials = $catalog->getMaterials();

    $title = $catalog['meta_title'];
    $description = $catalog['meta_description'];
    $keywords = $catalog['meta_keywords'];

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

    $a->getWidget('Glossary', array(
      'page_h1' => $catalog['name'],
      'struct' => self::createTemplateGlossaryData($materials)
    ))->display();
  }

  public function createTemplateGlossaryData($materials) {
    $result = [];
    for($i = 0; $i < count($materials); $i++) {
      $char = mb_strtoupper(mb_substr($materials[$i]['name'], 0, 1));
      $result[$char] = $result[$char] ?? ['char' => $char, 'data' => []];
      $result[$char]['data'][] = Data::getTermDataFromMaterial($materials[$i]);
    }
    usort($result, fn($a, $b) => $a['char'] <=> $b['char']);

    return $result;
  }
}