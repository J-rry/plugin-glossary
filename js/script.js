document.addEventListener('DOMContentLoaded', function () {

  const pathname = document.location.pathname;
  const isGlossarySection = pathname.split('/glossary').length > 1;
  if(!isGlossarySection) {
    const widget = document.querySelector(".x-cetera-widget");
    const dataGetType = 'parse';
    let glossaryData = "/glossary/cms/plugins/glossary/g_data.json";

    if(dataGetType === 'parse') {
      glossaryData = "/glossary/";
    }

    fetch(glossaryData)
      .then(response => response.text())
      .then(data => glossaryInit(data))
      .catch((err) => console.log(err));

    //Класс Термина
    class Term {
      constructor(term, specification, synonyms = '', links = '', alias = '', finded = false) {
        this.term = term;
        this.specification = specification;
        this.synonyms = synonyms;
        this.links = links;
        this.alias = alias;
        this.finded = finded;
      }
      getAlias() {
        return this.alias;
      }
      //Метод добавления синонимов
      addSynonym(...synonym) {
        this.synonyms = [...this.synonyms, ...synonym];
      }
      //Метод добавления ссылки
      addLink(...link) {
        this.links = [...this.links, ...link];
      }
      //Получить имя термина
      getTerm() {
        return this.term;
      }
      //Получить описание термина
      getSpecification() {
        return this.specification;
      }
      //Получить синонимы термина
      getSynonyms() {
        return this.synonyms;
      }
      //Получить ссылки на термин
      getLinks() {
        return this.links;
      }

      isFinded() {
        return this.finded;
      }
      termFinded() {
        this.finded = true;
      }
    }

    //Класс глоссария
    class Glossary {
      constructor(glossary = []) {
        this.glossary = glossary;
      }
      //Добавление нового термина(можно сразу несколько)
      addTerm(...term) {
        this.glossary = [...this.glossary, ...term];
      }
      //Получить Список терминов
      list() {
        return this.glossary;
      }
      //Получить термин по id в глассарии
      getElementById(id) {
        return this.glossary[id] || null;
      }
      //Получить термин по его имени
      getElementByTerm(term) {
        return this.glossary.find((element) => element.getTerm().toLowerCase() === term.toLowerCase()) || null;
      }
      getTermsList() {
        return this.glossary.map(data => data.getTerm());
      }
      otherTermsContainsTheTerm(term2) {
        return this.getTermsList().filter(term1 => {
          if(term1 === term2) {
            return false;
          }
          const regTerm = regExpForWord(term2);
          if(term1.search(regTerm) !== -1) {
            return true;
          } else {
            return false;
          }
          
        });
      }
      //Добавить термину html-обёртку(в type указывается тип обёртки)
      setTermWrappByType(term, number = 1, type = 2) {
        console.log(term);
        const alias = this.getElementByTerm(term).getAlias();
        const specification = this.getElementByTerm(term).getSpecification();
        const link = `/glossary/${alias}`;

        switch (type) {
          case 1:
            return `<abbr title="${specification}">${term}</abbr>`;
          case 2:
            return `<a href='${link}' title='${specification}'>${term}</a>`;
          case 3:
            return `<abbr title="${specification}">${term}</abbr><sup>[<a href="${link}" title="${term}">${number}</a>]</sup>`;
        }
      }
    }

    function regExpForWord(word) {
      return new RegExp("((?<=[^A-Za-zа-яА-ЯЁё\.-]+)" + word + "(?=$)|(?<=^)" + word + "(?=[^A-Za-zа-яА-ЯЁё-]+)|(?<=[^A-Za-zа-яА-ЯЁё\.-]+)" + word + "(?=[^A-Za-zа-яА-ЯЁё-]+))", "ui");
    }

    function repeatStr(str, count) {
      let result = '';
      for(let i = 0; i < count; i++) {
        result += str;
      }
      return result;
    }

    function glossaryInit (data) {
      //Создаём новый глоссарий
      const glossary = new Glossary();

      if(dataGetType === 'parse') {
        const parseData = data.match(/(?<=data-glossary=)'.*'/)[0];
        data = JSON.parse(parseData.slice(1, -1));
      }

      //Наполняем глоссарий
      data.forEach(term => {
        glossary.addTerm(new Term(term[0], term[1], term[2], term[3], term[4]));
      });

      console.log(glossary.list());
      const wrapAllTermsOnPage = () => {

        let isAtLeastOneTermFinded = false;
        // let termsOnPageCount = 0;
        //Массив, в котором будут содержаться все найденные текстовые ноды, в которых были найдены термины.
        //А также ндополнительная информация для последующего "оборачивания"
        const content = [];
        //Уровень углублённости ноды
        let floor = 0;

        //Основная функция, реализующая поиск текстовых нод и терминов внутри
        const findTextNode = (elem, i = 0) => {
          elem.childNodes.forEach((node) => {
            if (node.children) {
              i++;
              floor = i;
              findTextNode(node, i);
              i--;
            } else {
              //Проверяем, текстовая ли нода
              //Проверяем, является ли нода текстовым содержимым, чтобы отсечь элементы разметки(табуляцию и т п)
              if (node.nodeName === "#text" && node.textContent.trim().length > 0) {
                //Проверяем, не является ли родительская нода ссылкой
                if(node.parentNode.nodeName !== "A") {
                  let newNode = node.textContent;
                  let cutNode = newNode;
                  glossary.getTermsList().forEach(term => {
                    const regTerm = regExpForWord(term);
                    const index = cutNode.search(regTerm);
                    if(index === -1){
                      return;
                    }
                    if(!glossary.getElementByTerm(term).isFinded()) {
                      const otherTermsContainsTerm = glossary.otherTermsContainsTheTerm(term);
                      let wrappedTerm;
                      if(otherTermsContainsTerm.length === 0) {
                        glossary.getElementByTerm(term).termFinded();
                        wrappedTerm = glossary.setTermWrappByType(newNode.slice(index, index + term.length));
                        newNode = newNode.slice(0, index) + wrappedTerm + newNode.slice(index + term.length, newNode.length);
                      } else {
                        let subNode = newNode;
                        otherTermsContainsTerm.forEach(containsTerm => {
                          subNode = subNode.split(containsTerm);
                          subNode = subNode.join(repeatStr('|', containsTerm.length));
                        });    
                        
                        const newIndex = subNode.search(regExpForWord(term))
                        if(newIndex !== -1) {
                          glossary.getElementByTerm(term).termFinded();                                                  
                          wrappedTerm = glossary.setTermWrappByType(newNode.slice(newIndex, newIndex + term.length));
                          newNode = newNode.slice(0, newIndex) + wrappedTerm + newNode.slice(newIndex + term.length, newNode.length); 
                        } else {  
                          return; 
                        }
                      }
                      cutNode = newNode.split(wrappedTerm);
                      cutNode = cutNode.join(repeatStr('|', wrappedTerm.length));
                    }
                  });
                  if(newNode.length !== node.textContent.length) {
                    isAtLeastOneTermFinded = true;    
                    content.push({
                      floor: floor, //уровень углубления ноды
                      node: node.parentNode, //Родительская нода
                      old: node.textContent, //Старое содержимое ноды
                      new: newNode, //Новое содержимое ноды
                    });
                  }
                }
              }
            }
          });
        };

        findTextNode(widget);

        if (isAtLeastOneTermFinded) {
          //Сортируем замены, от самых глубоких
          content.sort((el1, el2) => el2.floor - el1.floor);

          //console.log(content);

          //Производим замену старого содержимого нод, на новое
          content.forEach((text) => {
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
  }
});
