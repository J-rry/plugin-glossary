<?php

namespace Glossary;

class Glossary {

  use \Glossary\Traits\GlossaryTraits;

  static protected $data;
  static protected $material;
  static public $glossaryPath;

  static public function pageMaterial($address) {
    $a = \Cetera\Application::getInstance();
    $catalog = $a->getCatalog();
    $catalogUrl = $catalog->getUrl();
    if ($address === $catalogUrl) {
      $materials = $catalog->getMaterials()->where("alias='index'");
    } else {
      $addressArr = explode("/", $address);
      $materialAlias = $addressArr[count($addressArr) - 1] === '' ? $addressArr[count($addressArr) - 2] : $addressArr[count($addressArr) - 1];
      $materials = $catalog->getMaterials()->where("alias='$materialAlias'");
    }
    if(count($materials) === 0) {
      return false;
    }
    self::$material = $materials[0];

    return $materials[0];
  }

  protected function createDataForReferences($data) {
    $glossaryPath = self::$glossaryPath;

    $newData = array_reduce($data, function($result, $term) use ($glossaryPath) {
      $terms = empty($term[2]) ? [$term[0]] : [$term[0], ...mb_split(", ?", $term[2])];
      $groupData = array_map(fn($termName) => ['term' => $termName, 'specification' => $term[1], 'link' => $glossaryPath . self::toGlossaryAlias($term[0])], $terms);
      $result = [...$result, ...$groupData];
      return $result;
    }, []);

    return $newData;
  }

  protected function wrapTerm($text, $specification, $link) {
    if($link === '') {
      return "<abbr title='$specification'>$text</abbr>";
    }
    return "<a href='$link' title='$specification'>$text</a>";
  }

  protected function getContainsTermsData($html) {
    $data = self::getGlossaryData();
    $termsAndSynonyms = self::createDataForReferences($data);

    $terms = array_reduce($termsAndSynonyms, function($result, $termData) use ($html) {
      if(mb_stripos($html, $termData['term']) !== false) {
        $termData['isFinded'] = false;
        $result[] = $termData;
      }
      return $result;
    },[]);
    
    return self::getOtherTermsContainsTerm($terms);
  }

  protected function getOtherTermsContainsTerm($terms) {
    $newData = array_reduce($terms, function($result, $termData) use ($terms) {
      $references = [];
      foreach($terms as $termData2) {
        if($termData['link'] !== $termData2['link'] && preg_match(self::termFindRegExp($termData['term']), $termData2['term']) === 1) {
          $references[] = $termData2['term'];
        }
      }
      $termData['containsTerms'] = $references;
      $result[] = $termData;
      return $result;
    }, []);
    return $newData;
  }

  protected function termFindRegExp($term) {
    return '/([^a-zа-яА-ЯЁё\.-]' . $term . '$|^' . $term . '[^a-zа-яА-ЯЁё\.-]|[^a-zа-яА-ЯЁё\.-]'. $term . '[^a-zа-яА-ЯЁё-])/ui';
  }

  protected function findTermPos($newHtml, $term) {
    if($term['isFinded'] === false) {
      $withoutLinks = mb_ereg_replace_callback("<a.*?>.*?</a>", fn($match) => str_repeat('|', strlen($match[0])), $newHtml);
      $onlyText = mb_ereg_replace_callback("</?.*?>", fn($match) => str_repeat('|', strlen($match[0])), $withoutLinks); 
      if(count($term['containsTerms']) !== 0) {
        foreach($term['containsTerms'] as $containingTerm) {
          $onlyText = mb_ereg_replace_callback($containingTerm, fn($match) => str_repeat('|', strlen($match[0])), $onlyText); 
        }
      }
      $regExp = self::termFindRegExp($term['term']);
      $isHaveTerm = preg_match($regExp, $onlyText, $matches, PREG_OFFSET_CAPTURE);
    }

    if($isHaveTerm === 1) {
      self::termFinded($term);
      return ['start' => $matches[0][1] + 1, 'length' => strlen($term['term']), 'term' => mb_substr($matches[0][0], 1, mb_strlen($term['term']))];
    }

    return false;
  }

  protected function termFinded($term) {
    $data = self::$data;

    $updateData = array_map(function($termData) use ($term) {
      if($term['link'] === $termData['link'])
        $termData['isFinded'] = true;
      return $termData;
    }, $data);
    self::$data = $updateData;
  }

  static public function wrapTermsOnPage() {
    $html = self::$material['text'];
    $newHtml = $html;
    self::$data = self::getContainsTermsData($html);
    for($i = 0; $i < count(self::$data); $i++) {
      $term = self::$data[$i];
      $findTerm = self::findTermPos($newHtml, $term);
      if($findTerm !== false) {
        $newHtml = substr_replace($newHtml, self::wrapTerm($findTerm['term'], $term['specification'], $term['link']), $findTerm['start'], $findTerm['length']);
      } 
    }

    self::log($newHtml);
    // self::$material['text'] = $newHtml;
    // $a = \Cetera\Application::getInstance();
    // $twig = $a->getTwig();
    // $catalog = $a->getCatalog();
    // $m = $catalog->getMaterials()->where("alias='webmaster'")[0];

    // $twig->display('page_section.twig', array("material" => $m));
  }

  static public function log($data) {
    echo '<hr>';
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
  }
}