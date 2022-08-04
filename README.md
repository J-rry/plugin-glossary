
# Модуль «Глоссарий»
Добавляет в панель управления cms раздел «Глоссарий», в котором можно создавать, удалять и редактировать различные термины. Для термина можно указать обозначение и добавить синонимы, если таковые присутствуют. Объявленный термин добавляется в раздел Глоссарий, в разделе создаётся карточка термина, на которой размещается указанная информация о термине и список страниц сайта, на которых упоминается термин. Также, объявленные термины в тексте на страницах сайта выделяются в ссылки на соответствующие страницы в глоссарии. Раздел «Глоссарий» можно не создавать, оставив путь пустым, в таком случае термины в тексте материалов будут выделяться в abbr, атрибутом title будет заполнен определением термина.

# Уточнения
1. "Термин" и "Определение" - обязательные для заполнения поля.
2. Если синонимы не заданы - это поле не выводится на странице термина.
3. Поиск терминов и их синонимов на странице производится в том порядке, в котором они указаны в панели управления, находится и выделяется первое совпадение, остальное опускается.
4. Не стоит задавать разным терминам одни и те же синонимы, либо заводить термины с таким же именем, как и один из синонимов другого термина, в таком случае модуль не перестанет работать, но выделение терминов на страниице может стать не логичным.
5. Ссылки на страницы, содержащие термины генерируются автоматически при открытии страницы термина.
6. Если упоминаний на других страницах не найдено, поле с ссылками также опускается.
7. Поиск упоминаний осуществляется ТОЛЬКО в тексте материалов, созданных через интерфейс Cetera CMS, преобразование терминов в ссылки происходит на всей странице.
8. Если на странице найдено упоминание, обёрнутое в ссылку, страница будет указана в ссылках, но на самой странице термин не будет обёрнут в ссылку на страницу из глоссария, однако, если после обёрнутого в ссылку термина существует повторное упоминание, не обёрнутое в ссылку - именно оно обернётся в ссылку на страница глоссария.
9. Корректно работает только с терминами на русском и английском языках.

# Установка
1. composer require dany/plugin-glossary
2. Добавляем следующий код в файл bootstrap.php в папку с темой сайта (обычно www/themes/активная_тема/bootstrap.php):

$glossaryPath = \Glossary\Options::getPath();
\Glossary\PageHandler::init($glossaryPath);
if(!!strlen($glossaryPath)) {
	\Glossary\WidgetGlossary::initPage($glossaryPath);
	\Glossary\WidgetTerm::initPage($glossaryPath);
}

3. Меняем шаблоны страниц глоссария и терминов в папке widgets, если нужно
4. Заходим в cms
5. Проверка и ремонт БД->Анализировать->Исправить обнаруженные ошибки
6. Обновляем страницу cms
7. Заходим на вкладку модуля и в настройках заполняем необходимые поля
8. Готово


# Инструкция по работе
После установки модуля в интерфейсе Cetera CMS появляется пункт меню «Глоссарий», при переходе в него, открывается панель для создания и редактирования терминов. Панель содержит 4 кнопки управления и список терминов.

Описание терминов в списке содержат следующие поля:
1. Термин (имя термина)
2. Определение
3. Синонимы

Термин - слово или словосочетание. 
Определение - описание термина, представляет собой текст, заключающий в себе семантику указанного термина. 
Синонимы - термины, похожие по смыслу на указанный термин.

Также панель содержит 3 кнопки управления:
1. Новый термин
2. Редактировать термин
3. Удалить термин
4. Настройки

При нажатии кнопки "Новый термин" вызывается попап для заполнения основной информации (термин, определение, синонимы). Первые 2 заполняются просто текстом, синонимы перечисляются через запятую ("," либо ", ", значения не имеет).

При нажатии на "Редактировать" открывается попап с теми же данными термина, в котором можно осуществить их редактирование.

При нажатии на "Удалить" термин удаляется из глоссария.

При нажатии на "Настройки" открывается попап с основными настройками модуля:

Первое поле - адрес глоссария, если поле не заполнено, страницы глоссария на сайте не будет и все добавленные термины на страницах сайта будут выделяться в тег abbr. Заполняется в виде адреса от корня сайта, например, если указать в этом поле /glossary/, то страница с глоссарием создастся по адресу адрес_сайта/glossary/, а страницы терминов будут создаваться по адресу адрес_сайта/glossary/имя_термина.

Следующие 3 поля отвечают за мета-теги страницы «Глоссарий», здесь можно указать Meta title, description и keywords для страницы.

Последние 3 поля представляют собой маски для мета-тегов страниц терминов, в них можно задать маски для Meta title, description и keywords страниц терминов, используя специальную переменную {=term}, которая при генерации страницы заменится на имя термина.
