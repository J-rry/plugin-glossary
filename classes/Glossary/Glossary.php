<?php

namespace Glossary;

class Glossary{

  protected $mainCatalog;
  protected $glossaryCatalog;
  protected $glossaryMaterial;
  protected $data;
  protected $struct;

  public function __construct() {
    $this->initGlossary();
  }

  public function reloadGlossary() {
    $this->glossaryCatalog->delete();
    $this->initGlossary();
  }

  public function initGlossary() {
    $this->mainCatalog = \Cetera\Application::getInstance()->getServer();
    $this->data = $this->getData();
    $this->struct = $this->getDataStruct();

    //Создаём раздел Глоссарий, если его нет
    $hasNotGlossary = empty($this->mainCatalog->findChildByAlias('glossary'));
    if($hasNotGlossary) {

      //Ищем упоминания терминов
      $this->initLinks();
      //$this->data = $this->getData();

      $this->mainCatalog->createChild([
        'name' => 'Глоссарий',
        'alias' => 'glossary',
        'typ' => \Cetera\ObjectDefinition::findByAlias('materials')
      ]);

      $this->glossaryCatalog = $this->mainCatalog->getChildByAlias('glossary');

      //Создаём главную страницу глоссария, если её нет
      $this->createGlossaryMaterial('index', 'Глоссарий', $this->createGlossaryContent());
      $this->glossaryMaterial = $this->glossaryCatalog->getMaterialByAlias('index');
  
      //Инициализируем создание матереалов, полученных терминов
      $this->initTermsPages();
    }

    $this->glossaryCatalog = $this->mainCatalog->getChildByAlias('glossary');
    $this->glossaryMaterial = $this->glossaryCatalog->getMaterialByAlias('index');
  }

  public function createDataForJS($data) {
    $newData = array_map(function($term) {
      $term[4] = $this->toAlias($term[0]);
      return $term;
    }, $data);

    return $newData;
  }

  protected function toAlias($name) {
    if(preg_match('/[А-Яа-яЁё]/u', $name)) {
      $name = mb_strtolower($name);
      $ruAlphabet = [
        'а' => 'a', 
        'б' => 'b', 
        'в' => 'v', 
        'г' => 'g', 
        'д' => 'd', 
        'е' => 'e', 
        'ё' => 'yo', 
        'ж' => 'j', 
        'з' => 'z', 
        'и' => 'i', 
        'й' => 'y', 
        'к' => 'k', 
        'л' => 'l', 
        'м' => 'm', 
        'н' => 'n', 
        'о' => 'o', 
        'п' => 'p', 
        'р' => 'r', 
        'с' => 's', 
        'т' => 't', 
        'у' => 'u', 
        'ф' => 'f', 
        'х' => 'h', 
        'ц' => 'c', 
        'ч' => 'ch', 
        'ш' => 'sh', 
        'щ' => 'shi', 
        'ъ' => 'w', 
        'ы' => 'ii', 
        'ь' => 'q', 
        'э' => 'ee', 
        'ю' => 'yu', 
        'я' => 'ya'
      ];
      $alias = 'ru-' . strtr($name, $ruAlphabet);
      return $alias;
    }
      
    return mb_strtolower($name);
  }

  public function toData($data) {
    return [
      $data['term'], 
      $data['specification'], 
      $data['synonyms'], 
      $data['links']
    ];
  }

  public function createNewTerm($term) {

    $term['links'] = $this->findTermReference($term);
    
    $this->data = $this->getData(0);

    $isUpdate = false;
    $newData = [];
    foreach($this->data as $data) {
      if((int)$data['id'] === (int)$term['id']) {
        $this->glossaryCatalog->getMaterialByAlias(mb_strtolower($data['term']))->delete();
        $newData[] = $term;
        $isUpdate = true;
      } else {
        $newData[] = $data;
      }
    }
    if(!$isUpdate) {
      $newData[] = $term;
    }

    $this->data = array_map(fn($data) => $this->toData($data), $newData);
    $this->struct = $this->getDataStruct();
    $this->updatePageContent($this->glossaryMaterial, "Глоссарий", $this->createGlossaryContent());

    $formatedTerm = $this->toData($term);
    $this->createTermPage($formatedTerm);

  }
  public function deleteTerm($id) {
    $termData = $this->getTermById($id);
    $material = $this->glossaryCatalog->getMaterialByAlias($this->toAlias($termData['term']));
    $material->delete();

    $this->deleteDataById($id);
    $this->struct = $this->getDataStruct();
    $this->updatePageContent($this->glossaryMaterial, "Глоссарий", $this->createGlossaryContent());
  }

  protected function deleteDataById($id) {
    $dataFromDB = include __DIR__ . '/../../g_data.php';
    $newData = array_filter($dataFromDB, function($data) use ($id) {
      return (int)$data['id'] !== $id;
    });
    $newData = array_map(fn($data) => $this->toData($data), $newData);
    $this->data = $newData;
  }

  public function initLinks() {
    //$dataFromDB = include __DIR__ . '/../../g_data.php';
    $data = $this->data;
    $newData = [];
    foreach($data as $term) {
      $term[3] = self::findTermReference($term, 0);
      $newData[] = $term;
    }
    $this->data = $newData;
  }

  //Создаёт материал в разделе Глоссарий
  protected function createGlossaryMaterial($alias, $name, $content) {
    $hasMaterial = count($this->glossaryCatalog->getMaterials()->where("alias='$alias'"));
    

    if(!$hasMaterial) {
      $page = $this->glossaryCatalog->createMaterial();
      $page->setFields([
        'alias'   => $alias,
        'publish' => true,
        'autor'   => 'admin',
        'text'    => $content,
        'name'    => $name
      ]);
      $page->save();
    } else {
      $material = $this->glossaryCatalog->getMaterialByAlias($alias);
      $this->updatePageContent($material, $name, $content);
    }
  }

  // protected static function getTermByName($name) {
  //   self::getData();
  //   $term = array_filter(self::$data, fn($nm) => $nm[0] === $name)[0];
  //   return $term;
  // }

  protected function getTermById($id) {
    $dataFromDB = include __DIR__ . '/../../g_data.php';
    $term = array_filter($dataFromDB, function($data) use($id) {
      return (int)$data['id'] === $id;
    });
    return array_values($term)[0];
  }

  //Получает данные из бд (и приводит к необходимому виду)
  protected function getData($dataType = 1) {
    $dataFromDB = include __DIR__ . '/../../g_data.php';

    if($dataType !== 1)
      return $dataFromDB;

    $data = array_reduce($dataFromDB, function($dataArray, $term) {
      $fields = [];
      $fields[0] = $term['term'];
      $fields[1] = $term['specification'];
      $fields[2] = $term['synonyms'];
      $fields[3] = $term['links'];
      $dataArray[] = $fields;

      return $dataArray;
    }, []);

    return $data;
  }

  //Получаем алфавит, на основании существующих терминов
  protected function getAlphabet() {
    $data = $this->data;
    $alphabet = array_unique(array_map(
      function($term) { 
        return mb_strtoupper(mb_substr($term[0], 0, 1));
      }, $data));

    sort($alphabet);

    return $alphabet;
  }

  //Получает структуру главной страницы глоссария в виде ассоциативного массива
  protected function getDataStruct() {
    $data = $this->data;
    $alphabet = $this->getAlphabet();

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
    $struct = $this->struct;

    $dataForJs = json_encode($this->createDataForJS($this->data));
    $dataContainer = "<div style='display: none;' data-glossary='$dataForJs'></div>";

    $content = $dataContainer;

    foreach($struct as $char => $terms) {

      $charTerms = '<ul style="margin-bottom: 30px;">';

      $charTerms .= array_reduce($terms, function($schema, $term) {
        $link = $this->toAlias($term[0]);
        $schema .= "<li><a title='Описание термина $term[0]' href='/glossary/$link'>$term[0]</a></li>";
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
    $data = $this->data;

    foreach($data as $termData) {
      $alias = $this->toAlias($termData[0]);
      $hasMaterial = count($this->glossaryCatalog->getMaterials()->where("alias='$alias'"));
      if(!$hasMaterial) {
        $this->createTermPage($termData);
      }
    }
  }

  //Обновляет контент в переданном материале
  protected function updatePageContent($material, $name, $content) {
    $material->setFields([
      'text' => $content,
      'name' => $name
    ]);
    $material->save();
  }

  //Создаёт материал термина и наполняет контентом, по переданным данным
  public function createTermPage($termData) {
    $alias = $this->toAlias($termData[0]);
    $content = $this->createTermPageContent($termData);
    $this->createGlossaryMaterial($alias, $termData[0], $content);
  }

  //Собирает разметку материала термина, по переданным данным
  protected function createTermPageContent($termData) {
    $specification = $termData[1];

    $synonymsArray = mb_split(", ?", $termData[2]);
    $synonyms = array_reduce($synonymsArray, function($list, $synonym) {
      $list .= strlen($list) === 0 ? '' : ', ';
      $list .= $this->getSynonymSchema($synonym);
      return $list;      
    }, "");

    $linksArray = mb_split(", ?", $termData[3]);      
    $links = array_reduce($linksArray, function($list, $link) {
      $list .= strlen($list) === 0 ? '' : ', ';
      $list .= $this->getLinkSchema($link);

      return $list;
    }, "");
    
    $content = $this->getTermPageSchema($specification, $synonyms, $links);

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
    $synonymAlias = $this->toAlias($synonym);
    $isHavePage = count(array_filter($this->data, 
      fn($term) => $this->toAlias($term[0]) === $synonymAlias));
  
    if($isHavePage) {
      $href = "/glossary/$synonymAlias";
      return "<a title='Описание термина $synonym' href='$href'>$synonym</a>";
    }
  
    return $synonym;
  }

  //Разметка для ссылок
  protected function getLinkSchema($link) {
    if(strlen($link) !== 0) {
      $linkData = mb_split("\|{3}", $link);
      return "<a title='$linkData[0]' href='$linkData[1]'>$linkData[0]</a>";
    }
    return '';
  }

  protected function getLinkData($link) {
    $linkData = mb_split("\|{3}", $link);
    return ['title' => $linkData[0], 'link' => $linkData[1]];
  }

  protected function getLinksData($links) {
    $linksArr = mb_split(", ?", $links);
    $data = array_reduce($linksArr, function($dt, $linkData) {
      $dt[] = self::getLinkData($linkData);
      return $dt;
    }, []);
    return $data;
  }

  protected function findTermReference($term, $dataType = 1) {
    if($dataType === 1)
      $termData = $this->toData($term);
    else
      $termData = $term;

    $catalogs = $this->mainCatalog->getSubs();
    $data = array_reduce($catalogs, function($cur, $id) use($termData) {

      $catalog = $this->mainCatalog->getById($id);
      $isCatalogHidden = $catalog->isHidden();
      $isParentHidden = $catalog->getParent()->isHidden();

      if(!$isCatalogHidden && !$isParentHidden) {
        if($catalog['alias'] !== 'glossary') {
          $materials = $this->mainCatalog->getById($id)->getMaterials();

          for($i = 0; $i < count($materials); $i++) {

            $html = $materials[$i]['text'];
            $onlyText = mb_split("</?.*?>", $html);
            $onlyText = implode("", $onlyText);
            $words = mb_split("[^A-Za-zа-яА-ЯЁё-]", $onlyText);
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
/*
  public function toFile($data, $var) {
    if($var) {
      file_put_contents(dirname(__FILE__).'/../../test.php',"<?php\nreturn " . var_export($data, true) . ";");
    } else {
      file_put_contents(dirname(__FILE__).'/../../test.php',"<?php\nreturn " . $data . ";");
    } 
  }
*/
}