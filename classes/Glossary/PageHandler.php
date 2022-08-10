<?php

namespace Glossary;

class PageHandler {

  static protected $data;

  static public function init() {
    $application = \Cetera\Application::getInstance();
    $application->registerOutputHandler(["\Glossary\PageHandler", "wrapTermsOnPage"]);
  }

  static public function wrapTermsOnPage(&$res) {
    $newHtml = $res;
    $htmlWithoutNoIndexContent = self::replaceNoIndexContent($newHtml);
    self::$data = self::getContainsTermsData($htmlWithoutNoIndexContent);
    for($i = 0; $i < count(self::$data); $i++) {
      $term = self::$data[$i];
      $findTerm = self::findTermPos($htmlWithoutNoIndexContent, $newHtml, $term);
      if($findTerm !== false) {
        $wrappedTerm = self::wrapTerm($findTerm['term'], $term['url']);
        $newHtml = substr_replace($newHtml, $wrappedTerm, $findTerm['start'], $findTerm['length']);
        $htmlWithoutNoIndexContent = substr_replace($htmlWithoutNoIndexContent, str_repeat('|', strlen($wrappedTerm)), $findTerm['start'], $findTerm['length']);
      } 
    }

    $res = $newHtml;
  }

  protected function replaceNoIndexContent($html) {
    $noIndexTags = 
    '<abbr.*?>.*?</abbr>|<a.*?>.*?</a>|<form.*?>.*?</form>|<script.*?>.*?</script>|<style.*?>.*?</style>|<title.*?>.*?</title>|<h\d.*?>.*?</h\d>|<!--.*?-->|<button.*?>.*?</button>|<head.*?>.*?</head>|<iframe.*?>.*?</iframe>|<embed.*?>.*?</embed>|<object.*?>.*?</object>|<audio.*?>.*?</audio>|<video.*?>.*?</video>|<source.*?>.*?</source>|<pre.*?>.*?</pre>|<nav.*?>.*?</nav>|<svg.*?>.*?</svg>|<code.*?>.*?</code>|<cite.*?>.*?</cite>|<canvas.*?>.*?</canvas>';
    $withoutNoIndexTags = mb_ereg_replace_callback($noIndexTags, fn($match) => str_repeat('|', strlen($match[0])), $html);
    $onlyText = mb_ereg_replace_callback("<.*?>", fn($match) => str_repeat('|', strlen($match[0])), $withoutNoIndexTags); 

    return $onlyText;
  }

  protected function getContainsTermsData($html) {
    $data = self::getData();
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

  protected function getData() {
    $typeId = \Cetera\ObjectDefinition::findByAlias('glossary')->getId();
    $glossaryMaterials = \Cetera\ObjectDefinition::findById($typeId)->getMaterials();

    $data = [];
    for($i = 0; $i < count($glossaryMaterials); $i++) {
      $data[$glossaryMaterials[$i]['alias']] = Data::getTermDataFromMaterial($glossaryMaterials[$i]);
    }
    return array_values($data);
  }

  protected function createDataForReferences($data) {
    $newData = array_reduce($data, function($result, $term) {
      $termsAndSynonyms = empty($term['synonyms']) ? [$term['term']] : [$term['term'], ...mb_split(", ?", $term['synonyms'])];
      $termsAndSynonymsData = array_map(fn($termName) => 
        ['term' => $termName, 'specification' => $term['specification'], 'url' => $term['url']], $termsAndSynonyms);
      $result = [...$result, ...$termsAndSynonymsData];
      return $result;
    }, []);
    return $newData;
  }

  protected function getOtherTermsContainsTerm($terms) {
    $newData = array_reduce($terms, function($result, $termData) use ($terms) {
      $references = [];
      foreach($terms as $termData2) {
        if($termData['url'] !== $termData2['url'] && preg_match(self::termFindRegExp($termData['term']), $termData2['term']) === 1) {
          $references[] = $termData2['term'];
        }
      }
      $termData['containsTerms'] = $references;
      $result[] = $termData;
      return $result;
    }, []);
    return $newData;
  }

  protected function findTermPos($htmlWithoutNoIndexContent, $newHtml, $term) {
    if($term['isFinded'] === false) {
      if(count($term['containsTerms']) !== 0) {
        foreach($term['containsTerms'] as $containingTerm) {
          $htmlWithoutNoIndexContent = mb_ereg_replace_callback($containingTerm, fn($match) => str_repeat('|', strlen($match[0])), $htmlWithoutNoIndexContent); 
        }
      }
      $regExp = self::termFindRegExp($term['term']);
      $isHaveTerm = preg_match($regExp, $htmlWithoutNoIndexContent, $matches, PREG_OFFSET_CAPTURE);
    }

    if($isHaveTerm === 1) {
      self::termFinded($term);
      return ['start' => $matches[0][1] + 1, 'length' => strlen($term['term']), 'term' => mb_substr($matches[0][0], 1, mb_strlen($term['term']))];
    }

    return false;
  }

  public function termFindRegExp($term) {
    return '/([^a-zа-яА-ЯЁё\.-]' . $term . '$|^' . $term . '[^a-zа-яА-ЯЁё\.-]|[^a-zа-яА-ЯЁё\.-]'. $term . '[^a-zа-яА-ЯЁё-])/ui';
  }

  protected function termFinded($term) {
    $data = self::$data;

    $updateData = array_map(function($termData) use ($term) {
      if($term['url'] === $termData['url'])
        $termData['isFinded'] = true;
      return $termData;
    }, $data);
    self::$data = $updateData;
  }

  protected function wrapTerm($text, $link) {
    return "<a href='$link' title='Определение термина &#171;$text&#187;'>$text</a>";
  }
}