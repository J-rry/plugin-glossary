<?php

//Добавляем в bootstrap.php активной темы сайта

use \Laminas\Router\Http\Regex;
use \Laminas\Router\Http\Segment;

$glossaryPath = Glossary\Glossary::getGlossaryConfigData()['glossary_path'];

if(strlen($glossaryPath) !== 0) {
	$router = Cetera\Application::getInstance()->getRouter();

	$router->addRoute('glossary', Regex::factory([
			'regex' => $glossaryPath . '?',
			'defaults' => ['controller' => '\Glossary\WidgetGlossary', 'action' => 'index'],
			'spec' => $glossaryPath,
	]), 1);

	$router->addRoute('glossary_term', Segment::factory([
			'route' => $glossaryPath . ':id[/]',
			'defaults' => ['controller' => '\Glossary\WidgetTerm', 'action' => 'index'],
	]), 1);
}

$address = $_SERVER['REQUEST_URI'];
$pageMaterial = Glossary\Glossary::pageMaterial($address);

if($pageMaterial !== false && $address !== $glossaryPath) {

	Glossary\Glossary::$glossaryPath = $glossaryPath;
	
	$router = Cetera\Application::getInstance()->getRouter();

	$router->addRoute('glossary', Regex::factory([
			'regex' => $address . '?',
			'defaults' => ['controller' => '\Glossary\Glossary', 'action' => 'wrapTermsOnPage'],
			'spec' => $address,
	]), 1);
}