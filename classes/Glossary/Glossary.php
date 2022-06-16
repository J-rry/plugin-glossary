<?php

namespace Glossary;

use Cetera\Section;
//use Cetera\Application;

class Glossary{

  static $dir;
  static $glossaryCatalog;
  static $glossaryMaterial;
  static $data;

  public static function testCode() {
    //Получить данные из материала
    //$data = self::$glossaryMaterial['text'];

    //Проверяем, есть ли материал
    //$data = self::$glossaryCatalog->getMaterials()->where('alias="index"');

    //$data = self::$glossaryCatalog->getMaterialByAlias('index')['show_future'];

    self::toFile($data, true);
  }

  public function toData($data) {
    $fields = [];
    $fields[0] = $data['term'];
    $fields[1] = $data['specification'];
    $fields[2] = $data['synonyms'];
    $fields[3] = $data['links'];

    return $fields;
  }

  public function toFile($data, $var) {
    if($var) {
      file_put_contents(dirname(__FILE__).'/../../test.php',"<?php\nreturn " . var_export($data, true) . ";");
    } else {
      file_put_contents(dirname(__FILE__).'/../../test.php',"<?php\nreturn " . $data . ";");
    } 
  }

  public static function inint(){

    //Получаем корневой каталог
    $section = Section::getByID(1);
    self::$dir = $section;

    //Ищем упоминания терминов
    self::initLinks();

    //Получаем данные из бд
    self::getData();

    $hasNotGlossary = empty($section->findChildByAlias('glossary'));

    //Создаём раздел Глоссарий
    if($hasNotGlossary) {
      $section->createChild([
        'name' => 'Глоссарий',
        'alias' => 'glossary',
        'typ' => \Cetera\ObjectDefinition::findByAlias('materials')
      ]);
    }

    self::$glossaryCatalog = $section->getChildByAlias('glossary');

    //Создаём главную страницу глоссария
    self::createGlossaryMaterial('index', 'Глоссарий');
    self::$glossaryMaterial = self::$glossaryCatalog->getMaterialByAlias('index');

    //Наполняем главную страницу контентом
    self::updatePageContent(self::$glossaryMaterial, 'Глоссарий', self::createGlossaryContent());

    //Инициализируем создание матереалов, полученных терминов
    self::initTermsPages();
  }

  public static function initLinks() {
    $dataFromDB = include __DIR__ . '/../../g_data.php';
    $newData = [];
    foreach($dataFromDB as $term) {
      $term['links'] = self::findTermReference($term);
      $newData[] = $term;
      $query = 'UPDATE g_list_plugin SET term=?, specification=?, synonyms=?, links=? WHERE term=' . $term['term'];
      \Cetera\Application::getInstance()->getConn()->executeQuery($query, $term);
    }
    file_put_contents(dirname(__FILE__).'/../../g_data.php',"<?php\nreturn " . var_export($newData, true) . ";");
  }

  //Создаёт материал в разделе Глоссарий
  public static function createGlossaryMaterial($alias, $name) {
    $hasMaterial = count(self::$glossaryCatalog->getMaterials()->where("alias='$alias'"));

    if(!$hasMaterial) {
      $page = self::$glossaryCatalog->createMaterial();
      $page->setFields([
        'alias'   => $alias,
        'publish' => true,
        'autor'   => 'admin',
        'name'    => $name
      ]);
      $page->save();
    }
  }

  //Получает данные из бд и приводит к необходимому виду
  protected function getData() {
    $dataFromDB = include __DIR__ . '/../../g_data.php';

    $data = array_reduce($dataFromDB, function($dataArray, $term) {
      $fields = [];
      $fields[0] = $term['term'];
      $fields[1] = $term['specification'];
      $fields[2] = $term['synonyms'];
      $fields[3] = $term['links'];
      $dataArray[] = $fields;

      return $dataArray;
    }, []);

    self::$data = $data;
  }

  //Получаем алфавит, на основании существующих терминов
  protected function getAlphabet() {
    $data = self::$data;
    $alphabet = array_unique(array_map(
      function($term) { 
        return mb_strtoupper(mb_substr($term[0], 0, 1));
      }, $data));

    sort($alphabet);

    return $alphabet;
  }

  //Получает структуру главной страницы глоссария в виде ассоциативного массива
  protected function getDataStruct() {
    $data = self::$data;
    $alphabet = self::getAlphabet();

    $dataStruct = array_reduce($alphabet, 
      function($struct, $char) use ($data) {
        $struct[$char] = array_values(array_filter($data, 
          function($term) use($char) {
            return mb_strtoupper(mb_substr($term[0], 0, 1)) === mb_strtoupper($char);
          }
        ));

        return $struct;
    }, []);
    
    return $dataStruct;
  }

  //Создаёт разметку главной страницы глоссария
  protected function createGlossaryContent() {
    $struct = self::getDataStruct();

    $content = '';

    foreach($struct as $char => $terms) {

      $charTerms = '<ul style="margin-bottom: 30px;">';

      $charTerms .= array_reduce($terms, function($schema, $term) {
        $link = mb_strtolower($term[0]);
        $schema .= "<li><a href='/glossary/$link'>$term[0]</a></li>";
        return $schema;
      },'');

      $charTerms .= '</ul>';

      $content .= "
        <h2>$char</h2>
        $charTerms
      ";
    }
    return $content;
  }

  //Создаёт материалы полученных из бд терминов и наполняет их контентом
  public function initTermsPages() {
    $data = self::$data;

    foreach($data as $termData) {
      $hasMaterial = count(self::$glossaryCatalog->getMaterials()->where("alias='$termData[0]'"));
      if(!$hasMaterial) {
        self::createTermPage($termData);
      }
    }
  }

  //Обновляет контент в переданном материале
  public static function updatePageContent($material, $name, $content) {
    $material->setFields([
      'text' => $content,
      'name' => $name
    ]);
    $material->save();
  }

  //Создаёт материал термина и наполняет контентом, по переданным данным
  protected function createTermPage($termData) {

    $alias = mb_strtolower($termData[0]);

    self::createGlossaryMaterial($alias, $termData[0]);

    $material = self::$glossaryCatalog->getMaterialByAlias($alias);
    $content = self::createTermPageContent($termData);

    self::updatePageContent($material, $termData[0], $content);
  }

  //Собирает разметку материала термина, по переданным данным
  protected function createTermPageContent($termData) {
    $specification = $termData[1];

    $synonymsArray = mb_split(", ?", $termData[2]);
    $synonyms = array_reduce($synonymsArray, function($list, $synonym) {
      $list .= strlen($list) === 0 ? '' : ', ';
      $list .= self::getSynonymSchema($synonym);
      return $list;      
    }, "");

    $linksArray = mb_split(", ?", $termData[3]);      
    $links = array_reduce($linksArray, function($list, $link) {
      $list .= strlen($list) === 0 ? '' : ', ';
      $list .= self::getLinkSchema($link);

      return $list;
    }, "");
    
    $content = self::getTermPageSchema($specification, $synonyms, $links);

    return $content;
  }

  //Разметка страницы термина
  protected function getTermPageSchema($specification, $synonyms, $links) {
    $card = "<p class='h2'>$specification</p>";
    $card .= mb_strlen($synonyms) ? "<p class='h3'>Синонимы: $synonyms</p>" : "";
    $card .= mb_strlen($links) ? "<p class='h3'>Ссылки: $links</p>" : "";

    return $card;
  
  }

  //Разметка для синонимов
  protected function getSynonymSchema($synonym) {
    $isHavePage = count(array_filter(self::$data, 
      fn($term) => mb_strtoupper($term[0]) === mb_strtoupper($synonym)));
  
    if($isHavePage) {
      $link = mb_strtolower($synonym);
      $href = "/glossary/$link";
      return "<a href='$href'>$synonym</a>";
    }
  
    return $synonym;
  }

  //Разметка для ссылок
  protected function getLinkSchema($link) {
    if(strlen($link) !== 0) {
      $linkData = mb_split("\|{3}", $link);
      return "<a href='$linkData[1]'>$linkData[0]</a>";
    }
    return '';
  }

  public function getLinkData($link) {
    $linkData = mb_split("\|{3}", $link);
    return ['title' => $linkData[0], 'link' => $linkData[1]];
  }

  public function getLinksData($links) {
    $linksArr = mb_split(", ?", $links);
    $data = array_reduce($linksArr, function($dt, $linkData) {
      $dt[] = self::getLinkData($linkData);
      return $dt;
    }, []);
    return $data;
  }

  public static function findTermReference($term) {
    $termData = self::toData($term);
    $catalogs = self::$dir->getSubs();
    $data = array_reduce($catalogs, function($cur, $id) use($termData) {

      $catalog = self::$dir->getById($id);
      $isCatalogHidden = $catalog->isHidden();

      if(!$isCatalogHidden) {
        if($catalog['alias'] !== 'glossary') {
          $materials = self::$dir->getById($id)->getMaterials();

          for($i = 0; $i < count($materials); $i++) {

            $html = $materials[$i]['text'];
            $onlyText = mb_split("</?.*?>", $html);
            $onlyText = implode("", $onlyText);
            $words = mb_split("\W+", $onlyText);
            $isHaveTerm = count(array_filter($words, 
              fn($word) => mb_strtolower($word) === mb_strtolower($termData[0])));

            if($isHaveTerm) {
              $links[] = $materials[$i]['name'] . "|||" . $materials[$i]->getUrl();
            }
          }
        }
      }
      if(!empty($links))
        $cur[] = implode(", ", $links);

      return $cur;
    }, []);

    return implode(", ", $data);
  }

}