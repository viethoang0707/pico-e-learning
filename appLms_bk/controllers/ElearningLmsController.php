<?php

defined("IN_FORMA") or die('Direct access is forbidden.');

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

class ElearningLmsController extends LmsController {

    public $name = 'elearning';
    public $ustatus = array();
    public $cstatus = array();
    public $levels = array();
    public $path_course = '';
    protected $_default_action = 'show';
    public $info = array();

    public function isTabActive($tab_name) {

        switch ($tab_name) {
            case "new" : {
                    if (!isset($this->info['elearning'][0]))
                        return false;
                };
                break;
            case "inprogress" : {
                    if (!isset($this->info['elearning'][1]))
                        return false;
                };
                break;
            case "completed" : {
                    if (!isset($this->info['elearning'][2]))
                        return false;
                };
                break;
        }
        return true;
    }

    public function init() {

        YuiLib::load('base,tabview');
        require_once(_base_ . '/lib/lib.json.php');
        $this->json = new Services_JSON();
        if (!isset($_SESSION['id_common_label']))
            $_SESSION['id_common_label'] = -1;

        require_once(_lms_ . '/lib/lib.course.php');
        require_once(_lms_ . '/lib/lib.subscribe.php');
        require_once(_lms_ . '/lib/lib.levels.php');

        $this->cstatus = array(
            CST_PREPARATION => '_CST_PREPARATION',
            CST_AVAILABLE => '_CST_AVAILABLE',
            CST_EFFECTIVE => '_CST_CONFIRMED',
            CST_CONCLUDED => '_CST_CONCLUDED',
            CST_CANCELLED => '_CST_CANCELLED',
        );

        $this->ustatus = array(
            //_CUS_RESERVED 		=> '_T_USER_STATUS_RESERVED',
            _CUS_WAITING_LIST => '_WAITING_USERS',
            _CUS_CONFIRMED => '_T_USER_STATUS_CONFIRMED',
            _CUS_SUBSCRIBED => '_T_USER_STATUS_SUBS',
            _CUS_BEGIN => '_T_USER_STATUS_BEGIN',
            _CUS_END => '_T_USER_STATUS_END'
        );
        $this->levels = CourseLevel::getLevels();
        $this->path_course = $GLOBALS['where_files_relative'] . '/appLms/' . Get::sett('pathcourse') . '/';

        $upd = new UpdatesLms();
        $this->info = $upd->courseUpdates();

        $this->base_link_course = 'lms/elearning';
    }

    public function fieldsTask() {
        $level = Docebo::user()->getUserLevelId();
        if (Get::sett('request_mandatory_fields_compilation', 'off') == 'on' && $level != ADMIN_GROUP_GODADMIN) {
            require_once(_adm_ . '/lib/lib.field.php');
            $fl = new FieldList();
            $idst_user = Docebo::user()->getIdSt();
            $res = $fl->storeFieldsForUser($idst_user);
        }
        Util::jump_to('index.php?r=elearning/show');
    }

    public function showTask() {

//        $model = new ElearningLms();
//
//        if (Get::sett('on_usercourse_empty') === 'on') {
//            $conditions_t = array(
//                'cu.iduser = :id_user'
//            );
//
//            $params_t = array(
//                ':id_user' => (int) Docebo::user()->getId()
//            );
//
//            $cp_courses = $model->getUserCoursePathCourses(Docebo::user()->getIdst());
//            if (!empty($cp_courses)) {
//                $conditions_t[] = "cu.idCourse NOT IN (" . implode(",", $cp_courses) . ")";
//            }
//
//            $courselist_t = $model->findAll($conditions_t, $params_t);
//
//            if (empty($courselist_t))
//                Util::jump_to('index.php?r=lms/catalog/show&sop=unregistercourse');
//        }
//
//        require_once(_lms_ . '/lib/lib.middlearea.php');
//        $ma = new Man_MiddleArea();
//        $block_list = array();
//        //if($ma->currentCanAccessObj('user_details_short')) $block_list['user_details_short'] = true;
//        if ($ma->currentCanAccessObj('user_details_full'))
//            $block_list['user_details_full'] = true;
//        if ($ma->currentCanAccessObj('credits'))
//            $block_list['credits'] = true;
//        if ($ma->currentCanAccessObj('news'))
//            $block_list['news'] = true;
//        $tb_label = $ma->currentCanAccessObj('tb_label');
//        if (!$tb_label)
//            $_SESSION['id_common_label'] = 0;
//        else {
//            $id_common_label = Get::req('id_common_label', DOTY_INT, -1);
//
//            if ($id_common_label >= 0)
//                $_SESSION['id_common_label'] = $id_common_label;
//            elseif ($id_common_label == -2)
//                $_SESSION['id_common_label'] = -1;
//
//            $block_list['labels'] = true;
//        }
//
//        if ($tb_label && $_SESSION['id_common_label'] == -1) {
//            require_once(_lms_ . '/admin/models/LabelAlms.php');
//            $label_model = new LabelAlms();
//
//            $user_label = $label_model->getLabelForUser(Docebo::user()->getId());
//
//            $this->render('_labels', array('block_list' => $block_list,
//                'label' => $user_label));
//        } else {
//            if (!empty($block_list)) {
//                if (isset($_GET['res']) && $_GET['res'] !== '')
//                    UIFeedback::info(Lang::t('_OPERATION_SUCCESSFUL', 'standard'));
//
//                if (isset($_GET['err']) && $_GET['err'] !== '')
//                    UIFeedback::error(Lang::t('_OPERATION_FAILURE', 'standard'));
//
//                $params = array();
//
//                $filter = array(
//                    'id_category' => $this->_getSessionTreeData('id_category', 0));
//
//                $params['initial_selected_node'] = $this->_getSessionTreeData('id_category', 0);
//                $params['filter'] = $filter;
//                $params['root_name'] = Lang::t('_CATEGORY', 'admin_course_managment');
//                $params['permissions'] = $this->permissions;
//
//                $params['base_link_course'] = $this->base_link_course;
//
//                $smodel = new SubscriptionAlms();
//                $params['unsubscribe_requests'] = $smodel->countPendingUnsubscribeRequests();
//                $this->render('_tabs_block', $params);
//            } else
//                $this->render('_tabs', array());
//        }
//
//        // add feedback:
//        // - feedback_type: [err|inf] display error feedback or info feedback
//        // - feedback_code: translation code of message
//        // - feedback_extra: extrainfo concat at end message
//        $feedback_code = Get::req('feedback_code', DOTY_STRING, "");
//        $feedback_type = Get::req('feedback_type', DOTY_STRING, "");
//        $feedback_extra = Get::req('feedback_extra', DOTY_STRING, "");
//        switch ($feedback_type) {
//            case "err":
//                $msg = Lang::t($feedback_code, 'login') . " " . $feedback_extra;
//                UIFeedback::error($msg);
//                break;
//            case "inf":
//                $msg = Lang::t($feedback_code, 'login') . " " . $feedback_extra;
//                UIFeedback::info($msg);
//                break;
//        }

        $this->getAllCourse();
    }

    public function newTask() {
        $model = new ElearningLms();

        $filter_text = Get::req('filter_text', DOTY_STRING, "");
        $filter_year = Get::req('filter_year', DOTY_INT, 0);

        $conditions = array(
            'cu.iduser = :id_user',
            'cu.status = :status'
        );

        $params = array(
            ':id_user' => (int) Docebo::user()->getId(),
            ':status' => _CUS_SUBSCRIBED
        );

        if (!empty($filter_text)) {
            $conditions[] = "(c.code LIKE '%:keyword%' OR c.name LIKE '%:keyword%')";
            $params[':keyword'] = $filter_text;
        }

        if (!empty($filter_year)) {
            $conditions[] = "(cu.date_inscr >= ':year-00-00 00:00:00' AND cu.date_inscr <= ':year-12-31 23:59:59')";
            $params[':year'] = $filter_year;
        }

        $courselist = $model->findAll($conditions, $params);

        //check courses accessibility
        $keys = array_keys($courselist);
        for ($i = 0; $i < count($keys); $i++) {
            $courselist[$keys[$i]]['can_enter'] = Man_Course::canEnterCourse($courselist[$keys[$i]]);
        }

        require_once(_lms_ . '/lib/lib.middlearea.php');
        $ma = new Man_MiddleArea();
        $this->render('courselist', array(
            'path_course' => $this->path_course,
            'courselist' => $courselist,
            'use_label' => $ma->currentCanAccessObj('tb_label'),
            'keyword' => $filter_text
        ));
    }

    public function inprogress() {
        $model = new ElearningLms();

        $filter_text = Get::req('filter_text', DOTY_STRING, "");
        $filter_year = Get::req('filter_year', DOTY_INT, 0);

        $conditions = array(
            'cu.iduser = :id_user',
            'cu.status = :status'
        );

        $params = array(
            ':id_user' => (int) Docebo::user()->getId(),
            ':status' => _CUS_BEGIN
        );

        if (!empty($filter_text)) {
            $conditions[] = "(c.code LIKE '%:keyword%' OR c.name LIKE '%:keyword%')";
            $params[':keyword'] = $filter_year;
        }

        if (!empty($filter_year)) {
            $conditions[] = "(cu.date_inscr >= ':year-00-00 00:00:00' AND cu.date_inscr <= ':year-12-31 23:59:59')";
            $params[':year'] = $filter_text;
        }

        $courselist = $model->findAll($conditions, $params);

        //check courses accessibility
        $keys = array_keys($courselist);
        for ($i = 0; $i < count($keys); $i++) {
            $courselist[$keys[$i]]['can_enter'] = Man_Course::canEnterCourse($courselist[$keys[$i]]);
        }
        require_once(_lms_ . '/lib/lib.middlearea.php');
        $ma = new Man_MiddleArea();
        $this->render('courselist', array(
            'path_course' => $this->path_course,
            'courselist' => $courselist,
            'use_label' => $ma->currentCanAccessObj('tb_label'),
            'keyword' => $filter_text
        ));
    }

    public function completed() {
        $model = new ElearningLms();

        $filter_text = Get::req('filter_text', DOTY_STRING, "");
        $filter_year = Get::req('filter_year', DOTY_INT, 0);

        $conditions = array(
            'cu.iduser = :id_user',
            'cu.status = :status'
        );

        $params = array(
            ':id_user' => (int) Docebo::user()->getId(),
            ':status' => _CUS_END
        );

        if (!empty($filter_text)) {
            $conditions[] = "(c.code LIKE '%:keyword%' OR c.name LIKE '%:keyword%')";
            $params[':keyword'] = $filter_text;
        }

        if (!empty($filter_year)) {
            $conditions[] = "(cu.date_inscr >= ':year-00-00 00:00:00' AND cu.date_inscr <= ':year-12-31 23:59:59')";
            $params[':year'] = $filter_year;
        }

        $courselist = $model->findAll($conditions, $params);

        //check courses accessibility
        $keys = array_keys($courselist);
        for ($i = 0; $i < count($keys); $i++) {
            $courselist[$keys[$i]]['can_enter'] = Man_Course::canEnterCourse($courselist[$keys[$i]]);
        }
        require_once(_lms_ . '/lib/lib.middlearea.php');
        $ma = new Man_MiddleArea();
        $this->render('courselist', array(
            'path_course' => $this->path_course,
            'courselist' => $courselist,
            'use_label' => $ma->currentCanAccessObj('tb_label'),
            'keyword' => $filter_text
        ));
    }

    public function allTask() {
        $model = new ElearningLms();

        $filter_text = Get::req('filter_text', DOTY_STRING, "");
        $filter_year = Get::req('filter_year', DOTY_INT, 0);

        $conditions = array(
            'cu.iduser = :id_user',
            'cu.status <> :status'
        );

        $params = array(
            ':id_user' => (int) Docebo::user()->getId(),
            ':status' => _CUS_END
        );

        if (!empty($filter_text)) {
            $conditions[] = "(c.code LIKE '%:keyword%' OR c.name LIKE '%:keyword%')";
            $params[':keyword'] = $filter_text;
        }

        if (!empty($filter_year)) {
            $conditions[] = "(cu.date_inscr >= ':year-00-00 00:00:00' AND cu.date_inscr <= ':year-12-31 23:59:59')";
            $params[':year'] = $filter_year;
        }

//		$cp_courses = $model->getUserCoursePathCourses( Docebo::user()->getIdst() );
//		if (!empty($cp_courses)) {
//			$conditions[] = "cu.idCourse NOT IN (".implode(",", $cp_courses).")";
//		}

        $courselist = $model->findAll($conditions, $params);

        //check courses accessibility
        $keys = array_keys($courselist);
        for ($i = 0; $i < count($keys); $i++) {
            $courselist[$keys[$i]]['can_enter'] = Man_Course::canEnterCourse($courselist[$keys[$i]]);
        }

        require_once(_lms_ . '/lib/lib.middlearea.php');
        $ma = new Man_MiddleArea();
        $this->render('courselist', array(
            'path_course' => $this->path_course,
            'courselist' => $courselist,
            'use_label' => $ma->currentCanAccessObj('tb_label'),
            'keyword' => $filter_text
        ));
    }

    /**
     * This implies the skill gap analysis :| well, a first implementation will be done based on
     * required over acquired skill and proposing courses that will give, the required competences.
     * If this implementation will require too much time i will wait for more information and pospone the implementation
     */
    public function suggested() {

        $competence_needed = Docebo::user()->requiredCompetences();

        $model = new ElearningLms();
        $courselist = $model->findAll(array(
            'cu.iduser = :id_user',
            'comp.id_competence IN (:competence_list)'
                ), array(
            ':id_user' => Docebo::user()->getId(),
            ':competence_list' => $competence_needed
                ), array('LEFT JOIN %lms_competence AS comp ON ( .... ) '));

        $this->render('courselist', array(
            'path_course' => $this->path_course,
            'courselist' => $courselist
        ));
    }

    /**
     * The action of self-unsubscription from a course (if enabled for the course),
     * available in the course box of the courses list
     */
    public function self_unsubscribe() {
        $id_user = Docebo::user()->idst; //Get::req('id_user', DOTY_INT, Docebo::user()->idst);
        $id_course = Get::req('id_course', DOTY_INT, 0);
        $id_edition = Get::req('id_edition', DOTY_INT, 0);
        $id_date = Get::req('id_date', DOTY_INT, 0);

        $cmodel = new CourseAlms();
        $cinfo = $cmodel->getCourseModDetails($id_course);

        //index.php?r=elearning/show
        $back = Get::req('back', DOTY_STRING, "");
        if ($back != "") {
            $parts = explode('/', $back);
            $length = count($parts);
            if ($length > 0) {
                $parts[$length - 1] = 'show';
                $back = implode('/', $parts);
            }
        }
        $jump_url = 'index.php?r=' . ($back ? $back : 'lms/elearning/show');

        if ($cinfo['auto_unsubscribe'] == 0) {
            //no self unsubscribe possible for this course
            Util::jump_to($jump_url . '&res=err_unsub');
        }

        $date_ok = TRUE;
        if ($cinfo['unsubscribe_date_limit'] != "" && $cinfo['unsubscribe_date_limit'] != "0000-00-00 00:00:00") {
            if ($cinfo['unsubscribe_date_limit'] < date("Y-m-d H:i:s")) {
                //self unsubscribing is no more allowed, go back to courselist page
                Util::jump_to($jump_url . '&res=err_unsub');
            }
        }

        $smodel = new SubscriptionAlms();
        $param = '';

        if ($cinfo['auto_unsubscribe'] == 1) {
            //moderated self unsubscribe
            $res = $smodel->setUnsubscribeRequest($id_user, $id_course, $id_edition, $id_date);
            $param .= $res ? '&res=ok_unsub' : '&res=err_unsub';
        }

        if ($cinfo['auto_unsubscribe'] == 2) {
            //directly unsubscribe user
            $res = $smodel->unsubscribeUser($id_user, $id_course, $id_edition, $id_date);
            $param .= $res ? '&res=ok_unsub' : '&res=err_unsub';
        }

        Util::jump_to($jump_url);
    }

    public function gettreedata() {
        require_once(_lms_ . '/lib/category/class.categorytree.php');
        $treecat = new Categorytree();

        $command = Get::req('command', DOTY_ALPHANUM, "");
        switch ($command) {
            case "expand":
                $node_id = Get::req('node_id', DOTY_INT, 0);
                $initial = Get::req('initial', DOTY_INT, 0);

                $db = DbConn::getInstance();
                $result = array();
                if ($initial == 1) {
                    $treestatus = $this->_getSessionTreeData('id_category', 0);
                    $folders = $treecat->getOpenedFolders($treestatus);
                    $result = array();

                    $ref = & $result;
                    foreach ($folders as $folder) {
                        if ($folder > 0) {
                            for ($i = 0; $i < count($ref); $i++) {
                                if ($ref[$i]['node']['id'] == $folder) {
                                    $ref[$i]['children'] = array();
                                    $ref = & $ref[$i]['children'];
                                    break;
                                }
                            }
                        }

                        $childrens = $treecat->getJoinedChildrensById($folder);
                        while (list($id_category, $idParent, $path, $lev, $left, $right, $associated_courses) = $db->fetch_row($childrens)) {
                            $is_leaf = ($right - $left) == 1;
                            $node_options = $this->_getNodeActions($id_category, $is_leaf, $associated_courses);
                            $ref[] = array(
                                'node' => array(
                                    'id' => $id_category,
                                    'label' => end(explode('/', $path)),
                                    'is_leaf' => $is_leaf,
                                    'count_content' => (int) (($right - $left - 1) / 2),
                                    'options' => $node_options));
                        }
                    }
                } else { //not initial selection, just an opened folder
                    $re = $treecat->getJoinedChildrensById($node_id);
                    while (list($id_category, $idParent, $path, $lev, $left, $right, $associated_courses) = $db->fetch_row($re)) {
                        $is_leaf = ($right - $left) == 1;

                        $node_options = $this->_getNodeActions($id_category, $is_leaf, $associated_courses);

                        $result[] = array(
                            'id' => $id_category,
                            'label' => end(explode('/', $path)),
                            'is_leaf' => $is_leaf,
                            'count_content' => (int) (($right - $left - 1) / 2),
                            'options' => $node_options); //change this
                    }
                }

                $output = array('success' => true, 'nodes' => $result, 'initial' => ($initial == 1));
                echo $this->json->encode($output);
                break;

            case "set_selected_node":
                $id_node = Get::req('node_id', DOTY_INT, -1);
                if ($id_node >= 0)
                    $this->_setSessionTreeData('id_category', $id_node);
                break;

            case "modify":
                if (!$this->permissions['mod_category']) {
                    $output = array('success' => false, 'message' => $this->_getMessage("no permission"));
                    echo $this->json->encode($output);
                    return;
                }

                $node_id = Get::req('node_id', DOTY_INT, 0);
                $new_name = Get::req('name', DOTY_STRING, false);

                $result = array('success' => false);
                if ($new_name !== false)
                    $result['success'] = $treecat->renameFolderById($node_id, $new_name);
                if ($result['success'])
                    $result['new_name'] = stripslashes($new_name);

                echo $this->json->encode($result);
                break;


            case "create":
                if (!$this->permissions['add_category']) {
                    $output = array('success' => false, 'message' => $this->_getMessage("no permission"));
                    echo $this->json->encode($output);
                    return;
                }

                $node_id = Get::req('node_id', DOTY_INT, false);
                $node_name = Get::req('name', DOTY_STRING, false); //no multilang required for categories

                $result = array();
                if ($node_id === false)
                    $result['success'] = false;
                else {
                    $success = false;
                    $new_node_id = $treecat->addFolderById($node_id, $node_name);
                    if ($new_node_id != false && $new_node_id > 0)
                        $success = true;

                    $result['success'] = $success;
                    if ($success)
                        $result['node'] = array(
                            'id' => $new_node_id,
                            'label' => stripslashes($node_name),
                            'is_leaf' => true,
                            'count_content' => 0,
                            'options' => $this->_getNodeActions($new_node_id, true));
                }
                echo $this->json->encode($result);
                break;

            case "delete":
                if (!$this->permissions['del_category']) {
                    $output = array('success' => false, 'message' => $this->_getMessage("no permission"));
                    echo $this->json->encode($output);
                    return;
                }

                $node_id = Get::req('node_id', DOTY_INT, 0);
                $result = array('success' => $treecat->deleteTreeById($node_id));
                echo $this->json->encode($result);
                break;

            case "move":
                if (!$this->permissions['mod_category']) {
                    $output = array('success' => false, 'message' => $this->_getMessage("no permission"));
                    echo $this->json->encode($output);
                    return;
                }

                $node_id = Get::req('src', DOTY_INT, 0);
                $node_dest = Get::req('dest', DOTY_INT, 0);
                $model = new CoursecategoryAlms();
                $result = array('success' => $model->moveFolder($node_id, $node_dest));

                echo $this->json->encode($result);
                break;

            case "options":
                $node_id = Get::req('node_id', DOTY_INT, 0);

                //get properties from DB
                $count = $treecat->getChildrenCount($node_id);
                $is_leaf = true;
                if ($count > 0)
                    $is_leaf = false;
                $node_options = $this->_getNodeActions($node_id, $is_leaf);

                $result = array('success' => true, 'options' => $node_options, '_debug' => $count);
                echo $this->json->encode($result);
                break;
            //invalid command
            default: {
                    
                }
        }
    }

    protected function _getSessionTreeData($index, $default = false) {
        if (!$index || !is_string($index))
            return false;
        if (!isset($_SESSION['course_category']['filter_status'][$index]))
            $_SESSION['course_category']['filter_status'][$index] = $default;
        return $_SESSION['course_category']['filter_status'][$index];
    }

    public function getcourselist() {
        //Datatable info
        $start_index = Get::req('startIndex', DOTY_INT, 0);
        $results = Get::req('results', DOTY_MIXED, Get::sett('visuItem', 25));
        $sort = Get::req('sort', DOTY_MIXED, 'userid');
        $dir = Get::req('dir', DOTY_MIXED, 'asc');

        $id_category = Get::req('node_id', DOTY_INT, (int) $this->_getSessionTreeData('id_category', 0));
        $filter_text = $_SESSION['course_filter']['text'];
        $classroom = $_SESSION['course_filter']['classroom'];
        $descendants = $_SESSION['course_filter']['descendants'];
        $waiting = $_SESSION['course_filter']['waiting'];

        $filter_open = false;

        if ($descendants || $waiting)
            $filter_open = true;

        $filter = array(
            'id_category' => $id_category,
            'classroom' => $classroom,
            'descendants' => $descendants,
            'waiting' => $waiting,
            'text' => $filter_text,
            'open' => $filter_open
        );

        $total_course = $this->model->getCourseNumber($filter);
        if ($start_index >= $total_course) {
            if ($total_course < $results) {
                $start_index = 0;
            } else {
                $start_index = $total_course - $results;
            }
        }
        $course_res = $this->model->loadCourse($start_index, $results, $sort, $dir, $filter);
        $course_with_cert = $this->model->getCourseWithCertificate();
        $course_with_competence = $this->model->getCourseWithCompetence();

        $list = array();

        while ($row = sql_fetch_assoc($course_res)) {
            $course_type = 'elearning';
            switch ($row['course_type']) {
                case 'classroom': $course_type = 'classroom';
                case 'elearning': {
                        if ($row['course_edition'] > 0)
                            $course_type = 'edition';
                    }
            }

            $num_subscribed = $row['subscriptions'] - $row['pending'];

            $list[$row['idCourse']] = array(
                'id' => $row['idCourse'],
                'code' => $row['code'],
                'name' => $row['name'],
                'type' => Lang::t('_' . strtoupper($row['course_type'])),
                'type_id' => $course_type,
                'wait' => (/* $row['course_type'] !== 'classroom' && */$row['course_edition'] != 1 && $row['pending'] != 0 ? '<a href="index.php?r=' . $this->base_link_subscription . '/waitinguser&id_course=' . $row['idCourse'] . '" title="' . Lang::t('_WAITING', 'course') . '">' . $row['pending'] . '</a>' : '' ),
                'user' => ($row['course_type'] !== 'classroom' && $row['course_edition'] != 1 ? '<a class="nounder" href="index.php?r=' . $this->base_link_subscription . '/show&amp;id_course=' . $row['idCourse'] . '" title="' . Lang::t('_SUBSCRIPTION', 'course') . '">' . $num_subscribed . ' ' . Get::img('standard/moduser.png', Lang::t('_SUBSCRIPTION', 'course')) . '</a>' : ''),
                'edition' => ($row['course_type'] === 'classroom' ? '<a href="index.php?r=' . $this->base_link_classroom . '/classroom&amp;id_course=' . $row['idCourse'] . '" title="' . Lang::t('_CLASSROOM_EDITION', 'course') . '">' . $this->model->classroom_man->getDateNumber($row['idCourse'], true) . '</a>' : ($row['course_edition'] == 1 ? '<a href="index.php?r=' . $this->base_link_edition . '/show&amp;id_course=' . $row['idCourse'] . '" title="' . Lang::t('_EDITIONS', 'course') . '">' . $this->model->edition_man->getEditionNumber($row['idCourse']) . '</a>' : '')),
                'certificate' => '<a href="index.php?r=' . $this->base_link_course . '/certificate&amp;id_course=' . $row['idCourse'] . '">' . Get::sprite('subs_pdf' . (!isset($course_with_cert[$row['idCourse']]) ? '_grey' : ''), Lang::t('_CERTIFICATE_ASSIGN_STATUS', 'course')) . '</a>',
                'competences' => '<a href="index.php?r=' . $this->base_link_competence . '/man_course&amp;id_course=' . $row['idCourse'] . '">' . Get::sprite('subs_competence' . (!isset($course_with_competence[$row['idCourse']]) ? '_grey' : ''), Lang::t('_COMPETENCES', 'course')) . '</a>',
                'menu' => '<a href="index.php?r=' . $this->base_link_course . '/menu&amp;id_course=' . $row['idCourse'] . '">' . Get::sprite('subs_menu', Lang::t('_ASSIGN_MENU', 'course')) . '</a>',
                'dup' => 'ajax.adm_server.php?r=' . $this->base_link_course . '/dupcourse&id_course=' . $row['idCourse'],
                'mod' => '<a href="index.php?r=' . $this->base_link_course . '/modcourse&amp;id_course=' . $row['idCourse'] . '">' . Get::sprite('subs_mod', Lang::t('_MOD', 'standard')) . '</a>',
                'del' => 'ajax.adm_server.php?r=' . $this->base_link_course . '/delcourse&id_course=' . $row['idCourse'] . '&confirm=1'
            );
        }

        if (!empty($list)) {
            $id_list = array_keys($list);
            $count_students = $this->model->getCoursesStudentsNumber($id_list);
            foreach ($list as $id_course => $cinfo) {
                $list[$id_course]['students'] = isset($count_students[$id_course]) ? $count_students[$id_course] : '';
            }
        }

        $result = array(
            'totalRecords' => $total_course,
            'startIndex' => $start_index,
            'sort' => $sort,
            'dir' => $dir,
            'rowsPerPage' => $results,
            'results' => count($list),
            'records' => array_values($list)
        );

        echo $this->json->encode($result);
    }

    protected function _getNodeActions($id_category, $is_leaf, $associated_courses = 0) {
        $node_options = array();

        //modify category action
        if ($this->permissions['mod_category']) {
            $node_options[] = array(
                'id' => 'mod_' . $id_category,
                'command' => 'modify',
                'icon' => 'standard/edit.png',
                'alt' => Lang::t('_MOD')
            );
        }

        //delete category action
        if ($this->permissions['del_category']) {
            if ($is_leaf && $associated_courses == 0) {
                $node_options[] = array(
                    'id' => 'del_' . $id_category,
                    'command' => 'delete',
                    'icon' => 'standard/delete.png',
                    'alt' => Lang::t('_DEL'));
            } else {
                $node_options[] = array(
                    'id' => 'del_' . $id_category,
                    'command' => false,
                    'icon' => 'blank.png');
            }
        }

        return $node_options;
    }

    public function getAllCourse() {
        require_once(_base_ . '/lib/lib.navbar.php');
        require_once(_lms_ . '/lib/lib.middlearea.php');
        $model = new CatalogLms();
        $active_tab = 'all';
        $action = Get::req('action', DOTY_STRING, '');

        $page = Get::req('page', DOTY_INT, 1);
        $id_cat = Get::req('id_cat', DOTY_INT, 0);

        $nav_bar = new NavBar('page', Get::sett('visuItem'), $model->getTotalCourseNumber($active_tab), 'link');

        $nav_bar->setLink('index.php?r=catalog/allCourse' . ($id_cat > 1 ? '&amp;id_cat=' . $id_cat : ''));



        $html = $model->getCourseList($active_tab, $page);
        $user_catalogue = $model->getUserCatalogue(Docebo::user()->getIdSt());
        $user_coursepath = $model->getUserCoursepath(Docebo::user()->getIdSt());

        $ma = new Man_MiddleArea();

        echo '<div class="middlearea_container">';

        $lmstab = $this->widget('lms_tab', array(
            'active' => 'catalog',
            'close' => false));

        $this->render('tab_start', array('user_catalogue' => $user_catalogue,
            'active_tab' => $active_tab,
            'user_coursepath' => $user_coursepath,
            'std_link' => 'index.php?r=catalog/allCourse' . ($page > 1 ? '&amp;page=' . $page : ''),
            'model' => $model,
            'ma' => $ma));
        $this->render('courselist', array('html' => $html,
            'nav_bar' => $nav_bar));
        $this->render('tab_end', array('std_link' => 'index.php?r=catalog/allCourse' . ($page > 1 ? '&amp;page=' . $page : ''),
            'model' => $model));
        $lmstab->endWidget();

        echo '</div>';
    }

}
