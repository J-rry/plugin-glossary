# Модуль «Глоссарий»
Позволяет создать на сайте раздел «Глоссарий». Главная страница глоссария содержит набор ссылок на занесенные через интерфейс Cetera CMS термины. Страницы терминов содержат имя термина, определение, синонимы и ссылки на страницы сайта, на которых встречаются его упоминания. На страниах, на которых встречается термин, первое его упоминание оборачивается в ссылку на определение из глоссария.

# Уточнения
1. "Термин" и "Определение" - обязательные для заполнения поля.
2. Если синонимы не заданы - это поле не выводится на странице термина.
3. Если в синонимах указывается термин, описаниее которого уже содерживается в глоссарии, он оборачивается в ссылку на страницу данного термина.
4. Ссылки на страницы, содержащие термины генерируются автоматически при добавлении термина.
5. Если упоминаний на других страницах не найдено, поле с ссылками также опускается.
6. Поиск упоминаний осуществляется ТОЛЬКО в тексте материалов, созданных через интерфейс Cetera CMS, преобразование терминов в ссылки также происходит только в материалах, созданных через cms.
7. Если на странице найдено упоминание, обёрнутое в ссылку, страница будет указана в ссылках, но на самой странице термин не будет обёрнут в ссылку на страницу из глоссария, однако, если после обёрнутого в ссылку термина существует повторное упоминание, не обёрнутое в ссылку - оно обернётся в ссылку на страница глоссария.

# Установка
composer require  dany/plugin-glossary
Заходим в cms, Проверка и ремонт БД->Анализировать->Исправить обнаруженные ошибки
Обновляем страницу cms
Готово

# Инструкция по работе
После установки модуля в интерфейсе Cetera CMS появляется пункт меню «Глоссарий», при переходе в этот пункт, открывается панель для создания и редактирования терминов. Пануль содержит 4 кнопки управления и список терминов.

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
4. Обновить Глоссарий

При нажатии кнопки "Новый термин" попап для заполнения основной информации (термин, определение, синонимы). Первые 2 заполняются просто текстом, синонимы перечисляются через запятую ("," либо ". ", значения не имеет).

При нажатии на "Редактировать" открывается попап с теми же данными термина, в которой можно осуществить их редактирование.

При нажатии на "Удалить" термин удаляется из глоссария.

Кнопка "Обновить Глоссарий" позволяет обновить ссылки на страницах терминов. Необходима, если за время существования терминов в системе были созданы новые материалы, на которых могут пресутствовать упоминания термина.


