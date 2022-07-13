<?php

namespace Glossary;

class WidgetTerm extends \Cetera\Widget\Templateable
{
	use \Cetera\Widget\Traits\Material;

  protected $_params = array(
  'term'           => '',
  'description'    => '',
  'synonyms'       => '',
  'links'          => '',
  'css_class'      => 'widget-glossary-term',
  'template'       => 'default.twig',
  );

  static function index() {
    $a = \Cetera\Application::getInstance();
    $address = explode("/", $_SERVER['REQUEST_URI']);
    $termAlias = $address[count($address) - 1];
    $glossary = new \Glossary\Glossary();
    $termData = $glossary->getDataByAlias($termAlias);


    if($termData === null) {
      $twig = $a->getTwig();
      $twig->display('page_section.twig', []);
    } else {
      //Маски мета-тегов
      $configData = include __DIR__ . '/../../glossary_config.php';
      $title = str_replace('{=term}', $termData[0],  $configData['TERMS_TITLE_MASK']);
      $description = str_replace('{=term}', $termData[0],  $configData['TERMS_DESCRIPTION_MASK']);
      $keywords = str_replace('{=term}', $termData[0],  $configData['TERMS_KEYWORDS_MASK']);

      $a->setPageProperty('title', $title);
      $a->addHeadString('<meta property="og:title" content="'.$title.'"/>', 'og:title');
      $a->setPageProperty('description', $description);
      $a->addHeadString('<meta property="og:description" content="'.htmlspecialchars($description).'"/>', 'og:description');
      $a->setPageProperty('keywords', $keywords);

      $glossary->getTermWidget($termData)->display();
    }
  }

}