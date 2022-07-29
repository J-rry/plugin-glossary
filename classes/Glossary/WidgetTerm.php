<?php

namespace Glossary;

use \Laminas\Router\Http\Segment;

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

  static public function initPage($glossaryPath) {
    $router = \Cetera\Application::getInstance()->getRouter();
    $router->addRoute('glossary_term', Segment::factory([
			'route' => $glossaryPath . ':id[/]',
			'defaults' => ['controller' => '\Glossary\WidgetTerm', 'action' => 'index'],
	  ]), 1);
  }

  static public function index() {
    $a = \Cetera\Application::getInstance();
    $address = explode("/", $_SERVER['REQUEST_URI']);
    $termAlias = $address[count($address) - 1];
    $termData = Data::getTermDataByAlias($termAlias);

    if($termData === false) {
      $twig = $a->getTwig();
      $twig->display('page_section.twig', []);
    } else {
      $title = str_replace('{=term}', $termData['term'],  Options::getTermTitleMask());
      $description = str_replace('{=term}', $termData['term'],  Options::getTermDescriptionMask());
      $keywords = str_replace('{=term}', $termData['term'],  Options::getTermKeywordsMask());


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
  }

  protected function findTermReference($term) {
    $termAndSynonyms = empty($term['synonyms']) ? [$term['term']] : [$term['term'], ...mb_split(", ?", $term['synonyms'])];
    $mainCatalog = \Cetera\Application::getInstance()->getServer();
    $catalogs = $mainCatalog->getSubs();
    $data = array_reduce($catalogs, function($links, $id) use($termAndSynonyms, $mainCatalog) { 
      $catalog = $mainCatalog->getById($id);

      if($catalog->isHidden() || $catalog->getParent()->isHidden()) 
        return $links;
        
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
            $links[] = ['title' => $materials[$i]['name'], 'link' => $materials[$i]->getUrl()];
            break;
          }
        }
      }
      return $links;
    }, []);
    
    return $data;
  }
}