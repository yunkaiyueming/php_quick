<?php

class m_demo extends model {
	
	private $tbname = 'big_apps_set';

	public function __construct() {
	}

	public function add($service_info) {
		try {
			$ret = db::i()->insert_row($this->tbname, $service_info);
			return $ret;
		} catch (ErrorException $e) {
			$log = db::i($this->zoneid)->last_query;
			$log .= '|' . $e->getMessage();
			$this->log($log);
		}

		return false;
	}

	public function getone($card) {
		try {
			return db::i()->once_query("select * from {$this->tbname} where id = :id", array('id' => (string) $card));
		} catch (ErrorException $e) {
			$log = db::i()->last_query;
			$log .= '|' . $e->getMessage();
			$this->log($log, '_statusfaild');
		}

		return false;
	}

	public function updateone($service_info) {
		try {
			return db::i()->update_row($this->tbname, $service_info, array("id"));
		} catch (ErrorException $e) {
			$log = db::i()->last_query;
			$log .= '|' . $e->getMessage();
			$this->log($log);
		}

		return false;
	}

	public function getall() {
		$sql = "select * from {$this->tbname}";
		$res = db::i()->query($sql);
		return $res;
	}

	public function getlimitpage($page, $s) {
		$ts = time();
		if ($s == 0) {
			$sql = "select id,ctype from {$this->tbname} where status = 0 and et > :ts";
		} else {
			$sql = "select * from {$this->tbname} where status = 1";
		}
		$res = db::i()->page_query($sql, array('ts' => $ts), 80, $page);

		return $res;
	}

	public function delete() {
		$expire = time() - 5184000;
		$sql = "delete from {$this->tbname} where uid != 0 and status = 1 and zoneid > 0 and et < {$expire}";
		db::i()->query($sql);
	}

	public function log($msg) {
		func::log($msg);
	}

}
