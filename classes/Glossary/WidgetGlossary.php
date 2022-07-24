<?php

namespace Glossary;

class WidgetGlossary extends \Cetera\Widget\Templateable
{
	use \Cetera\Widget\Traits\Material;
  use \Glossary\Traits\GlossaryTraits;

  protected $_params = array(
  'struct'         => '',
  'page_h1'        => 'Глоссарий',
  'css_class'      => 'widget-glossary',
  'template'       => 'default.twig',
  );

  static public function index() {

    $a = \Cetera\Application::getInstance();

    $configData = self::getGlossaryConfigData();
    $data = self::getGlossaryData();
    $glossaryPath = $configData['glossary_path'];

    //Маски мета-тегов
    $title = $configData['glossary_title'];
    $description = $configData['glossary_description'];
    $keywords = $configData['glossary_keywords'];

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
      'struct' => self::createTemplateGlossaryData($glossaryPath, $data)
    ))->display();
  }

  //Получаем алфавит, на основании существующих терминов
  protected function getAlphabet($data) {
    $alphabet = array_unique(array_map(fn($term) => mb_strtoupper(mb_substr($term[0], 0, 1)), $data));
    sort($alphabet);

    return $alphabet;
  }

  //Получает структуру главной страницы глоссария в виде массива
  public function createTemplateGlossaryData($glossaryPath, $data) {
    $alphabet = self::getAlphabet($data);

    $dataStruct = array_reduce($alphabet, 
      function($struct, $char) use ($glossaryPath, $data) {
        $item = [];
        $item['char'] = $char;
        $item['data'] = array_reduce($data, 
          function($newData, $term) use($glossaryPath, $char) {
            if(mb_strtoupper(mb_substr($term[0], 0, 1)) === mb_strtoupper($char)) {
              $newData[] = [
                'term'  => $term[0], 
                'path' => $glossaryPath . self::toGlossaryAlias($term[0])
              ];
            }  
            return $newData;
          }, []);
        $struct[] = $item;
        return $struct;
    }, []);
    return $dataStruct;
  }
}