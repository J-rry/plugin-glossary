<?php
include_once('common_bo.php');
if (!$application->getUser()->isAdmin()) die('access denied');

$data = array();
//$formattedData = array();

if (!isset($_REQUEST['sort'])) $_REQUEST['sort'] = 'id';
if (!isset($_REQUEST['dir'])) $_REQUEST['dir'] = 'ASC';

use Glossary\Glossary;

$r = $application->getConn()->executeQuery('SELECT id, term, specification, synonyms, links FROM glossary_data ORDER BY '.$_REQUEST['sort'].' '.$_REQUEST['dir']);
while ( $f = $r->fetch() ) {
    $data[] = $f;
    //$formattedData[] = $glossary->toData($f);  
}

file_put_contents(dirname(__FILE__).'/g_data.php',"<?php\nreturn " . var_export($data, true) . ";");

//file_put_contents(__DIR__.'/../../../www/cms/plugins/glossary/g_data.json', json_encode($glossary->createDataForJS($formattedData)));

$glossary = new Glossary();

echo json_encode(array(
    'success' => true,
    'rows'    => $data
));