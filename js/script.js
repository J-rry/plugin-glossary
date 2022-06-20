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
      //Добавить термину html-обёртку(в type указывается тип обёртки)
      setTermWrappByType(term, number = 1, type = 2) {
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

      const wrapAllTermsOnPage = () => {

        $isAtLeastOneTermFinded = false;
        $termsOnPageCount = 0;
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
                  //Массив всех слов в текстовой ноде
                  const reg = new RegExp("[^A-Za-zа-яА-ЯЁё-]", "u");
                  const wordsInNode = node.textContent.trim().split(reg);
                  //console.log(wordsInNode);
                  //Массив найденных терминов в ноде
                  const containsTerms = wordsInNode.filter((word) => {
                    if(glossary.getElementByTerm(word)) {
                      if(!glossary.getElementByTerm(word).isFinded()) {
                        glossary.getElementByTerm(word).termFinded();
                        return true;
                      }
                    }
                    return false;
                  });
                  // console.log('Найденные');
                  // console.log(containsTerms);
                  //Проверяем, есть ли в ноде термины
                  if (containsTerms.length) {
                    let nodeNewText = node.textContent;
                    let newTextSplited = [];

                    containsTerms.forEach((term, id) => {
                      $termsOnPageCount++;
                      const wrappedTerm = glossary.setTermWrappByType(term, $termsOnPageCount);

                      $isAtLeastOneTermFinded = true;
                      //glossary.getElementByTerm(term).termFinded();

                      //Если текст содержит только один термин, находим и заменяем его на "обёрнутый"
                      if (id === 0) {
                        nodeNewText = nodeNewText.replace(term, wrappedTerm);
                        //Если терминов в ноде больше одного
                      } else {
                        //Предыдущий "обёрнутый" термин
                        let previousTerm = glossary.setTermWrappByType(
                          containsTerms[id - 1]
                        );
                        //Разбиваем ноду на 2 части, по "обёрнутому" термину
                        let [firstPart, secondPart] = nodeNewText.split(previousTerm);
                        //Оборачиваем термин во второй части
                        nodeNewText = secondPart.replace(term, wrappedTerm);
                        //Если элементов больше 2х, удаляем последний элемент массива
                        if (id > 1) newTextSplited.pop();
                        //Добавляем в массив первую часть ноды, обёрнутый термин, и новую вторую часть
                        newTextSplited = [
                          ...newTextSplited,
                          firstPart,
                          previousTerm,
                          nodeNewText,
                        ];
                      }
                    });

                    nodeNewText =
                      containsTerms.length > 1 ? newTextSplited.join("") : nodeNewText;

                    //Добавляем в массив данные для последующей замены
                    content.push({
                      floor: floor, //уровень углубления ноды
                      node: node.parentNode, //Родительская нода
                      old: node.textContent, //Старое содержимое ноды
                      new: nodeNewText, //Новое содержимое ноды
                    });
                  }
                }
              }
            }
          });
        };

        findTextNode(widget);

        if ($isAtLeastOneTermFinded) {
          //Сортируем замены, от самых глубоких
          content.sort((el1, el2) => el2.floor - el1.floor);

          console.log(content);

          //Производим замену старого содержимого нод, на новое
          content.forEach((text) => {
            const nodeHTML = text.node.innerHTML;
            const inHTML = text.old.split('\u00A0');
            const oldHTML = inHTML.join('&nbsp;');
            text.node.innerHTML = nodeHTML.replace(oldHTML, text.new);
          });
        }

        return $isAtLeastOneTermFinded;
      };

      wrapAllTermsOnPage();
      
    }
  }
});
