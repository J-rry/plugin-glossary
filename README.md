
# Модуль «Глоссарий»
Добавляет в панель управления cms раздел «Глоссарий», в котором можно создавать, удалять и редактировать различные термины. Для термина можно указать обозначение и добавить синонимы, если таковые присутствуют. Объявленный термин добавляется в раздел Глоссарий, адрес которого указывается при установке, в разделе создаётся карточка термина, на которой размещается указанная информация о термине и список страниц сайта, на которых упоминается термин. Также, объявленные термины в тексте материалов выделяются в ссылки на свои страницы в глоссарии. Раздел «Глоссарий» можно не создавать, оставив путь пустым, в таком случае термины в тексте материалов будут выделяться в <abbr>, атрибутом title будет заполнен определением термина.

# Уточнения
1. "Термин" и "Определение" - обязательные для заполнения поля.
2. Если синонимы не заданы - это поле не выводится на странице термина.
3. Если в синонимах указывается термин, описаниее которого уже содержится в глоссарии, он оборачивается в ссылку на страницу данного термина.
4. Ссылки на страницы, содержащие термины генерируются автоматически при открытии страницы термина.
5. Если упоминаний на других страницах не найдено, поле с ссылками также опускается.
6. Поиск упоминаний осуществляется ТОЛЬКО в тексте материалов, созданных через интерфейс Cetera CMS, преобразование терминов в ссылки также происходит только в материалах, созданных через cms.
7. Если на странице найдено упоминание, обёрнутое в ссылку, страница будет указана в ссылках, но на самой странице термин не будет обёрнут в ссылку на страницу из глоссария, однако, если после обёрнутого в ссылку термина существует повторное упоминание, не обёрнутое в ссылку - именно оно обернётся в ссылку на страница глоссария.
8. Корректно работает только с терминами на русском и английском языках.

# Установка
1. composer require dany/plugin-glossary
2. Добавляем следующий код в файл bootstrap.php в папку с темой сайта (обычно www/themes/активная тема/bootstrap.php):

$glossaryPath = \Glossary\Options::getPath();
\Glossary\PageHandler::init($glossaryPath);
if(!!strlen($glossaryPath)) {
	\Glossary\WidgetGlossary::initPage($glossaryPath);
	\Glossary\WidgetTerm::initPage($glossaryPath);
}

3. При установке в корне плагина должен автоматически создаться файл glossary_config.php, если файл не создался автоматически, создаём его вручную (<?php return *содержимое переменной $config* из файла install.php)
4. Указываем в glossary_config.php ссылку по которой хотите расположить глоссарий в параметре GLOSSARY_PATH
5. Меняем шаблоны страниц глоссария и терминов в папке widgets, если нужно
6. Заходим в cms
7. Проверка и ремонт БД->Анализировать->Исправить обнаруженные ошибки
8. Обновляем страницу cms
9. Готово


# Инструкция по работе
После установки модуля в интерфейсе Cetera CMS появляется пункт меню «Глоссарий», при переходе в него, открывается панель для создания и редактирования терминов. Панель содержит 3 кнопки управления и список терминов.

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

При нажатии кнопки "Новый термин" вызывается попап для заполнения основной информации (термин, определение, синонимы). Первые 2 заполняются просто текстом, синонимы перечисляются через запятую ("," либо ", ", значения не имеет).

При нажатии на "Редактировать" открывается попап с теми же данными термина, в котором можно осуществить их редактирование.

При нажатии на "Удалить" термин удаляется из глоссария.