<?php
/**
 * This is the User Control Panel Object.
 *
 * Copyright (C) 2014 Schmooze Com, INC
 */
namespace UCP\Modules;
use \UCP\Modules as Modules;

class Cel extends Modules{
	protected $module = 'Cel';
	private $limit = 15;
	private $break = 5;

	public function __construct($Modules) {
		$this->Modules = $Modules;
		$this->cel = $this->UCP->FreePBX->Cel;
		$this->user = $this->UCP->User->getUser();

		if($this->UCP->Session->isMobile || $this->UCP->Session->isTablet) {
			$this->limit = 7;
		}
	}

	/**
	* Setup Menu Items for display in UCP
	*/
	public function getMenuItems() {
		$extensions = $this->UCP->getSetting($this->user['username'],'Cel','assigned');
		$menu = array();
		if(!empty($extensions)) {
			$menu = array(
				"rawname" => "cel",
				"name" => _("Call Event Logs"),
				"badge" => false
			);
			foreach($extensions as $e) {
				$data = $this->UCP->FreePBX->Core->getDevice($e);
				if(empty($data) || empty($data['description'])) {
					$data = $this->UCP->FreePBX->Core->getUser($e);
					$name = $data['name'];
				} else {
					$name = $data['description'];
				}
				$menu["menu"][] = array(
					"rawname" => $e,
					"name" => $e . (!empty($name) ? " - " . $name : ""),
					"badge" => false
				);
			}
		}
		return !empty($menu["menu"]) ? $menu : array();
	}

	function getDisplay() {
		$page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$ext = !empty($_REQUEST['sub']) ? $_REQUEST['sub'] : '';
		if(!$this->_checkExtension($ext)) {
			return _('Not Authorized');
		}

		$html = '';

		$displayvars = array(
			'ext' => $ext,
		);

		$link = '?display=dashboard&mod=cel&sub='.$ext.'&view=history';

		$searchparams = array(
			'datefrom',
			'dateto',
			'callerid',
			'exten',
			'application'
		);
		foreach ($searchparams as $param) {
			if (isset($_REQUEST[$param])) {
				$displayvars[$param] = $_REQUEST[$param];
				$filters[$param] = $_REQUEST[$param];
				$link.= "&" . $param . "=" . $_REQUEST[$param];
			}
		}

		$calls = $this->cel->getCalls($filters, $ext);
		usort($calls, function($a, $b) {
			return $b['starttime']->format('U') - $a['starttime']->format('U');
		});

		$count = 0;
		foreach ($calls as $uniqueid => $call) {
			$count++;

			if ($count > (($page - 1) * $this->limit) && $count <= ($page * $this->limit)) {
				$paginatedcalls[$uniqueid] = $call;
			}
		}

		$displayvars['calls'] = $paginatedcalls;

		$totalPages = ceil(count($calls) / $this->limit);

		$displayvars['pagnation'] = $this->UCP->Template->generatePagnation($totalPages, $page, $link, $this->break);

		$html .= $this->load_view(__DIR__.'/views/view.php',$displayvars);
		if ($displayvars['calls']) {
			$html .= $this->load_view(__DIR__.'/views/results.php',$displayvars);
		}

		return $html;
	}

	private function _checkExtension($extension) {
		$extensions = $this->UCP->getSetting($this->user['username'],'Cel','assigned');
		return in_array($extension,$extensions);
	}
}
