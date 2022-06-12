<?php

namespace Glossary;

class Glossary{

  protected $filePath = '';
  public $data = [];
  public $alphabet = [];
  public $struct = [];

  public function __construct($filePath)
  {
    $this->filePath = $filePath;
    $this->data = $this->getData($filePath);
    $this->alphabet = $this->getAlphabet($this->data);
    $this->struct = $this->getDataStruct($this->data, $this->alphabet);
  }

  protected function getData($filePath) {
    $glossaryData = [];

    if (($file = fopen($filePath, 'r')) !== false) {
      while (($data = fgetcsv($file, 1000, ',')) !== false) {
        $glossaryData[] = $data;
      }
      fclose($file);
    }

    return $glossaryData;
  }

  protected function getAlphabet($data) {
    $alphabet = array_unique(array_map(
      fn($term) => mb_strtoupper(mb_substr($term[0], 0, 1))
      , $data));

    sort($alphabet);

    return $alphabet;
  }

  protected function getDataStruct($data, $alphabet) {
    $pageStruct = array_reduce($alphabet, 
      function($struct, $char) use ($data) {

        $struct[$char] = array_values(array_filter($data, 
          fn($term) => mb_strtoupper(mb_substr($term[0], 0, 1)) === mb_strtoupper($char)));

        return $struct;
    }, []);
    
    return $pageStruct;
  }

  protected function createFile($name, $content) {
    //Создаём файл с необходимым контентом
    $newFile = fopen($name, 'w+');
    fwrite($newFile, $content);
    fclose($newFile);
  }

  protected function getSynonymScheme($synonym) {

    $isHaveCart = count(array_filter($this->data, 
      fn($term) => mb_strtoupper($term[0]) === mb_strtoupper($synonym)));
  
    if($isHaveCart) {
      $anchor = mb_strtolower($synonym);
      $firstLetter = mb_substr($anchor, 0, 1);
      $href = "/glossary/$firstLetter";
      return "<a href='$href#glossary-$anchor'>$synonym</a>";
    }
  
    return $synonym;
  }

  protected function getLinkScheme($link) {
    if(strlen($link) !== 0) {
      $linkData = mb_split("\|{3}", $link);
      return "<a href='$linkData[1]'>$linkData[0]</a>";
    }
    return '';
  }

  protected function createCardContent($term, $specification, $synonyms, $links) {
    $anchor = mb_strtolower($term);
    $card = "
    <div class='glossary-card'>
      <a href='##' name='glossary-$anchor'></a>
      <h2 class='glossary-term'>$term</h2>
      <p  class='glossary-specification'>$specification</p>
      <div  class='glossary-synonyms'>Синонимы: $synonyms</div>
      <div  class='glossary-links'>Ссылки: $links</div>
    </div>";

    return $card;
    
  }

  protected function createCharPageScheme($pageData) {
    $pageContent = array_reduce($pageData, function($content, $data) {
      $term = $data[0];
      $specification = $data[1];
  
      $synonymsArray = mb_split(", ?", $data[2]);
      $synonyms = array_reduce($synonymsArray, function($list, $synonym) {
        $list .= strlen($list) === 0 ? '' : ', ';
        $list .= $this->getSynonymScheme($synonym);

        return $list;      
      }, "");
  
      $linksArray = mb_split(", ?", $data[3]);      
      $links = array_reduce($linksArray, function($list, $link) {
        $list .= strlen($list) === 0 ? '' : ', ';
        $list .= $this->getLinkScheme($link);
  
        return $list;
      }, "");
      
      $content .= $this->createCardContent($term, $specification, $synonyms, $links);

      return $content;
    }, "");

    return $pageContent;

  }

  protected function createGlossaryMainPage() {
    $dirName = './glossary';

    if(!is_dir($dirName)) {
      mkdir($dirName, 0777, true);
    }

    $pageContent = array_reduce($this->alphabet, function($content, $char) {
      $path = "/glossary/" . mb_strtolower($char);
      $content .= "<li><a href='$path' title='Glossary $char'>$char</a></li>";

      return $content;
    }, "");
    
    $page = "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <link rel='stylesheet' href='/styles/style.css'>
        <title>Glossary</title>
    </head>
    <body>
    <ul class='glossary'>
      $pageContent
    </ul>
    </body>
    </html>";

    //Создаём файл с необходимым контентом
    $this->createFile($dirName . '/' . 'index.php', $page);
  }

  protected function createGlossaryContent() {
    foreach($this->struct as $char => $cards) {
      $dirName = './glossary/' . mb_strtolower($char);

      //Создаём дирректорию
      if(!is_dir($dirName)) {
        mkdir($dirName, 0777, true);
      }

      //Шаблон страницы и добавление в него карточек
      $pageContent = $this->createCharPageScheme($cards);
      $page = "<!DOCTYPE html>
      <head>
          <link rel='stylesheet' href='/styles/style.css'>
          <title>Glossary-$char</title>
      </head>
      <body>
        <div class='glossary-library'>
          $pageContent
        </div>
      </body>
      </html>";

      //Создаём файл с необходимым контентом
      $this->createFile($dirName . '/' . 'index.php', $page);
    }
  }

  private function dataUpdater() {
    return '<?php
    $updatedData = json_decode(file_get_contents(\'php://input\'));

    if (($file = fopen(\'../glossary.csv\', \'w+\')) !== false) {
      foreach($updatedData as $data) {
        if (fputcsv($file, $data, \',\') === false) {
          return false;
        }
      }
      fclose($file);
    }';

  }

  public function createGlossary() {
    $this->createGlossaryMainPage();
    $this->createGlossaryContent();

    $this->createFile('./glossary/data.json', json_encode($this->data));
    $this->createFile('./glossary/update.php', $this->dataUpdater());
  }

  // public function updateData() {

  // }

}