<?php
include_once('common_bo.php');
if (!$application->getUser()->isAdmin()) die('access denied');

$data = array();

if (!isset($_REQUEST['sort'])) $_REQUEST['sort'] = 'id';
if (!isset($_REQUEST['dir'])) $_REQUEST['dir'] = 'ASC';

$r = $application->getConn()->executeQuery('SELECT id, term, specification, synonyms, links FROM g_list_plugin ORDER BY '.$_REQUEST['sort'].' '.$_REQUEST['dir']);
while ( $f = $r->fetch() ) $data[] = $f;

file_put_contents(dirname(__FILE__).'/g_list_urls.php',"<?php\nreturn " . var_export($data, true) . ";");

echo json_encode(array(
    'success' => true,
    'rows'    => $data
));