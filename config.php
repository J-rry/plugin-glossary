<?php
$t = $this->getTranslator();
$t->addTranslation(__DIR__.'/lang');

$this->registerWidget(array(
    'name'    => 'Glossary',
    'class'   => '\\Glossary\\WidgetGlossary',
    'not_placeable' => true,
));

$this->registerWidget(array(
    'name'    => 'Term',
    'class'   => '\\Glossary\\WidgetTerm',
    'not_placeable' => true,
));

$url = \Glossary\Data::catalogUrl();
if (!!$url) {
    \Glossary\WidgetGlossary::initPage($url);
    if(\Glossary\Data::isMaterialExist())
	    \Glossary\WidgetTerm::initPage($url);
} else {
    \Glossary\PageHandler::init($glossaryPath);
}