<?php
include_once('common_bo.php');
if (!$application->getUser()->isAdmin()) die('access denied');

$res = array(
    'success' => false
);

$action = $_POST['action'];

switch($action) {
    case 'save_options': 
        \Glossary\Options::setPath($_POST['glossary_path']);
        \Glossary\Options::setTitle($_POST['glossary_title']);
        \Glossary\Options::setDescription($_POST['glossary_description']);
        \Glossary\Options::setKeywords($_POST['glossary_keywords']);
        \Glossary\Options::setTermTitleMask($_POST['term_title_mask']);
        \Glossary\Options::setTermDescriptionMask($_POST['term_description_mask']);
        \Glossary\Options::setTermKeywordsMask($_POST['term_keywords_mask']);
        $res['success'] = true;
        break;
    case 'get_options':
        $res['success'] = true;
        $res['data'] = \Glossary\Options::getOptions();
        break;
    default:
        break;
}
echo json_encode($res);