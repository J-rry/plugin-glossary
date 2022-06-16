<?php
$t = $this->getTranslator();
$t->addTranslation(__DIR__.'/lang');

if ( $this->getBo() && $this->getUser() && $this->getUser()->isAdmin() )
{
	
    $this->getBo()->addModule(array(
        'id'       => 'glossary_plugin',
        'position' => MENU_SITE,
        'name' 	   => $t->_('Глоссарий'),
        'icon'     => '/cms/plugins/glossary/images/icon.gif',
        'iconCls'  => 'x-fa fa-directions',
        'class'    => 'Plugin.glossary.Panel'
    ));
    
}

//use Glossary\Glossary;
//Glossary::inint();

//$conn->executeQuery('DROP TABLE IF EXISTS g_list_plugin');
\Cetera\Application::getInstance()->getConn()->executeQuery("CREATE TABLE IF NOT EXISTS g_list_plugin 
(id int(11) NOT NULL, 
term varchar(60), 
specification varchar(500), 
synonyms varchar(500), 
links varchar(1000))");