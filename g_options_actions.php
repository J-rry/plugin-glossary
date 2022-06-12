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

    $query = 'g_options_plugin SET g_wrap_type=?';

    if ($id) $query = 'REPLACE INTO ' . $query . ', id=' . $id;
    else $query = 'INSERT INTO ' . $query;

    $application->getConn()->executeQuery($query, array($_POST['g_wrap_type']));
    if (!$id) $id = $application->getConn()->lastInsertId();

    $res['success'] = true;
}

if ($action == 'get_g_options') {

	$query = 'SELECT * FROM g_options_plugin WHERE id=?';
	
	$res['data'] = $application->getConn()->fetchAssoc($query, array((int)$_REQUEST['id']));
	$res['success'] = true;

}

echo json_encode($res);