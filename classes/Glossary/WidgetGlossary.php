<?php

namespace Glossary;

class WidgetGlossary extends \Cetera\Widget\Templateable
{
	use \Cetera\Widget\Traits\Material;

  protected $_params = array(
  'struct'         => '',
  'css_class'      => 'widget-glossary',
  'template'       => 'default.twig',
  );

  public function __construct() {
    $glossary = new \Glossary\Glossary();
    $glossaryCatalog = \Cetera\Application::getInstance()->getCatalog();
    $this->_params['struct'] = $glossary->createTemplateGlossaryData($glossaryCatalog);
  }

}