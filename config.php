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
\Cetera\Application::getInstance()->addScript('/cms/plugins/glossary/js/script.js');