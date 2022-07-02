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
    $this->glossaryMaterials = include dirname(__FILE__) . '/../../glossaty_materials.php';
  }

  public function initGlossary($glossaryCatalog) {
    $this->glossaryCatalog = $glossaryCatalog;

    $hasNotTermsCatalog = empty($glossaryCatalog->findChildByAlias('terms'));
    if($hasNotTermsCatalog) {
      $this->initLinks();
      $glossaryCatalog->createChild([
        'name' => 'Термины',
        'alias' => 'terms',
        'typ' =>  \Cetera\ObjectDefinition::findByAlias('materials')
      ]);

      $this->termsCatalog = $this->glossaryCatalog->getChildByAlias('terms');
      $this->initTermsPages($this->termsCatalog);
    }
    $this->termsCatalog = $this->glossaryCatalog->getChildByAlias('terms');
  }

  public function addGlossaryMaterial() {
    $glossaryCatalog = $this->glossaryCatalog;

    $existGlossaryMaterials = $this->glossaryMaterials;
    $isGlossaryHaveNoMaterials = count($existGlossaryMaterials) === 0;
    if(!$isGlossaryHaveNoMaterials) {
      $glossaryCatalogId = $glossaryCatalog['id'];
      $isWidgetAlreadyExistInCatalog = count(array_filter($existGlossaryMaterials, function($widgetData) use($glossaryCatalogId) {
        return $widgetData['catalog']['id'] === $glossaryCatalogId;
      }));
      if($isWidgetAlreadyExistInCatalog) {
        return;
      }
    }
    
    $materials = $glossaryCatalog->getMaterials();
    $newData;

    for($i = 0; $i < count($materials); $i++) {
      $isHaveGlossaryWidget = mb_strpos($materials[$i]['text'], '<cms action="widget" class="widget-Glossary" widgetname="Glossary"');
      if($isHaveGlossaryWidget !== false) {
        $newData = [
          'catalog' => [
            'id' => $glossaryCatalog['id'], 
            'alias' => $glossaryCatalog['alias']
          ],
          'material' => [
            'id' => $materials[$i]['id'], 
            'alias' => $materials[$i]['alias']
          ]
        ];

        $existGlossaryMaterials[] = $newData;
        $this->toFile('glossaty_materials', $existGlossaryMaterials, true);
        return;
      }
    }
  }

  public function createNewTerm($term) {
    $term['links'] = $this->findTermReference($term);

    $data = $this->getData(0);
    $aliasUpdate = array_values(array_filter($data, function($termData) use ($term) {
      return $termData['id'] === $term['id'] && $this->toAlias($termData['term']) !== $this->toAlias($term['term']);
    }));
    
    $glossaryMaterials = $this->glossaryMaterials;

    foreach($glossaryMaterials as $material) {
      $termsCatalog = $this->mainCatalog->getById($material['catalog']['id'])->findChildByAlias('terms');
      $this->termsCatalog = $termsCatalog;
      $alias = $this->toAlias($term['term']);
      $isTermAlreadtyExists = count($termsCatalog->getMaterials()->where("alias='$alias'"));
      if(count($aliasUpdate)) {
        $termsCatalog->getMaterialByAlias($this->toAlias($aliasUpdate[0]['term']))->delete();
      }
      if($isTermAlreadtyExists) {
        $termsCatalog->getMaterialByAlias($alias)->delete();
      }
      $formatedTerm = $this->toData($term); 
      $this->createTermPage($termsCatalog, $formatedTerm);
    }
    return true;
  }

  public function deleteTerm($id) {
    $alias = $this->toAlias($this->getTermById($id)['term']);

    $glossaryMaterials = $this->glossaryMaterials;

    foreach($glossaryMaterials as $material) {
      $termsCatalog = $this->mainCatalog->getById($material['catalog']['id'])->findChildByAlias('terms');
      $termsCatalog->getMaterialByAlias($alias)->delete();
    }

    return true;
  }

  public function reloadGlossary() {
    $glossaryMaterials = $this->glossaryMaterials;
    $catalogsIds = $this->mainCatalog->getSubs();
    $updateGlossaryMaterials = $glossaryMaterials;

    foreach($glossaryMaterials as $material) {

      $catalogId = $material['catalog']['id'];
      $materialId = $material['material']['id'];

      $isCatalogStillExist = count(array_filter($catalogsIds, function($id) use ($catalogId, $test) {
        return (int)$id === (int)$catalogId;
      }));
      if(!$isCatalogStillExist) {
        $updateGlossaryMaterials =  array_filter($updateGlossaryMaterials, function($mat) use($materialId) {
          return (int)$mat['material']['id'] !== (int)$materialId;
        });
        continue;
      }

      $isMaterialStillExist = count($this->mainCatalog->getById($catalogId)->getMaterials()->where("id='$materialId'"));
      if(!$isMaterialStillExist) {
        $updateGlossaryMaterials =  array_filter($updateGlossaryMaterials, function($mat) use($materialId) {
          return (int)$mat['material']['id'] !== (int)$materialId;
        });
        $this->deleteTermCatalogByParentId($catalogId);
        continue;
      }

      $isMaterialStillHaveGlossaryWidget = 
      mb_strpos($this->mainCatalog->getMaterialByID($materialId)['text'], '<cms action="widget" class="widget-Glossary" widgetname="Glossary"') !== false;
      if(!$isMaterialStillHaveGlossaryWidget) {
        $updateGlossaryMaterials =  array_filter($updateGlossaryMaterials, function($mat) use($materialId) {
          return (int)$mat['material']['id'] !== (int)$materialId;
        });
        $this->deleteTermCatalogByParentId($catalogId);
        continue;
      }
    
      $this->deleteTermCatalogByParentId($catalogId);

      $this->termsCatalog = $termsCatalog;
      $this->initGlossary($this->mainCatalog->getById($catalogId));
    }

    if(count($updateGlossaryMaterials) !== count($glossaryMaterials)) {
      $this->toFile('glossaty_materials', $updateGlossaryMaterials, true);
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

  protected function deleteTermCatalogByParentId($catalogId) {
    $termsCatalog = $this->mainCatalog->getById($catalogId)->findChildByAlias('terms');
    $isTermCatalogStillExist = !empty($termsCatalog);
    if($isTermCatalogStillExist) {
      $termsCatalog->delete();
    }
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

  protected function getTermById($id) {
    $dataFromDB = $this->getData(0);
    $term = array_filter($dataFromDB, function($data) use($id) {
      return (int)$data['id'] === $id;
    });
    return array_values($term)[0];
  }

  protected function initLinks() {
    $data = $this->data;
    $newData = [];
    foreach($data as $term) {
      $term[3] = $this->findTermReference($term, 0);
      $newData[] = $term;
    }
    $this->data = $newData;
  }

  //Создаёт материалы полученных из бд терминов и наполняет их контентом
  protected function initTermsPages($termsCatalog) {
    $data = $this->data;

    foreach($data as $termData) {
      $alias = $this->toAlias($termData[0]);
      $hasMaterial = count($termsCatalog->getMaterials()->where("alias='$alias'"));
      if(!$hasMaterial) {
        $this->createTermPage($termsCatalog, $termData);
      }
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
  public function createTemplateGlossaryData() {
    $data = $this->data;
    $alphabet = $this->getAlphabet();
    $path = $this->termsCatalog->getUrl();

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
    if(strlen($links) === 0) {
      return [];
    }
    $linksArray = mb_split(", ?", $links);
    $linksData = array_reduce($linksArray, function($data, $link) {
      $dataLink = $this->getLinkData($link);
      $dataLink['separator'] = count($data) === 0 ? '' : ', ';
      $data[] = $dataLink;
      return $data;      
    }, []);

    return $linksData;
  }

  protected function getLinkData($link) {
    $linkData = mb_split("\|{3}", $link);
    return ['title' => $linkData[0], 'link' => $linkData[1]];
  }

  protected function findTermReference($term, $dataType = 1) {
    if($dataType === 1)
      $termData = $this->toData($term);
    else
      $termData = $term;

    //Находим id-ы материалов и каталогов терминов глоссария, чтобы отсечь их
    $glossaryMaterials = $this->glossaryMaterials;
    $termsCatalogsIds = array_reduce($glossaryMaterials, function($ids, $material) {
      $termId = $this->mainCatalog->getById($material['catalog']['id'])->findChildByAlias('terms')['id'];
      if($termId) {
        $ids[] = $termId;
      }
      return $ids;
    }, []);
    $glossaryMaterialsIds = array_map(function($material) {
      return $material['material']['id'];
    }, $glossaryMaterials);

    $catalogs = $this->mainCatalog->getSubs();
    $data = array_reduce($catalogs, function($cur, $id) use($termData, $termsCatalogsIds, $glossaryMaterialsIds) {

      $catalog = $this->mainCatalog->getById($id);
      $isCatalogHidden = $catalog->isHidden();
      $isParentHidden = $catalog->getParent()->isHidden();

      if(!$isCatalogHidden && !$isParentHidden) {
        if(!in_array((int)$catalog['id'], $termsCatalogsIds)) {
          $materials = $this->mainCatalog->getById($id)->getMaterials();

          for($i = 0; $i < count($materials); $i++) {
            if(!in_array($materials[$i]['id'], $glossaryMaterialsIds)) {
              $html = $materials[$i]['text'];
              $onlyText = mb_split("</?.*?>", $html);
              $onlyText = implode("", $onlyText);
              $regExp = '/([^a-zа-яА-ЯЁё\.-]' . $termData[0] . '$|^' . $termData[0] . '[^a-zа-яА-ЯЁё\.-]|[^a-zа-яА-ЯЁё\.-]'. $termData[0] . '[^a-zа-яА-ЯЁё-])/ui';
              $isHaveTerm = preg_match($regExp, $onlyText);

              if($isHaveTerm === 1) {
                $links[] = $materials[$i]['name'] . "|||" . $materials[$i]->getUrl();
              }
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