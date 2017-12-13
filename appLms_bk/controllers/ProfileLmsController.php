<?php defined("IN_FORMA") or die('Direct access is forbidden.');

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

if (Docebo::user()->isAnonymous())
    die("You can't access!");

class ProfileLmsController extends LmsController {

    protected $db;
    protected $model;
    protected $json;
    protected $aclManager;
    protected $max_dim_avatar;

    public function init() {
        require_once(_base_ . '/lib/lib.json.php');
        $this->db = DbConn::getInstance();
        $this->model = new ProfileLms();
        $this->json = new Services_JSON();
        $this->aclManager = Docebo::user()->getAClManager();
        $this->max_dim_avatar = 150;
    }

    protected function _profileBackUrl() {
        $id_user = Get::req('id_user', DOTY_INT, 0);
        $type = Get::req('type', DOTY_STRING, 'false');
        $from = Get::req('from', DOTY_INT, 0);
        $back_my_friend = Get::req('back', DOTY_INT, 0);
        if ($type !== 'false')
            if ($from == 0)
                return getBackUi('index.php?modname=profile&op=profile&id_user=' . $id_user . '&ap=goprofile', Lang::t('_BACK', 'standard'));
            else
                return getBackUi('index.php?modname=myfiles&op=myfiles&working_area=' . $type, Lang::t('_BACK', 'standard'));
        if ($back_my_friend)
            return getBackUi('index.php?modname=myfriends&op=myfriends', Lang::t('_BACK', 'standard'));
        return false;
    }

    public function show() {
        if (!defined("LMS")) {
            checkRole('/lms/course/public/profile/view', false);
        } else {
            checkPerm('view', false, 'profile', 'lms');
        }

        require_once(_lms_ . '/lib/lib.lms_user_profile.php');

        $id_user = Docebo::user()->getIdST();
        $profile = new LmsUserProfile($id_user);
        $profile->init('profile', 'framework', 'r=lms/profile/show'/* &id_user'.(int)$id_user */, 'ap'); //'modname=profile&op=profile&id_user='.$id_user

//        $access_career = $ma->currentCanAccessObj('career');
        $user =  new UserProfile();
        $user->_lang = & DoceboLanguage::createInstance('profile', 'framework');
        
        $_check = false;
        if (!defined("LMS")) {
            $_check = checkRole('/lms/course/public/profile/mod', true);
        } else {
            $_check = checkPerm('mod', true, 'profile', 'lms');
        }
        if ($_check)
            $profile->enableEditMode();

        //view part
        if (Get::sett('profile_only_pwd') == 'on') {
            echo '<div id="profile_fix">';
            echo $profile->getUserInfo();

            require_once($GLOBALS['where_lms'] . '/lib/lib.middlearea.php');
            require_once($GLOBALS['where_lms'] . '/modules/course/course.php');
            $ma = new Man_MiddleArea();
            $access_career = $ma->currentCanAccessObj('career');

            //if($this->acl_man->relativeId($this->user_info[ACL_INFO_USERID]) == 'alberto' && $access_career) {
            if ($access_career) {

                $url = $this->_url_man;
                $course_stats = userCourseList($url, false, false);  //TODO:  review this call . use course list to compute carreer

                $base_url = 'index.php?r=' . _after_login_ . '&amp;filter=';
                $end = 0;
                if (isset($course_stats['with_ustatus'][_CUS_END]) && $course_stats['with_ustatus'][_CUS_END] != 0) {
                    $end = $course_stats['with_ustatus'][_CUS_END];
                }

                $html .= '<div class="inline_block" style="margin:0px;width:73%">'
                        . '<h2 class="heading">' . $user->_lang->def('_CAREER') . '</h2>'
                        . '<div class="content">'
                        . '<div class="course_stat">'
                        . '<table summary="">'
                        . '<tr><th scope="row">' . $user->_lang->def('_TOTAL_COURSE') . ' :</th><td>' . ($course_stats['total'] - $end) . '</td></tr>'
                        . ( isset($course_stats['with_ustatus'][_CUS_END]) && $course_stats['with_ustatus'][_CUS_END] != 0 ? '<tr><th scope="row">' . $user->_lang->def('_COURSE_END') . ' :</th><td>' . $course_stats['with_ustatus'][_CUS_END] . '</td></tr>' : '' )
                        . ( isset($course_stats['expiring']) && $course_stats['expiring'] != 0 ? '<tr><th scope="row">' . $user->_lang->def('_COURSE_EXPIRING') . ' :</th><td>' . $course_stats['expiring'] . '</td></tr>' : '' );

                if (count($course_stats['with_ulevel']) > 1) {

                    require_once($GLOBALS['where_lms'] . '/lib/lib.levels.php');
                    $lvl = CourseLevel::getLevels();
                    foreach ($course_stats['with_ulevel'] as $lvl_num => $quantity) {

//                        $html .= ''
//                                . '<tr><th scope="row">' . str_replace('[level]', $lvl[$lvl_num], $this->_lang->def('_COURSE_AS')) . ' :</th><td>' . $quantity . '</td></tr>';
                    } //end foreach
                }
                $query = "SELECT c.idMetaCertificate, m.idCertificate"
                        . " FROM " . $GLOBALS['prefix_lms'] . "_certificate_meta_course as c"
                        . " JOIN " . $GLOBALS['prefix_lms'] . "_certificate_meta as m ON c.idMetaCertificate = m.idMetaCertificate"
                        . " WHERE c.idUser = '" . getLogUserId() . "'"
                        . " GROUP BY c.idMetaCertificate"
                        . " ORDER BY m.title, m.description";

                $result = sql_query($query);

                $num_meta_cert = mysql_num_rows($result);

                while (list($id_meta, $id_certificate) = sql_fetch_row($result)) {
                    $query_released = "SELECT on_date"
                            . " FROM " . $GLOBALS['prefix_lms'] . "_certificate_meta_assign"
                            . " WHERE idUser = '" . getLogUserId() . "'"
                            . " AND idMetaCertificate = '" . $id_meta . "'";

                    $result_released = sql_query($query_released);

                    $query = "SELECT user_release"
                            . " FROM " . $GLOBALS['prefix_lms'] . "_certificate"
                            . " WHERE id_certificate = '" . $id_certificate . "'";

                    list($user_release) = sql_fetch_row(sql_query($query));

                    if (mysql_num_rows($result_released)) {
                        
                    } elseif ($user_release == 0)
                        $num_meta_cert--;
                    else {
                        $query = "SELECT idCourse"
                                . " FROM " . $GLOBALS['prefix_lms'] . "_certificate_meta_course"
                                . " WHERE idUser = '" . getLogUserId() . "'"
                                . " AND idMetaCertificate = '" . $id_meta . "'";

                        $result_int = sql_query($query);
                        $control = true;

                        while (list($id_course) = sql_fetch_row($result_int)) {
                            $query = "SELECT COUNT(*)"
                                    . " FROM " . $GLOBALS['prefix_lms'] . "_courseuser"
                                    . " WHERE idCourse = '" . $id_course . "'"
                                    . " AND idUser = '" . getLogUserId() . "'"
                                    . " AND status = '" . _CUS_END . "'";


                            list($number) = sql_fetch_row(sql_query($query));

                            if (!$number)
                                $control = false;
                        }

                        if (!$control)
                            $num_meta_cert--;
                    }
                }

                $tot_cert = $num_meta_cert + $course_stats['cert_relesable'];

                $html .= ''
                        . ( isset($course_stats['cert_relesable']) && $tot_cert != 0 ? '<tr><th scope="row">' . $user->_lang->def('_CERT_RELESABLE') . ' :</th><td><a href="index.php?modname=mycertificate&amp;op=mycertificate">' . $tot_cert . '</a></td></tr>' : '' )
                        . ( $pendent != 0 ? '<tr><th scope="row">' . $user->_lang->def('_FRIEND_PENDENT') . ' :</th><td><a href="index.php?modname=myfriends&amp;op=myfriends">' . $pendent . '</a></td></tr>' : '' )
                        . '</table>'
                        . '</div>'
                        . '</div>'
                        . '</div>';
            }
            echo $html;
            echo '</div>';
            echo $profile->getTitleArea();
            echo $profile->getHead();
            echo $profile->performAction(false, 'mod_password');
            echo $this->_profileBackUrl();
            echo $profile->getFooter();
        } else {
            echo '<div id="profile_fix">';
            echo $profile->getUserInfo();
            echo '</div>';
            echo $profile->getTitleArea();
            echo $profile->getHead();
            echo $profile->performAction();
            echo $this->_profileBackUrl();
            echo $profile->getFooter();
        }
    }

    function renewalpwd() {
        require_once(_base_ . '/lib/lib.usermanager.php');
        $user_manager = new UserManager();

        $_title = "";
        $_error_message = "";
        $_content = "";

        $url = 'index.php?r=lms/profile/renewalpwd'; //'index.php?modname=profile&amp;op=renewalpwd'

        if ($user_manager->clickSaveElapsed()) {
            $error = $user_manager->saveElapsedPassword();
            if ($error['error'] == true) {
                $res = Docebo::user()->isPasswordElapsed();

                if ($res == 2)
                    $_title = getTitleArea(Lang::t('_CHANGEPASSWORD', 'profile'));
                else
                    $_title = getTitleArea(Lang::t('_TITLE_CHANGE', 'profile'));

                $_error_message = $error['msg'];
                $_content = $user_manager->getElapsedPassword($url);
            } else {
                unset($_SESSION['must_renew_pwd']);
                //Util::jump_to('index.php?r=lms/profile/show');
                Util::jump_to('index.php');
            }
        } else {
            $_SESSION['must_renew_pwd'] = 1;
            $res = Docebo::user()->isPasswordElapsed();
            if ($res == 2)
                $_title = getTitleArea(Lang::t('_CHANGEPASSWORD', 'profile'));
            else
                $_title = getTitleArea(Lang::t('_TITLE_CHANGE', 'profile'));
            $_content = $user_manager->getElapsedPassword($url);
        }

        //view part
        echo $_title . '<div class="std_block">' . $_error_message . $_content . '</div>';
    }

}

?>
