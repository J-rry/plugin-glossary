<?php

use Glossary\Glossary;

\Cetera\Application::getInstance()->getConn()->executeQuery("CREATE TABLE IF NOT EXISTS g_list_plugin 
(id int(11) NOT NULL, 
term varchar(60), 
specification varchar(500), 
synonyms varchar(500), 
links varchar(1000))");

Glossary::inint();