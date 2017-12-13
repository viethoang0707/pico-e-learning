<?php

Class Lang {

	static protected $_loaded = false;

	private function  __construct() {
		self::init();
	}

	public static function init() {
		require_once(_installer_.'/lang/'.self::getSelLang().'.php');
	}

	public static function getSelLang() {
		return $_SESSION['sel_lang'];
	}

	public static function t($keyword) {
		if (!self::$_loaded) self::init();

		if (defined($keyword)) {
			return constant($keyword);
		}
		else {
			return '<span style="font-weight: bold;color:red;">'.$keyword.'</span>';
			//return ucfirst(trim(strtolower(str_replace('_', ' ', $keyword))));
		}
	}

	static public function direction($key="code") {

		return 'ltr';
	}

	static public function getLanguageList($key="code") {
		// key can be "code" or "language"
		$res=array();
		if ($key == "code") {
			$res["vi"] = "vietnamese";
			$res["en"] = "english";
			
		} else if ($key == "language") {
			$res["vietnamese"] = "vietnamese";
			$res["english"] = "english";
			
		}
		return $res;
	}


	public static function setLanguage() {
		$lang =Get::gReq('set_lang', DOTY_STRING, '');
		if (!empty($lang)) {
			$_SESSION['sel_lang']=$lang;
			self::init();
			StepManager::loadCurrentStep();
			ob_clean();
			$res =array();
			$res['intro']=Lang::t('_INSTALLER_INTRO_TEXT');
			$res['title']=Lang::t('_INTRODUCTION');
			$res['btn']=Lang::t('_NEXT').' &raquo;';
			require_once(_base_.'/lib/lib.json.php');
			$json = new Services_JSON();
			echo $json->encode($res);
			session_write_close();
			die();
		}
		if (!isset($_SESSION['sel_lang'])) {
			$_SESSION['sel_lang']='vietnamese';
		}
	}

}

?>