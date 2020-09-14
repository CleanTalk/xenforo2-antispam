<?php

namespace CleanTalk\ApbctXF2;

require_once \XF::getRootDirectory().'/src/addons/CleanTalk/lib/Cleantalk/Antispam/SFW.php';

/*
 * CleanTalk SpamFireWall Bitrix class
 * author Cleantalk team (welcome@cleantalk.org)
 * copyright (C) 2014 CleanTalk team (http://cleantalk.org)
 * license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 * see https://github.com/CleanTalk/php-antispam
*/

class SFW extends \Cleantalk\Antispam\SFW
{
	private $query;
	public function __construct($api_key) {
		parent::__construct($api_key, \XF::db(), "xf_");
	}

	protected function universal_query($query) {
		$this->query = $query;
		$this->db_query = $this->db->query($query);
	}

	protected function universal_fetch() {
		return $this->db->fetchRow($this->query);
	}
	
	protected function universal_fetch_all() {
		return $this->db->fetchAll($this->query);
	}
}
