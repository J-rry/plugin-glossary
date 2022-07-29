<?php
include_once('common_bo.php');
if (!$application->getUser()->isAdmin()) die('access denied');

if (!isset($_REQUEST['sort'])) $_REQUEST['sort'] = 'id';
if (!isset($_REQUEST['dir'])) $_REQUEST['dir'] = 'ASC';

$dataFromDB = $application->getConn()->executeQuery('SELECT id, term, specification, synonyms FROM glossary_data ORDER BY '.$_REQUEST['sort'].' '.$_REQUEST['dir']);

$data = [];
while ($term = $dataFromDB->fetch()) {
  $data[] = $term;
}

echo json_encode(array(
  'success' => true,
  'rows'    => $data
));