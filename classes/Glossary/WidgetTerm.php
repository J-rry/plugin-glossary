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
      $twig->display('page_404.twig', []);
    } else {
      //Маски мета-тегов
      $title = "Описание термина &#171;" . $termData[0] . "&#187;";
      $description = "Глоссарий. Описание термина &#171;" . $termData[0] . "&#187;";
      $keywords = "Глоссарий, описание, термин, " . $termData[0];

      $a->setPageProperty('title', $title);
      $a->addHeadString('<meta property="og:title" content="'.$title.'"/>', 'og:title');
      $a->setPageProperty('description', $description);
      $a->addHeadString('<meta property="og:description" content="'.htmlspecialchars($description).'"/>', 'og:description');
      $a->setPageProperty('keywords', $keywords);

      echo $glossary->getTermWidget($termData);
    }
  }

}