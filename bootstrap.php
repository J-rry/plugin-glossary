<?php

//Добавляем в bootstrap.php активной темы сайта
// /glossary/ меняем на относительную ссылку, по которой должен выводиться Глоссарий

use \Laminas\Router\Http\Regex;
use \Laminas\Router\Http\Segment;

$router = Cetera\Application::getInstance()->getRouter();

$router->addRoute('glossary', Regex::factory([
    'regex' => '/glossary/?',
    'defaults' => ['controller' => '\Glossary\WidgetGlossary', 'action' => 'index'],
    'spec' => '/glossary/',
]), 1);

$router->addRoute('glossary_term', Segment::factory([
    'route' => '/glossary/:id[/]',
    'defaults' => ['controller' => '\Glossary\WidgetTerm', 'action' => 'index'],
]), 1);