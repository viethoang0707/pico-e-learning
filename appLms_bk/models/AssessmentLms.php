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

class AssessmentLms extends Model {

	protected $_t_order = false;

	public function  __construct() {
	}

	/**
	 * This function return the correct order to use when you wish to diplay an
	 * assessment list for the user.
	 * @param <array> $t_name the table name to use as a prefix for the field, if false is passed no prefix will e used
	 *							we need a prefix for the course user rows and a prefix for the course table
	 *							array('u', 'c')
	 * @return <string> the order to use in a ORDER BY clausole
	 */
	protected function _resolveOrder($t_name = array('', '')) {
		// read order for the course from database
		if($this->_t_order == false) {

			$t_order = Get::sett('tablist_mycourses', false);
			if($t_order != false) {

				$arr_order_course = explode(',', $t_order);
				$arr_temp = array();
				foreach($arr_order_course as $key=>$value) {

					switch ($value) {
						case 'status': $arr_temp[] = ' ?u.status '; break;
						case 'code': $arr_temp[] = ' ?c.code '; break;
						case 'name': $arr_temp[] = ' ?c.name '; break;
					}
				}
				$t_order = implode(', ', $arr_temp);
			} else {

				$t_order = '?u.status, ?c.name';
			}
			// save a class copy of the resolved list
			$this->_t_order = $t_order;
		}
		foreach($t_name as $key=>$value) {
			if($value != '') $t_name[$key] = $value.'.';
		}
		return str_replace(array('?u.', '?c.'), $t_name ,$this->_t_order);
	}

	public function compileWhere($conditions, $params) {

		if(!is_array($conditions)) return "1";

		$where = array();
		$find = array_keys($params);
		foreach($conditions as $key=>$value) {

			$where[] = str_replace($find, $params, $value);
		}
		return implode(" AND ", $where);
	}

	public function findAll($conditions, $params)
	{
		$db = DbConn::getInstance();
		$query = $db->query(
			"SELECT c.idCourse, c.course_type, c.idCategory, c.code, c.name, c.description, c.difficult, c.status AS course_status, c.course_edition, "
			."	c.max_num_subscribe, c.create_date, "
			."	c.direct_play, c.img_othermaterial, c.course_demo, c.use_logo_in_courselist, c.img_course, c.lang_code, "
			."	c.course_vote, "
			."	c.date_begin, c.date_end, c.valid_time, c.show_result, c.userStatusOp,"

			."	cu.status AS user_status, cu.level, cu.date_inscr, cu.date_first_access, cu.date_complete, cu.waiting"

			." FROM %lms_course AS c "
			." JOIN %lms_courseuser AS cu ON (c.idCourse = cu.idCourse) "
			." JOIN %lms_assessment_user AS a ON (a.id_assessment = c.idCourse AND a.id_user = cu.idUser)"

			." WHERE ".$this->compileWhere($conditions, $params)
			.(!isset($conditions['cu.status']) ? '' : " AND cu.status IN ("._CUS_SUBSCRIBED.", "._CUS_BEGIN.")")
			." ORDER BY ".$this->_resolveOrder(array('cu', 'c'))
		);
		
		$result = array();
		$courses = array();
		while($data = $db->fetch_assoc($query)) {

			$data['enrolled'] = 0;
			$data['waiting'] = 0;
			$courses[] = $data['idCourse'];
			$result[$data['idCourse']] = $data;
		}
		// find subscriptions
		$re_enrolled = $db->query(
			"SELECT c.idCourse, COUNT(*) as numof_associated, SUM(waiting) as numof_waiting"
			." FROM %lms_course AS c "
			." JOIN %lms_courseuser AS cu ON (c.idCourse = cu.idCourse) "
			." WHERE c.idCourse IN (".implode(',', $courses).") "
			." GROUP BY c.idCourse"
		);
		while($data = $db->fetch_assoc($re_enrolled)) {

			$result[$data['idCourse']]['enrolled'] = $data['numof_associated'] - $data['numof_waiting'];
			$result[$data['idCourse']]['waiting'] = $data['numof_waiting'];
		}
		return $result;
	}

}