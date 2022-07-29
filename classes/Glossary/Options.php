<?php

namespace Glossary;

class Options {

    use \Cetera\DbConnection;

    public static function getPath(): String {
      return self::configGet('glossary_path') ?? '';
    }

    public static function getTitle(): String {
      return self::configGet('glossary_title') ?? '';
    }

    public static function getDescription() {
      return self::configGet('glossary_description') ?? '';
    }

    public static function getKeywords(): String {
      return self::configGet('glossary_keywords') ?? '';
    }

    public static function getTermTitleMask(): String {
      return self::configGet('term_title_mask') ?? '';
    }

    public static function getTermDescriptionMask(): String {
      return self::configGet('term_description_mask') ?? '';
    }

    public static function getTermKeywordsMask(): String {
      return self::configGet('term_keywords_mask') ?? '';
    }

    public static function setPath(String $glossaryPath) {
      self::configSet('glossary_path', $glossaryPath);
    }
    
    public static function setTitle(String $glossaryTitle) {
      self::configSet('glossary_title', $glossaryTitle);
    }
  
    public static function setDescription(String $glossaryDescription) {
      self::configSet('glossary_description', $glossaryDescription);
    }

    public static function setKeywords(String $glossaryKeywords) {
      self::configSet('glossary_keywords', $glossaryKeywords);
    }
     
    public static function setTermTitleMask(String $termTitleMask) {
      self::configSet('term_title_mask', $termTitleMask);
    }
      
    public static function setTermDescriptionMask(String $termDescriptionMask) {
      self::configSet('term_description_mask', $termDescriptionMask);
    }
    
    public static function setTermKeywordsMask(String $termKeywordsnMask) {
      self::configSet('term_keywords_mask', $termKeywordsnMask);
    }

    public static function getOptions() {
      return self::configGetAll() ?? [];
    }

}