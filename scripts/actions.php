<?php
include_once('common_bo.php');

if (!$application->getUser()->isAdmin()) die('access denied');
 
$res = array(
    'success' => false
);

$action = $_POST['action'];
$id = (int)$_POST['id'];

switch ($action) {
    case 'delete_term':
        $application->getConn()->executeQuery('DELETE FROM glossary_data WHERE id=?', array($id));
        $res['success'] = true;
        break;
    case 'save_term':
        saveTerm($application, $id, $_POST['term'], $_POST['specification'], $_POST['synonyms']);
        $res['success'] = true;
        break;
    case 'get_term':
        $res['data'] = $application->getConn()->fetchAssoc('SELECT * FROM glossary_data WHERE id=?', array($id));
        $res['success'] = true; 
        break;
    default: 
        break;
}

echo json_encode($res);

function saveTerm($application, $id, $term, $specification, $synonyms) {
	$query = 'glossary_data SET term=?, specification=?, synonyms=?, alias=?';

    if($id) 
        $query = 'UPDATE ' . $query . ' WHERE id=' . $id;
    else 
        $query = 'INSERT INTO ' . $query;
    
    $application->getConn()->executeQuery($query, array($term, $specification, $synonyms, Glossary\Data::toAlias($term)));
    if (!$id) $id = $application->getConn()->lastInsertId(); 
}