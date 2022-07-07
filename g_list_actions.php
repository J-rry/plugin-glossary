<?php
include_once('common_bo.php');

if (!$application->getUser()->isAdmin()) die('access denied');
 
$res = array(
    'success' => false,
    'errors' => array()
);

$action = $_POST['action'];
$id = (int)$_POST['id'];

if ($action == 'delete_term') {
	
    $query_del = 'DELETE FROM glossary_data WHERE id=?';
    $application->getConn()->executeQuery($query_del, array($id));

    $res['success'] = true;
}

if ($action == 'save_term') {

	$query = 'glossary_data SET term=?, specification=?, synonyms=?, links=?';

    if($id) 
        $query = 'UPDATE ' . $query . ' WHERE id=' . $id;
    else 
        $query = 'INSERT INTO ' . $query;
    
    $application->getConn()->executeQuery($query, array($_POST['term'], $_POST['specification'], $_POST['synonyms'], ''));
    if (!$id) $id = $application->getConn()->lastInsertId();
            
    $res['success'] = true;
    
}

if ($action == 'get_g_list') {

	$query = 'SELECT * FROM glossary_data WHERE id=?';
	
	$res['data'] = $application->getConn()->fetchAssoc($query, array((int)$_REQUEST['id']));

	$res['success'] = true;  
    
}
echo json_encode($res);
