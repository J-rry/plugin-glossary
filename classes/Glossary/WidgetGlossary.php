<?php

namespace Glossary;

class WidgetGlossary extends \Cetera\Widget\Templateable
{
	use \Cetera\Widget\Traits\Material;

  protected $_params = array(
  'struct'         => '',
  'page_h1'        => 'Глоссарий',
  'css_class'      => 'widget-glossary',
  'template'       => 'default.twig',
  );

  static function index() {

    $glossary = new \Glossary\Glossary();
    $a = \Cetera\Application::getInstance();

    //Маски мета-тегов
    $configData = include __DIR__ . '/../../glossary_config.php';
    $title = $configData['GLOSSARY_TITLE'];
    $description = $configData['GLOSSARY_DESCRIPTION'];
    $keywords = $configData['GLOSSARY_KEYWORDS'];

    $a->setPageProperty('title', $title);
    $a->addHeadString('<meta property="og:title" content="'.$title.'"/>', 'og:title');
    $a->setPageProperty('description', $description);
    $a->addHeadString('<meta property="og:description" content="'.htmlspecialchars($description).'"/>', 'og:description');
    $a->setPageProperty('keywords', $keywords);

    $glossary->getGlossaryWidget()->display();
  }

}