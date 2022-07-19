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

    $data = \Glossary\Glossary::getData();
    $termData = self::getDataByAlias($data, $termAlias);


    if($termData === null) {
      $twig = $a->getTwig();
      $twig->display('page_section.twig', []);
    } else {
      $configData = \Glossary\Glossary::getConfigData();

      $title = str_replace('{=term}', $termData[0],  $configData['term_title_mask']);
      $description = str_replace('{=term}', $termData[0],  $configData['term_description_mask']);
      $keywords = str_replace('{=term}', $termData[0],  $configData['term_keywords_mask']);


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
        'term'        => $termData[0],
        'description' => $termData[1],
        'synonyms'    => self::createSynonymsData($termData[2]),
        'links'       => self::createLinksData($termData[3])
        ))->display();
    }
  }

  public function getDataByAlias($data, $alias) {
    $termData = array_values(array_filter($data, fn($term) => $alias === \Glossary\Glossary::toAlias($term[0])));
    if(!count($termData)) {
      return null;
    }
    $termData = $termData[0];
    $termData[3] = self::findTermReference($termData);

    return $termData;
  }

  protected function createSynonymsData($synonyms) {
    if(strlen($synonyms) === 0) {
      return [];
    }
    $synonymsArray = mb_split(", ?", $synonyms);
    $synonymsData = array_reduce($synonymsArray, function($dataResult, $synonym) {
      $dataSynonym['synonym'] = $synonym;
      $dataSynonym['separator'] = count($dataResult) === 0 ? '' : ', ';
      $dataResult[] = $dataSynonym;
      return $dataResult;      
    }, []);

    return $synonymsData;
  }

  protected function createLinksData($links) {
    if(count($links) === 0) {
      return [];
    }
    $linksData = array_reduce($links, function($data, $link) {
      $dataLink = $link;
      $dataLink['separator'] = count($data) === 0 ? '' : ', ';
      $data[] = $dataLink;
      return $data;      
    }, []);

    return $linksData;
  }

  protected function findTermReference($term) {
    $termAndSynonyms = empty($term[2]) ? [$term[0]] : [$term[0], ...mb_split(", ?", $term[2])];
    $mainCatalog = \Cetera\Application::getInstance()->getServer();
    $catalogs = $mainCatalog->getSubs();
    $data = array_reduce($catalogs, function($links, $id) use($termAndSynonyms, $mainCatalog) { 
      $catalog = $mainCatalog->getById($id);
      if(!$catalog->isHidden() && !$catalog->getParent()->isHidden()) {
        $materials = $catalog->getMaterials();
        for($i = 0; $i < count($materials); $i++) {
          $html = $materials[$i]['text'];
          foreach($termAndSynonyms as $term) {
            $termPos = mb_stripos($html, $term);
            if($termPos !== false) {
              $onlyText = implode("", mb_split("</?.*?>", $html));
              $regExp = '/([^a-zа-яА-ЯЁё\.-]' . $term . '$|^' . $term . '[^a-zа-яА-ЯЁё\.-]|[^a-zа-яА-ЯЁё\.-]'. $term . '[^a-zа-яА-ЯЁё-])/ui';
              $isHaveTerm = preg_match($regExp, $onlyText);
        
              if($isHaveTerm === 1) {
                $links[] = ['title' => $materials[$i]['name'], 'link' => $materials[$i]->getUrl()];
                break;
              }
            }
          }
        }
      }
      return $links;
    }, []);
    
    return $data;
  }
}