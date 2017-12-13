<?php

/* ======================================================================== \
  |   FORMA - The E-Learning Suite                                            |
  |                                                                           |
  |   Copyright (c) 2013 (Forma)                                              |
  |   http://www.formalms.org                                                 |
  |   License  http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt           |
  |                                                                           |
  |   from docebo 4.0.5 CE 2008-2012 (c) docebo                               |
  |   License http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt            |
  \ ======================================================================== */

if (!defined('IN_FORMA'))
    die('You cannot access this file directly');

function questbank(&$url) {

    require_once(_lib_ . '/lib.form.php');
    require_once(_lms_ . '/lib/lib.quest_bank.php');

    $lang = & DoceboLanguage::createInstance('test', 'lms');
// now add the yui for the table

    $qb_select = new QuestBank_Selector();
    $qb_select->selected_quest = 'selected_quest';
    $qb_select->item_per_page = 25;

    $qb_man = new QuestBankMan();

    $form = new Form();

    header('Content-type: charset=utf-8');
    cout($qb_select->get_header(), 'page_head');
//addCss('style_yui_docebo');

    cout('<script type="text/javascript">'
            . $qb_select->get_setup_js()
            . '</script>', 'page_head');

    cout(getTitleArea($lang->def('_QUEST_BANK', 'menu_course'))
            . '<div class="std_block yui-skin-docebo yui-skin-sam">', 'content');

// -- search filter --------------------------------------------------

    $export_f = $qb_man->supported_format();

    cout($form->openForm('search_form', $url->getUrl(), false, 'POST')
            . '<input type="hidden" id="selected_quest" name="selected_quest" value="">'
            . '<div class="align_right">

			<input type="submit" id="export_quest" name="export_quest" value="' . $lang->def('_EXPORT') . '">
			<select id="export_quest_select" name="export_quest_select">', 'content');
    cout('<option value="-1">' . Lang::t('_NEW_TEST', 'test') . '</option>', 'content');
    foreach ($export_f as $id_exp => $def) {
        cout('<option value="' . $id_exp . '">' . $def . '</option>', 'content');
    }
    cout('</select>
			<input type="submit" id="import_quest" name="import_quest" value="' . $lang->def('_IMPORT') . '">
		</div>', 'content');

    cout($qb_select->get_filter(), 'content');

    cout($form->closeForm(), 'content');

// -------------------------------------------------------------------

    cout($qb_select->get_selector(), 'content');

    $re_type = mysql_query("
	SELECT type_quest
	FROM " . $GLOBALS['prefix_lms'] . "_quest_type
	WHERE type_quest <> 'break_page'
	ORDER BY sequence");

    cout('
	<div class="align_left">'
            . $form->openForm('add_quest_form', $url->getUrl('op=addquest'), 'GET') . '
		<input type="submit" id="add_quest" name="add_quest" value="' . $lang->def('_ADD') . '">
		<select id="add_test_quest" name="add_test_quest">', 'content');
    while (list($type_quest) = sql_fetch_row($re_type)) {

        cout('<option value="' . $type_quest . '">'
                . $lang->def('_QUEST_ACRN_' . strtoupper($type_quest)) . ' - ' . $lang->def('_QUEST_' . strtoupper($type_quest))
                . '</option>', 'content');
    }
    cout('</select>'
            . $form->closeForm() . '
	</div>', 'content');

    cout('</div>', 'content');
}

// XXX: addquest
function addquest(&$url) {
    checkPerm('view', false, 'storage');
    $lang = & DoceboLanguage::createInstance('test');

    $type_quest = Get::pReq('add_test_quest', DOTY_STRING, 'choice');

    require_once(_lms_ . '/modules/question/question.php');

    quest_create($type_quest, 0, $url->getUrl());
}

function modquest(&$url) {
    $lang = & DoceboLanguage::createInstance('test');

    $id_quest = importVar('id_quest', true, 0);

    list($type_quest) = sql_fetch_row(mysql_query("
	SELECT type_quest
	FROM " . $GLOBALS['prefix_lms'] . "_testquest
	WHERE idQuest = '" . $id_quest . "' AND idTest = 0"));

    require_once(_lms_ . '/modules/question/question.php');

    quest_edit($type_quest, $id_quest, $url->getUrl());
}

function importquest(&$url) {

    require_once(_lib_ . '/lib.form.php');

    $lang = & DoceboLanguage::createInstance('test');
    $form = new Form();

    require_once(_lms_ . '/lib/lib.quest_bank.php');
    $qb_man = new QuestBankMan();
    $supported_format = array_flip($qb_man->supported_format());

    require_once(_lms_ . '/lib/lib.questcategory.php');
    $quest_categories = array(
        0 => $lang->def('_NONE')
    );
    $cman = new Questcategory();
    $arr = $cman->getCategory();
    foreach ($arr as $id_category => $name_category) {
        $quest_categories[$id_category] = $name_category;
    }
    unset($arr);

    $title = array($url->getUrl() => $lang->def('_QUEST_BANK', 'menu_course'), $lang->def('_IMPORT'));
    cout(
            getTitleArea($title, 'quest_bank')
            . '<div class="std_block">'
            . $form->openForm('import_form', $url->getUrl('op=doimportquest'), false, false, 'multipart/form-data')
            . $form->openElementSpace()
            . $form->getFilefield($lang->def('_FILE'), 'import_file', 'import_file')
//            . $form->getRadioSet($lang->def('_FILE_FORMAT'), 'file_format', 'file_format', $supported_format, 0)
            . $form->getTextfield($lang->def('_FILE_ENCODE'), 'file_encode', 'file_encode', 255, 'utf-8')
            . $form->getDropdown($lang->def('_QUEST_CATEGORY'), 'quest_category', 'quest_category', $quest_categories)
            . $form->closeElementSpace()
            . $form->openButtonSpace()
            . $form->getButton('undo', 'undo', $lang->def('_UNDO'))
            . $form->getButton('quest_search', 'quest_search', $lang->def('_IMPORT'))
            . $form->closeButtonSpace()
            . $form->closeForm()
            . '</div>', 'content');
}

function doimportquest(&$url) {

    require_once(_lms_ . '/lib/lib.quest_bank.php');
    require_once 'PHPExcel/PHPExcel.php';

    $lang_t = & DoceboLanguage::createInstance('test');

    $qb_man = new QuestBankMan();

//    $file_format = Get::pReq('file_format', DOTY_INT, 0);
//    $file_encode = Get::pReq('file_encode', DOTY_ALPHANUM, 'utf-8');
//    $file_readed = file($_FILES['import_file']['tmp_name']);
    $filename = $_FILES['import_file']['tmp_name'];
    $inputFileType = PHPExcel_IOFactory::identify($filename);
    $objReader = PHPExcel_IOFactory::createReader($inputFileType);

    $objReader->setReadDataOnly(true);
    $file_readed = $objReader->load("$filename");

    $quest_category = Get::req('quest_category', DOTY_INT, 0);

//        echo '<pre>';
//        print_r ($file_readed);
//        echo '</pre>';
    addCss('style_yui_docebo');

    $title = array($url->getUrl() => $lang_t->def('_QUEST_BANK', 'menu_course'), $lang_t->def('_IMPORT'));
    cout(getTitleArea($title, 'quest_bank')
            . '<div class="std_block">'
            . getBackUi($url->getUrl(), $lang_t->def('_BACK')), 'content');

    $import_result = importQuestBank($file_readed);
//	$import_result = $qb_man->import_quest($file_readed, $file_format, 0, $quest_category);
    cout('<table clasS="type-one" id="import_result">'
            /*. '<caption>' . $lang_t->def('_IMPORT') . '</caption>'*/, 'content');
//    cout('<thead>', 'content');
//    cout('<tr class="type-one-header">'
//            . '<th>' . $lang_t->def('_QUEST_TYPE') . '</th>'
//            . '<th>' . $lang_t->def('_SUCCESS') . '</th>'
//            . '<th>' . $lang_t->def('_FAIL') . '</th>'
//            . '</tr>', 'content');
//    cout('</thead>', 'content');
    cout('<tbody>', 'content');
//    foreach ($import_result as $type_quest => $i_result) {
//
//        cout('<tr>'
//                . '<td>' . $lang_t->def('_QUEST_' . strtoupper($type_quest)) . '</td>'
//                . '<td>' . ( isset($i_result['success']) ? $i_result['success'] : '' ) . '</td>'
//                . '<td>' . ( isset($i_result['fail']) ? $i_result['fail'] : '' ) . '</td>'
//                . '</tr>', 'content');
//    }
    
    cout('<tr>'
                . '<td>' .  'Import success ' . $import_result . ' questions' . '</td>'
                . '</tr>', 'content');
    cout('</tbody>', 'content');
    cout('</table>', 'content');

    cout('</div>', 'content');
    
}

function exportquest(&$url) {

    require_once(_lms_ . '/lib/lib.quest_bank.php');

    $lang = & DoceboLanguage::createInstance('test');

    $qb_man = new QuestBankMan();

    $file_format = Get::pReq('export_quest_select', DOTY_INT, 0);
    $quest_category = Get::pReq('quest_category', DOTY_INT);
    $quest_difficult = Get::pReq('quest_difficult', DOTY_INT);
    $quest_type = Get::pReq('quest_type', DOTY_ALPHANUM);

    $quest_selection = Get::req('selected_quest', DOTY_NUMLIST, '');

    $quest_selection = array_filter(preg_split('/,/', $quest_selection, -1, PREG_SPLIT_NO_EMPTY));

    if ($file_format == -1) {
        $new_test_step = Get::pReq('new_test_step', DOTY_INT);

        if (Get::req('button_undo', DOTY_MIXED, false) !== false) {
            questbank($url);
            return;
        }

        if ($new_test_step == 2) {
            $title = trim($_POST['title']);
            if ($title == '')
                $title = $lang->def('_NOTITLE');

            if (is_array($quest_selection) && !empty($quest_selection)) {
//Insert the test

                $ins_query = "
				INSERT INTO " . $GLOBALS['prefix_lms'] . "_test
				( author, title, description )
					VALUES
				( '" . (int) getLogUserId() . "', '" . $title . "', '" . $_POST['textof'] . "' )";
//TODO:
                if (!mysql_query($ins_query)) {
                    $_SESSION['last_error'] = $lang->def('_OPERATION_FAILURE');
                }

                list($id_test) = sql_fetch_row(sql_query("SELECT LAST_INSERT_ID()"));

                if ($id_test) {
//Insert the question for the test

                    $reQuest = sql_query("
					SELECT q.idQuest, q.type_quest, t.type_file, t.type_class
					FROM " . $GLOBALS['prefix_lms'] . "_testquest AS q JOIN " . $GLOBALS['prefix_lms'] . "_quest_type AS t
					WHERE q.idQuest IN (" . implode(',', $quest_selection) . ") AND q.type_quest = t.type_quest");

                    while (list($idQuest, $type_quest, $type_file, $type_class) = sql_fetch_row($reQuest)) {
                        require_once(_lms_ . '/modules/question/' . $type_file);
                        $quest_obj = new $type_class($idQuest);
                        $new_id = $quest_obj->copy($id_test);
                    }

//Adding the item to the tree

                    require_once(_lms_ . '/modules/organization/orglib.php');

                    $odb = new OrgDirDb($_SESSION['idCourse']);

                    $odb->addItem(0, $title, 'test', $id_test, '0', '0', getLogUserId(), '1.0', '_DIFFICULT_MEDIUM', '', '', '', '', date('Y-m-d H:i:s'));
                }
            }

            questbank($url);
        } else {
            if (is_array($quest_selection) && !empty($quest_selection)) {
                require_once(_lib_ . '/lib.form.php');

                cout(getTitleArea($lang->def('_QUEST_BANK', 'menu_course'))
                        . '<div class="std_block yui-skin-docebo yui-skin-sam">', 'content');

                $form = new Form();

                cout($form->openForm('search_form', $url->getUrl(), false, 'POST')
                        . $form->getHidden('new_test_step', 'new_test_step', '2')
                        . $form->getHidden('export_quest', 'export_quest', $lang->def('_EXPORT'))
                        . $form->getHidden('export_quest_select', 'export_quest_select', $file_format)
                        . $form->getHidden('quest_category', 'quest_category', $quest_category)
                        . $form->getHidden('quest_difficult', 'quest_difficult', $quest_difficult)
                        . $form->getHidden('quest_type', 'quest_type', $quest_type)
                        . $form->getHidden('selected_quest', 'selected_quest', $_POST['selected_quest'])
                        . $form->openElementSpace()
                        . $form->getTextfield($lang->def('_TITLE'), 'title', 'title', '255')
                        . $form->getTextarea($lang->def('_DESCRIPTION'), 'textof', 'textof')
                        . $form->closeElementSpace()
                        . $form->openButtonSpace()
                        . $form->getButton('button_ins', 'button_ins', $lang->def('_TEST_INSERT'))
                        . $form->getButton('button_undo', 'button_undo', $lang->def('_UNDO'))
                        . $form->closeButtonSpace()
                        . $form->closeForm(), 'content');
            } else {
                $_SESSION['last_error'] = $lang->def('_EMPTY_SELECTION');
                questbank($url);
            }
        }
    } else {
        $quests = $qb_man->getQuestFromId($quest_selection);
        $quest_export = $qb_man->export_quest($quests, $file_format);
//        echo '<pre>';
//        print_r($quest_export);
//        echo '</pre>';
        require_once(_lib_ . '/lib.download.php');
        sendStrAsFile($quest_export, 'export_' . date("Y-m-d") . '.txt');
    }
}

function questbankDispatch($op) {

    require_once(_lib_ . '/lib.urlmanager.php');
    $url = & UrlManager::getInstance();
    $url->setStdQuery('modname=quest_bank&op=main');

    if (isset($_POST['undo']))
        $op = 'main';
    if (isset($_POST['import_quest']))
        $op = 'importquest';
    if (isset($_POST['export_quest']))
        $op = 'exportquest';

    switch ($op) {
        case "addquest" : {
                addquest($url);
            };
            break;
        case "modquest" : {
                modquest($url);
            };
            break;

        case "importquest" : {
                importquest($url);
            };
            break;
        case "doimportquest" : {
                doimportquest($url);
            };
            break;

        case "exportquest" : {
                exportquest($url);
            };
            break;

        case "main" :
        default: {
                questbank($url);
            }
    }
}

//import quest_bank
function importQuestBank($file_reader) {
    $query = "SELECT idQuest FROM learning_testquest ORDER BY idQuest DESC";
    $rs = sql_query($query);
    list($start) = sql_fetch_row($rs);
//    $count = count($file_reader);
//    echo "<pre>";
//    print_r($file_reader);
//    echo "</pre>";
//    for ($i = 0; $i < $count; $i++) {
//        $position_1 = strpos($line, "\t");
//        $position_2 = strpos($line, "\t", $position_1 + 1);
//        $position_3 = strpos($line, "\t", $position_2 + 1);
//        $position_4 = strpos($line, "\t", $position_3 + 1);
//        $position_5 = strpos($line, "\t", $position_4 + 1);
//        $position_6 = strlen($line);
//        $question = substr($line, 0, $position_1);
//        $true_answer = substr($line, $position_1, $position_2 - $position_1);
//        $answer_2 = substr($line, $position_2, $position_3 - $position_2);
//        $answer_3 = substr($line, $position_3, $position_4 - $position_3);
//        $answer_4 = substr($line, $position_4, $position_5 - $position_4);
//        $mark = substr($line, $position_5, $position_6 - $position_5);
//        if ($question != NULL && $question != '') {
//            $query = "INSERT INTO learning_testquest (idTest, idCategory, type_quest, title_quest, difficult, time_assigned, sequence, page, shuffle)"
//                    . "VALUES(0, 0, 'choice','" . $question . "',3,0," . ($i + 1) . ",1,0)";
//            sql_query($query);
//            $query = "SELECT idQuest FROM learning_testquest ORDER BY idQuest DESC";
//            $rs = sql_query($query);
//            list($idQuest) = sql_fetch_row($rs);
//        echo $idQuest;
//            importTrueAnswer($idQuest, $true_answer, $mark);
//            importAnswer($idQuest, $answer_2);
//            importAnswer($idQuest, $answer_3);
//            importAnswer($idQuest, $answer_4);
//            echo $answer_2;
//            echo '-';
//            echo $answer_3;
//            echo '-';
//            echo $answer_4;
//            echo '<br/>';
//        }
//    }
    $objPHPExcel = $file_reader;
    $total_sheets = $objPHPExcel->getSheetCount();

    $allSheetName = $objPHPExcel->getSheetNames();
    $objWorksheet = $objPHPExcel->setActiveSheetIndex(0);
    $highestRow = $objWorksheet->getHighestRow();
    $highestColumn = $objWorksheet->getHighestColumn();
    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
    $arraydata = array();
    for ($row = 2; $row <= $highestRow; ++$row) {
        for ($col = 0; $col < $highestColumnIndex; ++$col) {
            $value = $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
            $arraydata[$row - 2][$col] = $value;
        }
    }

    $stt = 0;
    foreach ($arraydata as $row) {
        if ($stt >= 1) {
            $question = $row[0];
            $true_answer = $row[1];
            $answer_2 = $row[2];
            $answer_3 = $row[3];
            $answer_4 = $row[4];
            if ($row[5] != NULL && $row[5] != '') {
                $mark = $row[5];
            } else {
                $mark = 0;
            }
            if ($question != NULL && $question != '') {
                $query = "INSERT INTO learning_testquest (idTest, idCategory, type_quest, title_quest, difficult, time_assigned, sequence, page, shuffle)"
                        . "VALUES(0, 0, 'choice','" . $question . "',3,0," . ($i + 1) . ",1,0)";
                sql_query($query);
                $query = "SELECT idQuest FROM learning_testquest ORDER BY idQuest DESC";
                $rs = sql_query($query);
                list($idQuest) = sql_fetch_row($rs);
//            echo $idQuest;
                importTrueAnswer($idQuest, $true_answer, $mark);
                if ($answer_2 != NULL && $answer_2 != '') {
                    importAnswer($idQuest, $answer_2);
                }
                if ($answer_2 != NULL && $answer_2 != '') {
                    importAnswer($idQuest, $answer_3);
                }
                if ($answer_2 != NULL && $answer_2 != '') {
                    importAnswer($idQuest, $answer_4);
                }
            }
        }
        $stt = $stt + 1;
    }
    $query = "SELECT idQuest FROM learning_testquest ORDER BY idQuest DESC";
    $rs = sql_query($query);
    list($end) = sql_fetch_row($rs);
    return $end - $start;
//    echo '<pre>';
//    print_r($arraydata);
//    echo '</pre>';
}

function importTrueAnswer($idQuest, $true_answer, $mark) {
    $query = "INSERT INTO learning_testquestanswer (idQuest, sequence, is_correct, answer, comment, score_correct, score_incorrect)"
            . "VALUES (" . $idQuest . ", 0, 1, '" . $true_answer . "', '', '" . $mark . "','0')";
    sql_query($query);
}

function importAnswer($idQuest, $answer) {
    $query = "INSERT INTO learning_testquestanswer (idQuest, sequence, is_correct, answer, comment, score_correct, score_incorrect)"
            . "VALUES (" . $idQuest . ", 0, 0, '" . $answer . "','', '0','0')";
    sql_query($query);
}

function is_utf8($str) {
    return preg_match("/^(
         [\x09\x0A\x0D\x20-\x7E]            # ASCII
       | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
       |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
       | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
       |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
       |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
       | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
       |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
      )*$/x", $str
    );
}

function force_utf8($str, $inputEnc = 'WINDOWS-1252') {
    if (is_utf8($str)) // Nothing to do.
        return $str;

    if (strtoupper($inputEnc) === 'ISO-8859-1')
        return utf8_encode($str);

    if (function_exists('mb_convert_encoding'))
        return mb_convert_encoding($str, 'UTF-8', $inputEnc);

    if (function_exists('iconv'))
        return iconv($inputEnc, 'UTF-8', $str);

    // You could also just return the original string.
    trigger_error(
            'Cannot convert string to UTF-8 in file '
            . __FILE__ . ', line ' . __LINE__ . '!', E_USER_ERROR
    );
}

?>