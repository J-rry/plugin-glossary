<?php

namespace Glossary;

use \Laminas\Router\Http\Regex;

class WidgetGlossary extends \Cetera\Widget\Templateable
{
	use \Cetera\Widget\Traits\Material;

  protected $_params = array(
  'struct'         => '',
  'glossary_path'  => '',
  'page_h1'        => 'Глоссарий',
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

    $data = Data::getData();
    $glossaryPath = Options::getPath();

    //Маски мета-тегов
    $title = Options::getTitle();
    $description = Options::getDescription();
    $keywords = Options::getKeywords();

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
      'struct' => self::createTemplateGlossaryData($glossaryPath, $data),
      'glossary_path' => $glossaryPath
    ))->display();
  }

  public function createTemplateGlossaryData($glossaryPath, $data) {
    $result = [];
    foreach ($data as $item) {
        $char = mb_strtoupper(mb_substr($item['term'], 0, 1));
        $result[$char] = $result[$char] ?? ['char' => $char, 'data' => []];
        $result[$char]['data'][] = $item;
    }
    usort($result, fn($a, $b) => $a['char'] <=> $b['char']);

    return $result;
  }
}