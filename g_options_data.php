<?php
include_once('common_bo.php');
if (!$application->getUser()->isAdmin()) die('access denied');

$data = array();

$r = $application->getConn()->executeQuery('SELECT id, glossary_path, glossary_title, glossary_description, glossary_keywords, term_title_mask, 
  term_description_mask, term_keywords_mask FROM glossary_options');
$data[] = $r->fetch();

echo json_encode(array(
    'success' => true,
    'rows' => $data
));