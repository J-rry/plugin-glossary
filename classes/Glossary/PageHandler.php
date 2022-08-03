<?php

namespace Glossary;

class PageHandler {

  static protected $data;
  static protected $glossaryPath;

  static public function init($glossaryPath) {
    $address = $_SERVER['REQUEST_URI'];

    if(empty($glossaryPath) || strpos($address, $glossaryPath) === false) {
      self::$glossaryPath = $glossaryPath;
      $application = \Cetera\Application::getInstance();
      $application->registerOutputHandler(["\Glossary\PageHandler", "wrapTermsOnPage"]);
    }
  }

  protected function createDataForReferences($data) {
    $newData = array_reduce($data, function($result, $term) {
      $termsAndSynonyms = empty($term['synonyms']) ? [$term['term']] : [$term['term'], ...mb_split(", ?", $term['synonyms'])];
      $termsAndSynonymsData = array_map(fn($termName) => 
        ['term' => $termName, 'specification' => $term['specification'], 'alias' => $term['alias']], $termsAndSynonyms);
      $result = [...$result, ...$termsAndSynonymsData];
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
    $data = Data::getData();
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

  public function termFindRegExp($term) {
    return '/([^a-zа-яА-ЯЁё\.-]' . $term . '$|^' . $term . '[^a-zа-яА-ЯЁё\.-]|[^a-zа-яА-ЯЁё\.-]'. $term . '[^a-zа-яА-ЯЁё-])/ui';
  }

  protected function replaceNoIndexContent($html) {
    $noIndexTags = 
    '<abbr.*?>.*?</abbr>|<a.*?>.*?</a>|<form.*?>.*?</form>|<script.*?>.*?</script>|<style.*?>.*?</style>|<title.*?>.*?</title>|<h1.*?>.*?</h1>|<!--.*?-->|<button.*?>.*?</button>|<head.*?>.*?</head>|<iframe.*?>.*?</iframe>|<embed.*?>.*?</embed>|<object.*?>.*?</object>|<audio.*?>.*?</audio>|<video.*?>.*?</video>|<source.*?>.*?</source>|<pre.*?>.*?</pre>|<nav.*?>.*?</nav>|<svg.*?>.*?</svg>|<code.*?>.*?</code>|<cite.*?>.*?</cite>|<canvas.*?>.*?</canvas>';
    $withoutNoIndexTags = mb_ereg_replace_callback($noIndexTags, fn($match) => str_repeat('|', strlen($match[0])), $html);
    $onlyText = mb_ereg_replace_callback("<.*?>", fn($match) => str_repeat('|', strlen($match[0])), $withoutNoIndexTags); 

    return $onlyText;
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
    $newHtml = $res;
    $htmlWithoutNoIndexContent = self::replaceNoIndexContent($newHtml);
    self::$data = self::getContainsTermsData($newHtml);
    for($i = 0; $i < count(self::$data); $i++) {
      $term = self::$data[$i];
      $findTerm = self::findTermPos($htmlWithoutNoIndexContent, $newHtml, $term);
      if($findTerm !== false) {
        $wrappedTerm = self::wrapTerm($findTerm['term'], $term['specification'], $term['alias']);
        $newHtml = substr_replace($newHtml, $wrappedTerm, $findTerm['start'], $findTerm['length']);
        $htmlWithoutNoIndexContent = substr_replace($htmlWithoutNoIndexContent, str_repeat('|', strlen($wrappedTerm)), $findTerm['start'], $findTerm['length']);
      } 
    }

    $res = $newHtml;
  }
}