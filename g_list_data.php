<?php
include_once('common_bo.php');
if (!$application->getUser()->isAdmin()) die('access denied');

$data = array();
$formatedData = array();

if (!isset($_REQUEST['sort'])) $_REQUEST['sort'] = 'id';
if (!isset($_REQUEST['dir'])) $_REQUEST['dir'] = 'ASC';

$r = $application->getConn()->executeQuery('SELECT id, term, specification, synonyms FROM glossary_data ORDER BY '.$_REQUEST['sort'].' '.$_REQUEST['dir']);
while ( $f = $r->fetch() ) {
    $data[] = $f;
    $formatedData[] = \Glossary\Glossary::toFormatedData($f);
}

file_put_contents(dirname(__FILE__).'/g_data.php',"<?php\nreturn " . var_export($formatedData, true) . ";");
file_put_contents(DOCROOT . '/cms/plugins/glossary/g_data.json', \Glossary\Glossary::createDataForJS($data));

echo json_encode(array(
    'success' => true,
    'rows'    => $data
));