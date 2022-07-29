<?php

//Добавляем в bootstrap.php активной темы сайта

$glossaryPath = \Glossary\Options::getPath();

\Glossary\PageHandler::init($glossaryPath);
if(!!strlen($glossaryPath)) {
	\Glossary\WidgetGlossary::initPage($glossaryPath);
	\Glossary\WidgetTerm::initPage($glossaryPath);
}