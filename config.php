<?php
$t = $this->getTranslator();
$t->addTranslation(__DIR__.'/lang');

$this->registerWidget(array(
    'name'    => 'Glossary',
    'class'   => '\\Glossary\\WidgetGlossary',
    'describ' => $t->_('Глоссарий'),
    'icon'    => '/cms/plugins/glossary/images/icon.gif',
    'ui'      => 'Plugin.glossary.Widget',
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
\Cetera\Event::attach(EVENT_CORE_MATERIAL_AFTER_SAVE, function($event, $data){	
    $glossary = new \Glossary\Glossary();
    if($glossary->isWidgetNeedInit($data['material'])) {
        $glossary->initGlossary($data['material']);
        $glossary->addGlossaryMaterial($data['material']);
    } 
});