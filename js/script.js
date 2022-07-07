document.addEventListener('DOMContentLoaded', function () {
  const material = document.querySelector(".x-cetera-widget");
  if(material === null) {
    return;
  } 
  
  //Полифилы
  addPolyfills();

  let glossaryDataURL = "/cms/plugins/glossary/g_data.json";

  var xhr = new XMLHttpRequest();

  xhr.open('GET', glossaryDataURL);
  xhr.onload = function() {
    glossaryInit(JSON.parse(xhr.response));
  }
  xhr.send();

  function Term (term, specification, link) {
    this.term = term;
    this.specification = specification;
    this.link = link;
    this.isFinded = false;
    this.containsTerms = [];

    this.finded = function () {
      this.isFinded = true;
    }
  }

  //конструктор глоссария
  function Glossary () {
    this.glossary = [];

    //Добавление нового термина
    this.addTerm = function(term) {
      this.glossary = this.glossary.concat(term);
    }
    //Получить термин по его имени
    this.getTermByName = function(name) {
      return this.glossary.find(function(element) {
        return element.term.toLowerCase() === name.toLowerCase()
      }) || null;
    }
    //Получить список терминов
    this.getTermsList = function() {
      return this.glossary.map(function(data) {
        return data.term
      });
    }
    //Определить массив с терминами, которые содержат в себе имя термина каждому термину
    this.otherTermsContainsTerm = function() {
      var that = this;
      this.glossary.forEach(function(termData) {
        const containsTerms = that.getTermsList().filter(function(term) {
          if(term === termData.term) {
            return false;
          }
          const regTerm = regExpForWord(termData.term);
          return term.search(regTerm) !== -1; 
        });
        termData.containsTerms = containsTerms;
      });
    }
    //Добавить термину html-обёртку
    this.setTermWrappByType = function(term) {
      const specification = this.getTermByName(term).specification;
      const link = this.getTermByName(term).link;
      if(link === null) {
        return "<abbr title='" + specification + "'>" + term + "</abbr>";
      } else {
        return "<a href='" + link + "' title='" + specification + "'>" + term + "</a>";
      }
    }
  }

  function regExpForWord(word) {
    return new RegExp('([^A-Za-zа-яА-ЯЁё\.-]' + word + '$|^' + word + '[^A-Za-zа-яА-ЯЁё\.-]|[^A-Za-zа-яА-ЯЁё\.-]'+ word + '[^A-Za-zа-яА-ЯЁё-]|^' + word + '$)', "i");
  }

  function addStubs(text, item) {
    text = text.split(item);
    text = text.join('|'.repeat(item.length));
    return text;
  }

  function glossaryInit(data) {
    if(data.length === 0) {
      return;
    }

    //Отсекаем часть терминов, которых нет на странице
    const materialHTML = material.innerHTML.toLocaleLowerCase();
    const containsData = data.filter(function(termData) {
      return materialHTML.indexOf(termData[0].toLocaleLowerCase()) !== -1;
    })

    //Создаём новый глоссарий
    const glossary = new Glossary();

    //Наполняем глоссарий
    const isGlossaryPageExist = data[0].length === 3;
    if(isGlossaryPageExist) {
      containsData.forEach(function(term) {
        glossary.addTerm(new Term(term[0], term[1], term[2]));
      });
    } else {
      containsData.forEach(function(term) {
        glossary.addTerm(new Term(term[0], term[1], null));
      });
    }
    glossary.otherTermsContainsTerm();

    function wrapAllTermsOnPage() {
      //Массив, в котором будут содержаться все найденные текстовые ноды, в которых были найдены термины.
      //А также ндополнительная информация для последующего "оборачивания"
      const content = [];
      var isAtLeastOneTermFinded = false;

      //Основная функция, реализующая поиск текстовых нод и терминов внутри
      function findTextNode(elem, floor) {
        elem.childNodes.forEach(function(node) {
          if (node.children) {
            floor++;
            findTextNode(node, floor);
            floor--;
            return;
          }
          //Проверяем, текстовая ли нода
          //Проверяем, является ли нода текстовым содержимым, чтобы отсечь элементы разметки(табуляцию и т п)
          if (node.nodeName === "#text" && node.textContent.trim().length > 0) {
            //Проверяем, не является ли родительская нода ссылкой
            if(node.parentNode.nodeName !== "A")  {

              let newNode = node.textContent;
              let cutNode = newNode;

              glossary.getTermsList().forEach(function(term) {
                
                const regTerm = regExpForWord(term);
                let index = cutNode.search(regTerm);

                if(index === -1){
                  return;
                }
                if(index !== 0) {
                  index++;
                }
                
                if(!glossary.getTermByName(term).isFinded) {

                  const otherTermsContainsTerm = glossary.getTermByName(term).containsTerms;
                  let wrappedTerm;

                  if(otherTermsContainsTerm.length === 0) {
                    glossary.getTermByName(term).finded();
                    wrappedTerm = glossary.setTermWrappByType(newNode.slice(index, index + term.length));
                    newNode = newNode.slice(0, index) + wrappedTerm + newNode.slice(index + term.length, newNode.length);
                    cutNode = addStubs(newNode, wrappedTerm);
                    return;
                  }

                  let subNode = newNode;

                  otherTermsContainsTerm.forEach(function(containsTerm) {
                    subNode = addStubs(subNode, containsTerm);
                  });    
                  
                  let newIndex = subNode.search(regExpForWord(term))

                  if(newIndex !== -1) {
                    if(newIndex !== 0) {
                      newIndex++;
                    }
                    glossary.getTermByName(term).finded();                                                  
                    wrappedTerm = glossary.setTermWrappByType(newNode.slice(newIndex, newIndex + term.length));
                    newNode = newNode.slice(0, newIndex) + wrappedTerm + newNode.slice(newIndex + term.length, newNode.length); 
                    cutNode = addStubs(newNode, wrappedTerm);
                  }
                }
              });
              if(newNode.length !== node.textContent.length) {
                isAtLeastOneTermFinded = true;    
                content.push({
                  floor: floor,          //уровень углубления ноды
                  node: node.parentNode, //Родительская нода
                  old: node.textContent, //Старое содержимое ноды
                  new: newNode,          //Новое содержимое ноды
                });
              }
            }
          }
        });
      };

      findTextNode(material, 0);

      if (isAtLeastOneTermFinded) {
        //Сортируем замены, от самых глубоких
        content.sort(function(el1, el2) {
          return el2.floor - el1.floor
        });

        //Производим замену старого содержимого нод, на новое
        content.forEach(function(text) {
          const nodeHTML = text.node.innerHTML;
          const inHTML = text.old.split('\u00A0');
          const oldHTML = inHTML.join('&nbsp;');
          text.node.innerHTML = nodeHTML.replace(oldHTML, text.new);
        });
      }
      return isAtLeastOneTermFinded;
    };
    wrapAllTermsOnPage();
  }

  //Полифилы
  function addPolyfills() {
    if (window.NodeList && !NodeList.prototype.forEach) {
      NodeList.prototype.forEach = Array.prototype.forEach;
    }
    if (!Array.prototype.find) {
      Object.defineProperty(Array.prototype, 'find', {
        value: function(predicate) {
          if (this == null) {
            throw new TypeError('"this" is null or not defined');
          }
    
          var o = Object(this);
          var len = o.length >>> 0;
  
          if (typeof predicate !== 'function') {
            throw new TypeError('predicate must be a function');
          }
  
          var thisArg = arguments[1];
          var k = 0;
  
          while (k < len) {
            var kValue = o[k];
            if (predicate.call(thisArg, kValue, k, o)) {
              return kValue;
            }
            k++;
          }
    
          return undefined;
        },
        configurable: true,
        writable: true
      });
    }
  
    if (!String.prototype.repeat) {
      String.prototype.repeat = function(count) {
        'use strict';
        if (this == null)
          throw new TypeError('can\'t convert ' + this + ' to object');
    
        var str = '' + this;
        count = +count;
  
        if (count != count)
          count = 0;
    
        if (count < 0)
          throw new RangeError('repeat count must be non-negative');
    
        if (count == Infinity)
          throw new RangeError('repeat count must be less than infinity');
    
        count = Math.floor(count);
        if (str.length == 0 || count == 0)
          return '';
  
        if (str.length * count >= 1 << 28)
          throw new RangeError('repeat count must not overflow maximum string size');
    
        var maxCount = str.length * count;
        count = Math.floor(Math.log(count) / Math.log(2));
        while (count) {
          str += str;
          count--;
        }
        str += str.substring(0, maxCount - str.length);
        return str;
      }
    }
  }
});
