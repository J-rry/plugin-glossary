<?php

namespace Glossary;

class Glossary {

  protected $mainCatalog;
  protected $glossaryCatalog;
  protected $glossaryMaterials;
  protected $termsCatalog;
  protected $data;

  public function __construct() {
    $this->mainCatalog = \Cetera\Application::getInstance()->getServer();
    $this->data = $this->getData();
    $this->glossaryMaterials = include dirname(__FILE__) . '/../../glossary_materials.php';
  }

  public function initGlossary($material) {
    $this->glossaryCatalog = $material->getCatalog();
    $this->termsCatalog = $this->glossaryCatalog->findChildByAlias('terms');
    $hasNotTermsCatalog = empty($this->termsCatalog);

    if($hasNotTermsCatalog) {
      $this->initLinks();
      $this->glossaryCatalog->createChild([
        'name' => 'Термины',
        'alias' => 'terms',
        'typ' =>  \Cetera\ObjectDefinition::findByAlias('materials')
      ]);

      $this->termsCatalog = $this->glossaryCatalog->getChildByAlias('terms');
      $this->initTermsPages($this->termsCatalog);
    }
  }

  public function isWidgetNeedInit($material) {
    $isPageHaveGlossaryWidget = mb_strpos($material['text'], '<cms action="widget" class="widget-Glossary" widgetname="Glossary"') !== false;
    $glossaryMaterials = $this->glossaryMaterials;
    $materialsWithoutAdded = array_values(array_filter($glossaryMaterials, function($mat) use($material) {
      return (int)$mat['material']['id'] !== (int)$material['id'];
    }));

    $isMaterialExist = count($materialsWithoutAdded) !== count($glossaryMaterials);
    if(!$isPageHaveGlossaryWidget && $isMaterialExist) {
      $catalogId = $material->getCatalog()['id'];
      $this->deleteTermCatalogByParentId($catalogId);
      $this->toFile('glossary_materials', $materialsWithoutAdded, true);
      return false;
    } else if(($isPageHaveGlossaryWidget && $isMaterialExist) || (!$isPageHaveGlossaryWidget && !$isMaterialExist)) {
      return false;
    } else {
      return true;
    }
  }

  public function addGlossaryMaterial($material) {
    $glossaryCatalog = $this->glossaryCatalog;

    $existGlossaryMaterials = $this->glossaryMaterials;

    $newData = [
      'catalog' => [
        'id' => $glossaryCatalog['id'], 
        'alias' => $glossaryCatalog['alias']
      ],
      'material' => [
        'id' => $material['id'], 
        'alias' => $material['alias']
      ]
    ];

    $existGlossaryMaterials[] = $newData;
    $this->toFile('glossary_materials', $existGlossaryMaterials, true);
    return true;
  }

  public function createNewTerm($term) {
    $term['links'] = $this->findTermReference($this->toData($term));

    $data = $this->getData(0);
    $aliasUpdate = array_values(array_filter($data, function($termData) use ($term) {
      return $termData['id'] === $term['id'] && $this->toAlias($termData['term']) !== $this->toAlias($term['term']);
    }));
    
    $glossaryMaterials = $this->glossaryMaterials;

    foreach($glossaryMaterials as $material) {
      $termsCatalog = $this->mainCatalog->getById($material['catalog']['id'])->findChildByAlias('terms');
      $this->termsCatalog = $termsCatalog;
      $alias = $this->toAlias($term['term']);
      if(count($aliasUpdate)) {
        $termsCatalog->getMaterialByAlias($this->toAlias($aliasUpdate[0]['term']))->delete();
      }
      $formatedTerm = $this->toData($term); 
      $this->createTermPage($termsCatalog, $formatedTerm);
    }
    return true;
  }

  public function deleteTerm($id) {
    $term = $this->getTermById($id);
    $alias = $this->toAlias($term['term']);
    $glossaryMaterials = $this->glossaryMaterials;

    foreach($glossaryMaterials as $material) {
      $termsCatalog = $this->mainCatalog->getById($material['catalog']['id'])->findChildByAlias('terms');
      $termsCatalog->getMaterialByAlias($alias)->delete();
    }

    return true;
  }

  protected function getTermById($id) {
    $dataFromDB = $this->getData(0);
    $term = array_filter($dataFromDB, function($data) use($id) {
      return (int)$data['id'] === $id;
    });
    return array_values($term)[0];
  }

  public function reloadGlossary() {
    $glossaryMaterials = $this->glossaryMaterials;

    if(!count($glossaryMaterials)) {
      return;
    }

    $catalogsIds = $this->mainCatalog->getSubs();
    $updateGlossaryMaterials = $glossaryMaterials;
    $this->initLinks();

    foreach($glossaryMaterials as $material) {

      $catalogId = $material['catalog']['id'];
      $materialId = $material['material']['id'];

      $isCatalogStillExist = count(array_filter($catalogsIds, function($id) use ($catalogId) {
        return (int)$id === (int)$catalogId;
      }));
      if(!$isCatalogStillExist) {
        $updateGlossaryMaterials = array_values(array_filter($updateGlossaryMaterials, function($mat) use($materialId) {
          return (int)$mat['material']['id'] !== (int)$materialId;
        }));
        continue;
      }

      $isMaterialStillExist = count($this->mainCatalog->getById($catalogId)->getMaterials()->where("id='$materialId'"));
      if(!$isMaterialStillExist) {
        $updateGlossaryMaterials =  array_values(array_filter($updateGlossaryMaterials, function($mat) use($materialId) {
          return (int)$mat['material']['id'] !== (int)$materialId;
        }));
        $this->deleteTermCatalogByParentId($catalogId);
        continue;
      }

      $isMaterialStillHaveGlossaryWidget = 
      mb_strpos($this->mainCatalog->getMaterialByID($materialId)['text'], '<cms action="widget" class="widget-Glossary" widgetname="Glossary"') !== false;
      if(!$isMaterialStillHaveGlossaryWidget) {
        $updateGlossaryMaterials =  array_values(array_filter($updateGlossaryMaterials, function($mat) use($materialId) {
          return (int)$mat['material']['id'] !== (int)$materialId;
        }));
        $this->deleteTermCatalogByParentId($catalogId);
        continue;
      }

      $this->termsCatalog = $this->mainCatalog->getById($catalogId)->findChildByAlias('terms');
      $this->initTermsPages($this->termsCatalog);
    }

    if(count($updateGlossaryMaterials) !== count($glossaryMaterials)) {
      $this->toFile('glossary_materials', $updateGlossaryMaterials, true);
    }
  }

  protected function deleteTermCatalogByParentId($catalogId) {
    $termsCatalog = $this->mainCatalog->getById($catalogId)->findChildByAlias('terms');
    $isTermCatalogStillExist = !empty($termsCatalog);
    if($isTermCatalogStillExist) {
      $termsCatalog->delete();
    }
  }

  public function createDataForJS($data) {
    $glossaryMaterials = $this->glossaryMaterials;
    $isGlossaryPageExist = count($glossaryMaterials) !== 0;

    if($isGlossaryPageExist) {
      $glossaryMainPageCatalogUrl = $this->mainCatalog->getById($glossaryMaterials[0]['catalog']['id'])->getUrl();
      $termsCatalog = $glossaryMainPageCatalogUrl . 'terms/';
      $newData = array_map(function($term) use($termsCatalog) {
        return [$term[0], $term[1], $termsCatalog . $this->toAlias($term[0])];
      }, $data);
    } else {
      $newData = array_map(function($term) {
        return [$term[0], $term[1]];
      }, $data);
    }

    return json_encode($newData);
  }

  public function getTermWidget($term) {
    $a = \Cetera\Application::getInstance();
    $html = $a->getWidget('Term', array(
      'description' => $term[1],
      'synonyms'    => $this->createSynonymsData($term[2]),
      'links'       => $this->createLinksData($term[3])
      ))->getHtml();
    return $html;
  }

  protected function toAlias($name) {
    $alias = implode('-', mb_split(' ', mb_strtolower($name)));
    if(preg_match('/[а-яё]/u', $alias)) {
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
      $translitAlias = 'ru-' . strtr($alias, $ruAlphabet);
      return $translitAlias;
    } 
    return $alias;
  }

  protected function initLinks() {
    $data = $this->data;
    $newData = [];
    foreach($data as $term) {
      $term[3] = $this->findTermReference($term);
      $newData[] = $term;
    }
    $this->data = $newData;
  }

  //Создаёт материалы полученных из бд терминов и наполняет их контентом
  protected function initTermsPages($termsCatalog) {
    $data = $this->data;

    foreach($data as $termData) {
      $alias = $this->toAlias($termData[0]);
      //$hasMaterial = count($termsCatalog->getMaterials()->where("alias='$alias'"));
      //if(!$hasMaterial) {
        $this->createTermPage($termsCatalog, $termData);
      //}
    }
  }

  //Создаёт материал термина и наполняет контентом, по переданным данным
  protected function createTermPage($termsCatalog, $termData) {
    $alias = $this->toAlias($termData[0]);
    $content = $this->getTermWidget($termData);
    $this->createGlossaryMaterial($termsCatalog, $alias, $termData[0], $content);
  }

  //Создаёт материал в разделе
  protected function createGlossaryMaterial($catalog, $alias, $name, $content) {
    $hasMaterial = count($catalog->getMaterials()->where("alias='$alias'"));
    
    if(!$hasMaterial) {
      $page = $catalog->createMaterial();
      $page->setFields([
        'alias'   => $alias,
        'publish' => true,
        'autor'   => 'admin',
        'text'    => $content,
        'name'    => $name
      ]);
      $page->save();
    } else {
      $material = $catalog->getMaterialByAlias($alias);
      $this->updatePageContent($material, $name, $content);
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

  //Получает данные из бд (и приводит к необходимому виду)
  protected function getData($dataType = 1) {
    $dataFromDB = include __DIR__ . '/../../g_data.php';

    if($dataType === 0)
      return $dataFromDB;

    $data = array_reduce($dataFromDB, function($dataArray, $term) {
      $dataArray[] = $this->toData($term);
      return $dataArray;
    }, []);
    return $data;
  }

  public function toData($data) {
    return [
      $data['term'], 
      $data['specification'], 
      $data['synonyms'], 
      $data['links']
    ];
  }

  
  public function toFile($fileName, $data, $var) {
    if($var) {
      file_put_contents(dirname(__FILE__) . "/../../$fileName.php","<?php\nreturn " . var_export($data, true) . ";");
    } else {
      file_put_contents(dirname(__FILE__) . "/../../$fileName.php","<?php\nreturn " . $data . ";");
    } 
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

  //Получает структуру главной страницы глоссария в виде массива
  public function createTemplateGlossaryData($glossaryCatalog) {
    $data = $this->data;
    $alphabet = $this->getAlphabet();
    $path = $glossaryCatalog->getUrl() . 'terms/';

    $dataStruct = array_reduce($alphabet, 
      function($struct, $char) use ($data, $path) {
        $item = [];
        $item['char'] = $char;
        $item['data'] = array_reduce($data, 
          function($newData, $term) use($char, $path) {
            if(mb_strtoupper(mb_substr($term[0], 0, 1)) === mb_strtoupper($char)) {
              $newData[] = [
                'term'  => $term[0], 
                'path' => $path . $this->toAlias($term[0])
              ];
            }  
            return $newData;
          }, []);
        $struct[] = $item;
        return $struct;
    }, []);
    return $dataStruct;
  }

  protected function createSynonymsData($synonyms) {
    if(strlen($synonyms) === 0) {
      return [];
    }
    $synonymsArray = mb_split(", ?", $synonyms);
    $synonymsData = array_reduce($synonymsArray, function($data, $synonym) {
      $dataSynonym = $this->getSynonymData($synonym);
      $dataSynonym['separator'] = count($data) === 0 ? '' : ', ';
      $data[] = $dataSynonym;
      return $data;      
    }, []);

    return $synonymsData;
  }

  protected function getSynonymData($synonym) {
    $alias = $this->toAlias($synonym);
    $isHavePage = count(array_filter($this->data, 
      function($term) use ($alias) {
        return $this->toAlias($term[0]) === $alias;
      }));

    $path = $this->termsCatalog->getUrl() . $alias;
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

    $catalogs = $this->mainCatalog->getSubs();
    $data = array_reduce($catalogs, function($links, $id) use($term) { 
      $catalog = $this->mainCatalog->getById($id);
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