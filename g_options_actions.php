<?php
include_once('common_bo.php');
if (!$application->getUser()->isAdmin()) die('access denied');

$res = array(
    'success' => false,
    'errors' => array()
);

$action = $_POST['action'];
$id = (int)$_POST['id'];

if ($action == 'save_g_options') {

    $query = 'glossary_options SET glossary_path=?, glossary_title=?, glossary_description=?, 
              glossary_keywords=?, term_title_mask=?, term_description_mask=?, term_keywords_mask=?';

    if ($id) $query = 'REPLACE INTO ' . $query . ', id=' . $id;
    else $query = 'INSERT INTO ' . $query;

    $application->getConn()->executeQuery($query, array($_POST['glossary_path'], $_POST['glossary_title'], $_POST['glossary_description'], $_POST['glossary_keywords'], 
    $_POST['term_title_mask'], $_POST['term_description_mask'], $_POST['term_keywords_mask']));
    if (!$id) $id = $application->getConn()->lastInsertId();

    $res['success'] = true;
}

if ($action == 'get_g_options') {

	$query = 'SELECT * FROM glossary_options WHERE id=?';

	$res['data'] = $application->getConn()->fetchAssoc($query, array((int)$_REQUEST['id']));
	$res['success'] = true;

}

echo json_encode($res); 