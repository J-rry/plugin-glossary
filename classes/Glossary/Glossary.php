<?php

namespace Glossary;

class Glossary {

  use \Glossary\Traits\GlossaryTraits;

  static protected $data;
  static public $glossaryPath;

  static public function isHavePageMaterial($address) {
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

    return true;
  }

  protected function createDataForReferences($data) {
    $newData = array_reduce($data, function($result, $term) {
      $terms = empty($term[2]) ? [$term[0]] : [$term[0], ...mb_split(", ?", $term[2])];
      $groupData = array_map(fn($termName) => 
        ['term' => $termName, 'specification' => $term[1], 'alias' => self::toGlossaryAlias($term[0])], $terms);
      $result = [...$result, ...$groupData];
      return $result;
    }, []);

    return $newData;
  }

  protected function wrapTerm($text, $specification, $alias) {
    $glossaryPath = self::$glossaryPath;

    if($glossaryPath === '') {
      return "<abbr title='$specification'>$text</abbr>";
    }
    $link = $glossaryPath . $alias;
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
        if($termData['alias'] !== $termData2['alias'] && preg_match(self::termFindRegExp($termData['term']), $termData2['term']) === 1) {
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
    return '/([^a-z??-????-??????\.-]' . $term . '$|^' . $term . '[^a-z??-????-??????\.-]|[^a-z??-????-??????\.-]'. $term . '[^a-z??-????-??????-])/ui';
  }

  protected function findTermPos($newHtml, $term) {
    if($term['isFinded'] === false) {
      $withoutAbbrs = mb_ereg_replace_callback("<abbr.*?>.*?</abbr>", fn($match) => str_repeat('|', strlen($match[0])), $newHtml);
      $withoutLinks = mb_ereg_replace_callback("<a.*?>.*?</a>", fn($match) => str_repeat('|', strlen($match[0])), $withoutAbbrs);
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
      if($term['alias'] === $termData['alias'])
        $termData['isFinded'] = true;
      return $termData;
    }, $data);
    self::$data = $updateData;
  }

  static public function wrapTermsOnPage(&$res) {
    $materialPosData = self::findMaterialOnPage($res);
    if($materialPosData === false) {
      return;
    }
    $html = $materialPosData['material'];
    $newHtml = $html;
    self::$data = self::getContainsTermsData($html);
    for($i = 0; $i < count(self::$data); $i++) {
      $term = self::$data[$i];
      $findTerm = self::findTermPos($newHtml, $term);
      if($findTerm !== false) {
        $newHtml = substr_replace($newHtml, self::wrapTerm($findTerm['term'], $term['specification'], $term['alias']), $findTerm['start'], $findTerm['length']);
      } 
    }

    $res = substr_replace($res, $newHtml, $materialPosData['start'], $materialPosData['length']);
  }

  protected function findMaterialOnPage($res) {
    $isHaveMaterial = preg_match('/<div class="x-cetera-widget" data-class="Cetera.fo.Material"/', $res, $matches, PREG_OFFSET_CAPTURE);
    if($isHaveMaterial === false) {
      return false;
    }
    $start = $matches[0][1];
    $offset = $start + strlen($matches[0][0]);
    $openDiv = 0;
    
    while($openDiv !== -1) {
      preg_match('/<\/?div.*?>/', $res, $matches, PREG_OFFSET_CAPTURE, $offset);
      if(strpos($matches[0][0], '</div>') === false) {
        $openDiv++;
      } else {
        $openDiv--;
      }
      $offset = $matches[0][1] + strlen($matches[0][0]);
    }
    $end = $offset;
    return ['start' => $start, 'length' => $end - $start, 'material' => substr($res, $start, $end - $start)];
  }
}

