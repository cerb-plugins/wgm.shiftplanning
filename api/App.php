<?php
if(class_exists('Extension_PageMenuItem')):
class WgmShiftPlanning_SetupPluginsMenuItem extends Extension_PageMenuItem {
	const POINT = 'wgmshiftplanning.setup.menu.plugins.shiftplanning';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('extension', $this);
		$tpl->display('devblocks:wgm.shiftplanning::setup/menu_item.tpl');
	}
};
endif;

if(class_exists('Extension_PageSection')):
class WgmShiftPlanning_SetupSection extends Extension_PageSection {
	const ID = 'wgmshiftplanning.setup.shiftplanning';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();

		$visit = CerberusApplication::getVisit();
		$visit->set(ChConfigurationPage::ID, 'shiftplanning');
		
		$tpl->assign('workers', DAO_Worker::getAll());
		
		$params = array(
			'api_key' => DevblocksPlatform::getPluginSetting('wgm.shiftplanning','api_key',''),
			'sp_user' => DevblocksPlatform::getPluginSetting('wgm.shiftplanning','sp_user',''),
			'sp_password' => DevblocksPlatform::getPluginSetting('wgm.shiftplanning','sp_password',''),
			'api_employees' => @json_decode(DevblocksPlatform::getPluginSetting('wgm.shiftplanning', 'api.employees.json', '[]'), true),
			'employees_to_workers' => @json_decode(DevblocksPlatform::getPluginSetting('wgm.shiftplanning', 'map.employees_to_workers.json', '[]'), true),
		);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:wgm.shiftplanning::setup/index.tpl');
	}
	
	function saveJsonAction() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(WgmShiftPlanning_API::_CACHE_EMPLOYEES_AVAILABLE);
		
		try {
			@$api_key = DevblocksPlatform::importGPC($_REQUEST['api_key'],'string','');
			@$sp_user = DevblocksPlatform::importGPC($_REQUEST['sp_user'],'string','');
			@$sp_password = DevblocksPlatform::importGPC($_REQUEST['sp_password'],'string','');
			
			if(empty($api_key))
				throw new Exception("The API key is required.");
			
			if(empty($sp_user))
				throw new Exception("The user is required.");
			
			if(empty($sp_password))
				throw new Exception("The password is required.");

			$shiftplanning = WgmShiftPlanning_API::getInstance();
			$response = $shiftplanning->getApiConfig();
			
			if(!isset($response['status']) || $response['status'] != 1)
				throw new Exception($response['data']);

			DevblocksPlatform::setPluginSetting('wgm.shiftplanning','api_key',$api_key);
			DevblocksPlatform::setPluginSetting('wgm.shiftplanning','sp_user',$sp_user);
			DevblocksPlatform::setPluginSetting('wgm.shiftplanning','sp_password',$sp_password);

			echo json_encode(array('status'=>true,'message'=>'Saved!'));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
	
	function syncEmployeesAction() {
		$tpl = DevblocksPlatform::getTemplateService();
		$shiftplanning = WgmShiftPlanning_API::getInstance();
		
		// Workers
		
		$tpl->assign('workers', DAO_Worker::getAll());
		
		// Employees
		
		$employees = array();
		
		$response = $shiftplanning->getEmployees();
		
		if(isset($response['status']) && 1==$response['status'])
			if(is_array($response['data']))
				$employees = $response['data'];
		
		DevblocksPlatform::setPluginSetting('wgm.shiftplanning', 'api.employees.json', json_encode($employees));
		
		// Employees
		
		$schedules = array();
		
		$response = $shiftplanning->getSchedules();
		
		if(isset($response['status']) && 1==$response['status'])
			if(is_array($response['data']))
				$schedules = $response['data'];
		
		DevblocksPlatform::setPluginSetting('wgm.shiftplanning', 'api.schedules.json', json_encode($schedules));
		
		// Params
		
		$params = array(
			'api_employees' => $employees,
			'employees_to_workers' => @json_decode(DevblocksPlatform::getPluginSetting('wgm.shiftplanning', 'map.employees_to_workers.json', '[]'), true),
		);
		
		$tpl->assign('params', $params);
		
		// Template
		
		$tpl->display('devblocks:wgm.shiftplanning::setup/employee_to_worker.tpl');
	}
	
	function saveEmployeesJsonAction() {
		$cache = DevblocksPlatform::getCacheService();
		$cache->remove(WgmShiftPlanning_API::_CACHE_EMPLOYEES_AVAILABLE);
		
		try {
			@$employee_ids = DevblocksPlatform::importGPC($_REQUEST['employee_ids'],'array',array());
			@$worker_ids = DevblocksPlatform::importGPC($_REQUEST['worker_ids'],'array',array());
			
			if(!(is_array($employee_ids) && is_array($worker_ids) && count($employee_ids) == count($worker_ids)))
				throw new Exception("Invalid parameters.");
			
			$employees_to_workers = array();
			
			foreach($employee_ids as $idx => $employee_id) {
				if(isset($worker_ids[$idx]) && !empty($worker_ids[$idx]))
					$employees_to_workers[$employee_id] = $worker_ids[$idx];
			}
			
			DevblocksPlatform::setPluginSetting('wgm.shiftplanning','map.employees_to_workers.json',json_encode($employees_to_workers));

			echo json_encode(array('status'=>true,'message'=>'Saved!'));
			return;
			
		} catch (Exception $e) {
			echo json_encode(array('status'=>false,'error'=>$e->getMessage()));
			return;
		}
	}
};
endif;

if(class_exists('Extension_DevblocksEventAction')):
class WgmShiftPlanning_EventActionGetAvailableWorkers extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);

		// Schedules
		
		@$schedules = json_decode(DevblocksPlatform::getPluginSetting('wgm.shiftplanning', 'api.schedules.json', '[]'), true);
		DevblocksPlatform::sortObjects($schedules, '[name]');
		$tpl->assign('schedules', $schedules);
		
		// Variables
		
		$variables_workers = array();
		foreach($trigger->variables as $variable) {
			if($variable['type'] == 'ctx_' . CerberusContexts::CONTEXT_WORKER)
				$variables_workers[$variable['key']] = $variable;
		}
		$tpl->assign('variables_workers', $variables_workers);
		
		// Templates
		
		$tpl->display('devblocks:wgm.shiftplanning::events/action_get_workers_from_shiftplanning.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$shiftplanning = WgmShiftPlanning_API::getInstance();
		
		$out = '';
		
		@$schedules = json_decode(DevblocksPlatform::getPluginSetting('wgm.shiftplanning', 'api.schedules.json', '[]'), true);
		@$employees_to_workers = json_decode(DevblocksPlatform::getPluginSetting('wgm.shiftplanning', 'map.employees_to_workers.json', '[]'), true);
		
		@$schedule_id = $params['schedule_id'];
		$employees_available = $shiftplanning->getEmployeesAvailableNow($schedule_id);
		
		if(!empty($schedule_id) && isset($schedules[$schedule_id]))
			$out .= sprintf("Schedule: %s \n\n", $schedules[$schedule_id]['name']);
		
		$worker_contexts = array();
		
		if(is_array($employees_available))
		foreach($employees_available as $employee_id => $employee_name) {
			if(isset($employees_to_workers[$employee_id])) {
				$worker_id = $employees_to_workers[$employee_id];
				$labels = array();
				$values = array();
				CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $labels, $values, null, true);
				$worker_contexts[$worker_id] = $values;
				
				$out .= sprintf(" * %s \n", $values['full_name']);
			}
		}

		// [TODO] Allow custom placeholders?
		@$var_key = $params['var_key'];
		
		if(!empty($var_key))
			$dict->$var_key = $worker_contexts;
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$shiftplanning = WgmShiftPlanning_API::getInstance();
		
		@$schedules = json_decode(DevblocksPlatform::getPluginSetting('wgm.shiftplanning', 'api.schedules.json', '[]'), true);
		@$employees_to_workers = json_decode(DevblocksPlatform::getPluginSetting('wgm.shiftplanning', 'map.employees_to_workers.json', '[]'), true);
		
		@$schedule_id = $params['schedule_id'];
		$employees_available = $shiftplanning->getEmployeesAvailableNow($schedule_id);
		
		$worker_contexts = array();
		
		if(is_array($employees_available))
		foreach($employees_available as $employee_id => $employee_name) {
			if(isset($employees_to_workers[$employee_id])) {
				$worker_id = $employees_to_workers[$employee_id];
				$labels = array();
				$values = array();
				CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $worker_id, $labels, $values, null, true);
				$worker_contexts[$worker_id] = $values;
			}
		}

		@$var_key = $params['var_key'];
		
		if(!empty($var_key))
			$dict->$var_key = $worker_contexts;
	}
};
endif;

class WgmShiftPlanning_API {
	private static $_instance = null;
	private $_api_key = '';
	private $_user = '';
	private $_password = '';
	private $_token = '';
	private $_errors = array();
	
	const _CACHE_EMPLOYEES_AVAILABLE = 'wgm_shiftplanning_api_shifts_today';
	
	/**
	 * @return WgmShiftPlanning_API
	 */
	public static function getInstance() {
		if(null == self::$_instance) {
			self::$_instance = new WgmShiftPlanning_API();
		}
		
		return self::$_instance;
	}
	
	private function __construct() {
		$api_key = DevblocksPlatform::getPluginSetting('wgm.shiftplanning','api_key','');
		$user = DevblocksPlatform::getPluginSetting('wgm.shiftplanning','sp_user','');
		$password = DevblocksPlatform::getPluginSetting('wgm.shiftplanning','sp_password','');
		
		$this->setAuth($api_key, $user, $password);
	}

	public function setAuth($api_key, $user, $password) {
		$this->_api_key = $api_key;
		$this->_user = $user;
		$this->_password = $password;
		
		// [TODO] Persist a token if it's not set or invalid
	}
	
	private function _getToken() {
		// [TODO] Cache token?
		
		if(empty($this->_token))
			$this->login();
			
		return $this->_token;
	}
	
	function getApiConfig() {
		$json = array(
			'key' => $this->_api_key,
			'request' => array(
				'module' => 'api.config',
				//'method' => 'GET',
			),
		);
		
		return $this->_postJson(null, $json);
	}
	
	function login() {
		$json = array(
			'key' => $this->_api_key,
			'output' => 'json',
			'request' => array(
				'module' => 'staff.login',
				'method' => 'GET',
				'username' => $this->_user,
				'password' => $this->_password,
			),
		);

		$response = $this->_postJson(null, $json);
		
		if(!isset($response['status']) || empty($response['status']))
			return false;
		
		if($response['status'] && isset($response['token']))
			$this->_token = $response['token'];
		
		return $response;
	}
	
	function getEmployees() {
		$json = array(
			'token' => $this->_getToken(),
			'output' => 'json',
			'request' => array(
				'module' => 'staff.employees',
				'method' => 'GET',
				'disabled' => 0,
				'inactive' => 0,
			),
		);
		
		return $this->_postJson(null, $json);
	}
	
	function getSchedules() {
		$json = array(
			'token' => $this->_getToken(),
			'output' => 'json',
			'request' => array(
				'module' => 'schedule.schedules',
				'method' => 'GET',
			),
		);
		
		return $this->_postJson(null, $json);
	}
	
	function getScheduleShifts($start_date, $end_date) {
		$json = array(
			'token' => $this->_getToken(),
			'output' => 'json',
			'request' => array(
				'module' => 'schedule.shifts',
				'method' => 'GET',
				'start_date' => $start_date,
				'end_date' => $end_date,
				'mode' => 'overview', // schedule
				//'schedule' => '277558',
			),
		);
		
		return $this->_postJson(null, $json);
	}
	
	function getEmployeesAvailableNow($schedule_id=null, $nocache=false) {
		$cache = DevblocksPlatform::getCacheService();
		
		if($nocache || false == ($response = $cache->load(self::_CACHE_EMPLOYEES_AVAILABLE))) {
			$response = $this->getScheduleShifts(date('Y-m-d', strtotime('-1 day')), date('Y-m-d', strtotime('+1 day')));
			
			if($response['status'] && 1 == $response['status'])
				$cache->save($response, self::_CACHE_EMPLOYEES_AVAILABLE, null, 1800); // 30 min
		}
		
		if(!isset($response['status']) || empty($response['status']) || !isset($response['data']))
			return false;
		
		$employees = array();
		
		if(is_array($response['data']))
		foreach($response['data'] as $shift) {
			if(!empty($schedule_id) && $schedule_id != $shift['schedule'])
				continue;
		
			if(time() >= $shift['start_date']['timestamp'] && time() <= $shift['end_date']['timestamp']) {
				foreach($shift['employees'] as $employee) {
					$employees[$employee['id']] = $employee['name'];
				}
			}
		}
		
		return $employees;
	}
	
	private function _postJson($params=array(), $json=null) {
		$url = 'https://www.shiftplanning.com/api/';
		
		$ch = curl_init($url);
		
		$headers = array();
		
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array('data' => json_encode($json) ));
		
		$out = curl_exec($ch);
		$info = curl_getinfo($ch);
		
		// [TODO] This can fail without HTTPS
		
		if(curl_errno($ch)) {
			$this->_errors = array(curl_error($ch));
			$json = false;
		} elseif(false == ($json = json_decode($out, true))) {
			$this->_errors = array('Failed to decode response.');
			$json = false;
		} else {
			$this->_errors = array();
		}
		
		curl_close($ch);
		return $json;
	}
};