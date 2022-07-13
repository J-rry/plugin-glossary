<?php

$config = [
  'GLOSSARY_PATH' => '',
  'GLOSSARY_TITLE' => 'Глоссарий сайта',
  'GLOSSARY_DESCRIPTION' => 'Глоссарий — словарь узкоспециализированных терминов в какой-либо отрасли знаний с толкованием',
  'GLOSSARY_KEYWORDS' => 'Глоссарий',
  'TERMS_TITLE_MASK' => 'Описание термина &#171;{=term}&#187;',
  'TERMS_DESCRIPTION_MASK' => 'Глоссарий. Описание термина &#171;{=term}&#187;',
  'TERMS_KEYWORDS_MASK' => 'Глоссарий, описание, термин, {=term}'
];

file_put_contents(dirname(__FILE__).'/glossary_config.php',"<?php\nreturn " . var_export($config, true) . ";");