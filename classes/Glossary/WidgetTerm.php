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

      $title = str_replace('{=term}', $termData[0],  $configData['TERMS_TITLE_MASK']);
      $description = str_replace('{=term}', $termData[0],  $configData['TERMS_DESCRIPTION_MASK']);
      $keywords = str_replace('{=term}', $termData[0],  $configData['TERMS_KEYWORDS_MASK']);

      $a->setPageProperty('title', $title);
      $a->addHeadString('<meta property="og:title" content="'.$title.'"/>', 'og:title');
      $a->setPageProperty('description', $description);
      $a->addHeadString('<meta property="og:description" content="'.htmlspecialchars($description).'"/>', 'og:description');
      $a->setPageProperty('keywords', $keywords);

      $a->getWidget('Term', array(
        'term'        => $termData[0],
        'description' => $termData[1],
        'synonyms'    => self::createSynonymsData($configData['GLOSSARY_PATH'], $data, $termData[2]),
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

  protected function createSynonymsData($glossaryPath, $data, $synonyms) {
    if(strlen($synonyms) === 0) {
      return [];
    }
    $synonymsArray = mb_split(", ?", $synonyms);
    $synonymsData = array_reduce($synonymsArray, function($dataResult, $synonym) use($glossaryPath, $data) {
      $dataSynonym = self::getSynonymData($glossaryPath, $data, $synonym);
      $dataSynonym['separator'] = count($dataResult) === 0 ? '' : ', ';
      $dataResult[] = $dataSynonym;
      return $dataResult;      
    }, []);

    return $synonymsData;
  }

  protected function getSynonymData($glossaryPath, $data, $synonym) {
    $alias = \Glossary\Glossary::toAlias($synonym);
    $isHavePage = count(array_filter($data, fn($term) => \Glossary\Glossary::toAlias($term[0]) === $alias));
    $path = $glossaryPath . $alias;

    return [
      'term' => $synonym,
      'link' => $isHavePage ? $path : ''
    ];
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
    $mainCatalog = \Cetera\Application::getInstance()->getServer();
    $catalogs = $mainCatalog->getSubs();
    $data = array_reduce($catalogs, function($links, $id) use($term, $mainCatalog) { 
      $catalog = $mainCatalog->getById($id);
      if(!$catalog->isHidden() && !$catalog->getParent()->isHidden()) {
        $materials = $catalog->getMaterials();
        for($i = 0; $i < count($materials); $i++) {
          $html = $materials[$i]['text'];
          $onlyText = implode("", mb_split("</?.*?>", $html));
          $regExp = '/([^a-zа-яА-ЯЁё\.-]' . $term[0] . '$|^' . $term[0] . '[^a-zа-яА-ЯЁё\.-]|[^a-zа-яА-ЯЁё\.-]'. $term[0] . '[^a-zа-яА-ЯЁё-])/ui';
          $isHaveTerm = preg_match($regExp, $onlyText);
    
          if($isHaveTerm === 1) {
            $links[] = ['title' => $materials[$i]['name'], 'link' => $materials[$i]->getUrl()];
          }
        }
      }
      return $links;
    }, []);
    
    return $data;
  }
}