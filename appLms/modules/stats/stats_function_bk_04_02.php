<?php

function convertCSV($csv) {
    $csv = chr(255) . chr(254) . mb_convert_encoding($csv, "UTF-16LE", "UTF-8");
//    $csv = utf8_decode($csv);
    return $csv;
}

function getAllAnswerOfCourse($idUser, $idCourse) {
    $answer = array();
    $query = "SELECT track.idTrack FROM learning_testtrack AS track JOIN learning_courseuser AS user ON track.idUser = user.idUser AND track.score_status = 'valid' AND track.idUser = '" . $idUser . "' AND user.idCourse = '" . $idCourse . "' JOIN learning_organization AS org ON track.idReference = org.idOrg AND org.idCourse ='" . $idCourse . "'";
    $rs = sql_query($query);
    while (list($track) = sql_fetch_row($rs)) {
        $answer[] = getTestTrackAnswer($track);
    }
    return $answer;
}

function getTestTrackAnswer($idTrack) {
    $query = "SELECT COUNT(*) FROM learning_testtrack_answer WHERE idTrack ='" . $idTrack . "' AND score_assigned != 0 OR manual_assigned !=0";
    list($answer) = sql_fetch_row(sql_query($query));
    return $answer;
}

function getTestTrack($idCourse) {
    $test_track = array();

    return $test_track;
}

function getMinMarkOfTest($idCourse) {
    $mark = array();
    $test = getAllTestsOfCourse($idCourse);
    $query = "SELECT idOrg FROM  " . $GLOBALS['prefix_lms'] . "_organization WHERE idCourse = '" . $idCourse . "' AND objectType = 'test'";
    $rs = sql_query($query);
    if ($test < 1) {
        list($idOrg) = sql_fetch_row($rs);
        $mark[] = getMinMark($idOrg, $idCourse);
    } else {
        while (list($idOrg) = sql_fetch_row($rs)) {
            $mark[] = getMinMark($idOrg, $idCourse);
        }
    }
    return $mark;
}

function getMaxMarkOfTest($idCourse) {
    $mark = array();
    $test = getAllTestsOfCourse($idCourse);
    $query = "SELECT idOrg FROM  " . $GLOBALS['prefix_lms'] . "_organization WHERE idCourse = '" . $idCourse . "' AND objectType = 'test'";
    $rs = sql_query($query);
    if ($test < 1) {
        list($idOrg) = sql_fetch_row($rs);
        $mark[] = getMaxMark($idOrg, $idCourse);
    } else {
        while (list($idOrg) = sql_fetch_row($rs)) {
            $mark[] = getMaxMark($idOrg, $idCourse);
        }
    }
    return $mark;
}

function getMaxMark($idOrg, $idCourse) {
    $array = getMarkOfTest($idOrg, $idCourse);
    $mark = $array[0];
    foreach ($array as $value) {
        if ($value > $mark) {
            $mark = $value;
        }
    }
    return $mark;
}

function getMinMark($idOrg, $idCourse) {
    $array = getMarkOfTest($idOrg, $idCourse);
    $mark = $array[0];
    foreach ($array as $value) {
        if ($value < $mark) {
            $mark = $value;
        }
    }
    return $mark;
}

function getMarkOfTest($idOrg, $idCourse) {
    $mark = array();
    $query = "SELECT track.score FROM learning_testtrack AS track JOIN learning_courseuser AS user ON track.idUser = user.idUser AND track.score_status = 'valid' AND user.level = '3' AND track.idReference = '" . $idOrg . "' AND user.idCourse = '" . $idCourse . "' GROUP BY track.idTrack";
    $rs = sql_query($query);
    while (list($score) = sql_fetch_row($rs)) {
        if ($score == NULL && $score == '') {
            $score = 0;
        }
        $mark[] = $score;
    }
    return $mark;
}

function getAllTestPassedOfCourse($idCourse) {
    $test = getAllTestsOfCourse($idCourse);
    $count = 0;
    $query = "SELECT idOrg FROM  " . $GLOBALS['prefix_lms'] . "_organization WHERE idCourse = '" . $idCourse . "' AND objectType = 'test'";
    $rs = sql_query($query);
    if ($test < 1) {
        list($idOrg) = sql_fetch_row($rs);
        $count = getTestPassed($idOrg, $idCourse);
    } else {
        while (list($idOrg) = sql_fetch_row($rs)) {
            $count = $count + getTestPassed($idOrg, $idCourse);
        }
    }
    return $count;
}

function getTestPassed($idTest, $idCourse) {
    $query = "SELECT * FROM learning_commontrack AS common JOIN learning_courseuser AS course ON common.idUser = course.idUser AND common.`status` = 'passed' AND course.`level` = 3 AND common.idReference = '" . $idTest . "' AND course.idCourse ='" . $idCourse . "'";
    $rs = sql_query($query);
    $count = 0;
    while (list($total) = sql_fetch_row($rs)) {
        $count = $count + 1;
    }
    return $count;
}

function getAllValidTestOfCourse($idCourse) {
    $test = getAllTestsOfCourse($idCourse);
    $count = 0;
    $query = "SELECT idOrg FROM  " . $GLOBALS['prefix_lms'] . "_organization WHERE idCourse = '" . $idCourse . "' AND objectType = 'test'";
    $rs = sql_query($query);
    if ($test < 1) {
        list($idOrg) = sql_fetch_row($rs);
        $count = getvalidTest($idOrg, $idCourse);
    } else {
        while (list($idOrg) = sql_fetch_row($rs)) {
            $count = $count + getvalidTest($idOrg, $idCourse);
        }
    }
    return $count;
}

function getvalidTest($idTest, $idCourse) {
    $query = "SELECT * FROM learning_courseuser AS course JOIN learning_testtrack AS test ON test.idUser = course.idUser AND course.level = 3 AND test.score_status = 'valid' AND test.idReference = '" . $idTest . "' AND course.idCourse='" . $idCourse . "' GROUP BY test.idUser";
    $rs = sql_query($query);
    $count = 0;
    while (list($total) = sql_fetch_row($rs)) {
        $count = $count + 1;
    }
    return $count;
}

function getAllTestedOfCourse($idCourse) {
    $count_user = getAllStudentOfCourse($idCourse);
    $count_test = getAllTestsOfCourse($idCourse);
    return $count_user * $count_test;
}

function getAllStudentOfCourse($idCourse) {
    $query = "SELECT COUNT(*) FROM learning_courseuser WHERE idCourse = '" . $idCourse . "' AND level = 3";
    $rs = sql_query($query);
    list($count_user) = sql_fetch_row($rs);
    return $count_user;
}

function getAllTestsOfCourse($idCourse) {
    $query = "SELECT COUNT(*) FROM learning_organization WHERE idCourse = '" . $idCourse . "' AND objectType='test'";
    $rs = sql_query($query);
    list($count_test) = sql_fetch_row($rs);
    return $count_test;
}

function getScoreStudentOfCourse($idUser, $idCourse) {
    $answer = array();
    $query = "SELECT * FROM learning_testtrack AS track JOIN learning_courseuser AS user ON track.idUser = user.idUser AND track.score_status = 'valid' AND track.idUser = '" . $idUser . "' AND user.idCourse = '" . $idCourse . "' JOIN learning_organization AS org ON track.idReference = org.idOrg AND org.idCourse ='" . $idCourse . "'";
    $rs = sql_query($query);
    while ($track = sql_fetch_array($rs)) {
        if ($track['score'] == NULL || $track['score'] == '') {
            $track['score'] = 0;
        }
        $answer[] = $track['score'];
    }
    return $answer;
}

function getPartiicipatedCourse($idUser, $idCourse) {
    $course = array();

    $module = new CoursestatsLms();
    $pagination = false;
    $list = $module->getCourseUserStatsList($pagination, $idCourse, $idUser);
    foreach ($list as $values) {
        if ($values->status != NULL || $values->status != "") {
            $course[] = $values->title;
        }
    }
    return $course;
}

function getCourseNotPartiicipated($idUser, $idCourse) {
    $course = array();

    $module = new CoursestatsLms();
    $pagination = false;
    $list = $module->getCourseUserStatsList($pagination, $idCourse, $idUser);
    foreach ($list as $values) {
        if ($values->status == NULL || $values->status == "") {
            $course[] = $values->title;
        }
    }
    return $course;
}

function getMaxMarkOfUser($idUser, $idCourse) {
    $mark = array();
    $test = getAllTestsOfCourse($idCourse);
    $query = $query = "SELECT org.idOrg FROM learning_commontrack AS common JOIN learning_organization AS org ON org.idOrg = common.idReference AND org.idCourse ='" . $idCourse . "' AND common.idUser = '" . $idUser . "' AND common.objectType = 'test'";
    $rs = sql_query($query);
    if ($test < 1) {
        list($idOrg) = sql_fetch_row($rs);
        $mark[] = getMarkOfStudent($idUser, $idOrg, $idCourse);
    } else {
        while (list($idOrg) = sql_fetch_row($rs)) {
            $mark[] = getMarkOfStudent($idUser, $idOrg, $idCourse);
        }
    }
    return $mark;
}

function getMaxMarkUser($idUser, $idCourse) {
    $array = getMaxMarkOfUser($idUser, $idCourse);
    $mark = $array[0];
    foreach ($array as $value) {
        if ($value > $mark) {
            $mark = $value;
        }
    }
    return $mark;
}

function getMinMarkOfUser($idUser, $idCourse) {
    $mark = array();
    $test = getAllTestsOfCourse($idCourse);
    $query = $query = "SELECT org.idOrg FROM learning_commontrack AS common JOIN learning_organization AS org ON org.idOrg = common.idReference AND org.idCourse ='" . $idCourse . "' AND common.idUser = '" . $idUser . "' AND common.objectType = 'test'";
    $rs = sql_query($query);
    if ($test < 1) {
        list($idOrg) = sql_fetch_row($rs);
        $mark[] = getMarkOfStudent($idUser, $idOrg, $idCourse);
    } else {
        while (list($idOrg) = sql_fetch_row($rs)) {
            $mark[] = getMarkOfStudent($idUser, $idOrg, $idCourse);
        }
    }
    return $mark;
}

function getMinMarkUser($idUser, $idCourse) {
    $array = getMinMarkOfUser($idUser, $idCourse);
    $mark = $array[0];
    foreach ($array as $value) {
        if ($value < $mark) {
            $mark = $value;
        }
    }
    return $mark;
}

function getMarkOfStudent($idUser, $idOrg, $idCourse) {
    $query = "SELECT track.score FROM learning_testtrack AS track  WHERE track.score_status = 'valid' AND track.idReference = '" . $idOrg . "' AND track.idUser = '" . $idUser . "' GROUP BY track.idTrack";
    $rs = sql_query($query);
    while (list($score) = sql_fetch_row($rs)) {
        if ($score == NULL && $score == '') {
            $score = 0;
        }
        $mark = $score;
    }
    return $mark;
}

