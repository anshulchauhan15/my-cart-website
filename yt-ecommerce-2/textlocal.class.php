<?php


class Textlocal
{
	const REQUEST_URL = 'https://api.textlocal.in/';
	const REQUEST_TIMEOUT = 60;
	const REQUEST_HANDLER = 'curl';

	private $username;
	private $hash;
	private $apiKey;

	private $errorReporting = false;

	public $errors = array();
	public $warnings = array();

	public $lastRequest = array();


	function __construct($username, $hash, $apiKey = https://test.instamojo.com/@anshulchauhan1808)
	{
		$this->username = $username;
		$this->hash = $hash;
		if ($apiKey) {
			$this->apiKey = $apiKey;
		}

	}

	
	private function _sendRequest($command, $params = array())
	{
		if ($this->apiKey && !empty($this->apiKey)) {
			$params['apiKey'] = $this->apiKey;

		} else {
			$params['hash'] = $this->hash;
		}
		
		$params['username'] = $this->username;

		$this->lastRequest = $params;

		if (self::REQUEST_HANDLER == 'curl')
			$rawResponse = $this->_sendRequestCurl($command, $params);
		else throw new Exception('Invalid request handler.');

		$result = json_decode($rawResponse);
		if (isset($result->errors)) {
			if (count($result->errors) > 0) {
				foreach ($result->errors as $error) {
					switch ($error->code) {
						default:
							throw new Exception($error->message);
					}
				}
			}
		}

		return $result;
	}

	
	private function _sendRequestCurl($command, $params)
	{

		$url = self::REQUEST_URL . $command . '/';

		
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $params,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_TIMEOUT        => self::REQUEST_TIMEOUT
		));

		$rawResponse = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($rawResponse === false) {
			throw new Exception('Failed to connect to the Textlocal service: ' . $error);
		} elseif ($httpCode != 200) {
			throw new Exception('Bad response from the Textlocal service: HTTP code ' . $httpCode);
		}

		return $rawResponse;
	}

	
	private function _sendRequestFopen($command, $params)
	{
		throw new Exception('Unsupported transfer method');
	}

	
	public function getLastRequest()
	{
		return $this->lastRequest;
	}

	

	public function sendSms($numbers, $message, $sender, $sched = null, $test = false, $receiptURL = null, $custom = null, $optouts = false, $simpleReplyService = false)
	{

		if (!is_array($numbers))
			throw new Exception('Invalid $numbers format. Must be an array');
		if (empty($message))
			throw new Exception('Empty message');
		if (empty($sender))
			throw new Exception('Empty sender name');
		if (!is_null($sched) && !is_numeric($sched))
			throw new Exception('Invalid date format. Use numeric epoch format');

		$params = array(
			'message'       => rawurlencode($message),
			'numbers'       => implode(',', $numbers),
			'sender'        => rawurlencode($sender),
			'schedule_time' => $sched,
			'test'          => $test,
			'receipt_url'   => $receiptURL,
			'custom'        => $custom,
			'optouts'       => $optouts,
			'simple_reply'  => $simpleReplyService
		);

		return $this->_sendRequest('send', $params);
	}



	public function sendSmsGroup($groupId, $message, $sender = null, $sched = null, $test = false, $receiptURL = null, $custom = null, $optouts = false, $simpleReplyService = false)
	{

		if (!is_numeric($groupId))
			throw new Exception('Invalid $groupId format. Must be a numeric group ID');
		if (empty($message))
			throw new Exception('Empty message');
		if (empty($sender))
			throw new Exception('Empty sender name');
		if (!is_null($sched) && !is_numeric($sched))
			throw new Exception('Invalid date format. Use numeric epoch format');

		$params = array(
			'message'       => rawurlencode($message),
			'group_id'      => $groupId,
			'sender'        => rawurlencode($sender),
			'schedule_time' => $sched,
			'test'          => $test,
			'receipt_url'   => $receiptURL,
			'custom'        => $custom,
			'optouts'       => $optouts,
			'simple_reply'  => $simpleReplyService
		);

		return $this->_sendRequest('send', $params);
	}


	
	public function sendMms($numbers, $fileSource, $message, $sched = null, $test = false, $optouts = false)
	{

		if (!is_array($numbers))
			throw new Exception('Invalid $numbers format. Must be an array');
		if (empty($message))
			throw new Exception('Empty message');
		if (empty($fileSource))
			throw new Exception('Empty file source');
		if (!is_null($sched) && !is_numeric($sched))
			throw new Exception('Invalid date format. Use numeric epoch format');

		$params = array(
			'message'       => rawurlencode($message),
			'numbers'       => implode(',', $numbers),
			'schedule_time' => $sched,
			'test'          => $test,
			'optouts'       => $optouts
		);

	
		if (is_readable($fileSource))
			$params['file'] = '@' . $fileSource;
		else $params['url'] = $fileSource;

		return $this->_sendRequest('send_mms', $params);
	}


	public function sendMmsGroup($groupId, $fileSource, $message, $sched = null, $test = false, $optouts = false)
	{

		if (!is_numeric($groupId))
			throw new Exception('Invalid $groupId format. Must be a numeric group ID');
		if (empty($message))
			throw new Exception('Empty message');
		if (empty($fileSource))
			throw new Exception('Empty file source');
		if (!is_null($sched) && !is_numeric($sched))
			throw new Exception('Invalid date format. Use numeric epoch format');

		$params = array(
			'message'       => rawurlencode($message),
			'group_id'      => $groupId,
			'schedule_time' => $sched,
			'test'          => $test,
			'optouts'       => $optouts
		);

		/** Local file. POST to service */
		if (is_readable($fileSource))
			$params['file'] = '@' . $fileSource;
		else $params['url'] = $fileSource;

		return $this->_sendRequest('send_mms', $params);
	}


	public function getUsers()
	{
		return $this->_sendRequest('get_users');
	}



	public function transferCredits($user, $credits)
	{

		if (!is_numeric($credits))
			throw new Exception('Invalid credits format');
		if (!is_numeric($user))
			throw new Exception('Invalid user');
		if (empty($user))
			throw new Exception('No user specified');
		if (empty($credits))
			throw new Exception('No credits specified');

		if (is_int($user)) {
			$params = array(
				'user_id' => $user,
				'credits' => $credits
			);
		} else {
			$params = array(
				'user_email' => rawurlencode($user),
				'credits'    => $credits
			);
		}

		return $this->_sendRequest('transfer_credits', $params);
	}

	

	public function getTemplates()
	{
		return $this->_sendRequest('get_templates');
	}


	public function checkKeyword($keyword)
	{

		$params = array('keyword' => $keyword);
		return $this->_sendRequest('check_keyword', $params);
	}


	public function createGroup($name)
	{
		$params = array('name' => $name);
		return $this->_sendRequest('create_group', $params);
	}


	public function getContacts($groupId, $limit, $startPos = 0)
	{

		if (!is_numeric($groupId))
			throw new Exception('Invalid $groupId format. Must be a numeric group ID');
		if (!is_numeric($startPos) || $startPos < 0)
			throw new Exception('Invalid $startPos format. Must be a numeric start position, 0 or above');
		if (!is_numeric($limit) || $limit < 1)
			throw new Exception('Invalid $limit format. Must be a numeric limit value, 1 or above');

		$params = array(
			'group_id' => $groupId,
			'start'    => $startPos,
			'limit'    => $limit
		);
		return $this->_sendRequest('get_contacts', $params);
	}

	
	public function createContacts($numbers, $groupid = '5')
	{
		$params = array("group_id" => $groupid);

		if (is_array($numbers)) {
			$params['numbers'] = implode(',', $numbers);
		} else {
			$params['numbers'] = $numbers;
		}

		return $this->_sendRequest('create_contacts', $params);
	}


	function createContactsBulk($contacts, $groupid = '5')
	{
		// JSON & URL-encode array
		$contacts = rawurlencode(json_encode($contacts));

		$params = array
		("group_id" => $groupid, "contacts" => $contacts);
		return $this->_sendRequest('create_contacts_bulk', $params);
	}

	
	public function getGroups()
	{
		return $this->_sendRequest('get_groups');
	}

	
	public function getMessageStatus($messageid)
	{
		$params = array("message_id" => $messageid);
		return $this->_sendRequest('status_message', $params);
	}

	
	public function getBatchStatus($batchid)
	{
		$params = array("batch_id" => $batchid);
		return $this->_sendRequest('status_batch', $params);
	}


	public function getInboxes()
	{
		return $this->_sendRequest('get_inboxes');
	}

	* @return array|bool|mixed
	 */
	public function getMessages($inbox)
	{
		if (!isset($inbox)) return false;
		$options = array('inbox_id' => $inbox);
		return $this->_sendRequest('get_messages', $options);
	}

	public function getScheduledMessages()
	{
		return $this->_sendRequest('get_scheduled');
	}

	public function deleteGroup($groupid)
	{
		$options = array('group_id' => $groupid);
		return $this->_sendRequest('delete_group', $options);
	}



	public function getAPIMessageHistory($start, $limit, $min_time, $max_time)
	{
		return $this->getHistory('get_history_api', $start, $limit, $min_time, $max_time);
	}

	
	public function getEmailToSMSHistory($start, $limit, $min_time, $max_time)
	{
		return $this->getHistory('get_history_email', $start, $limit, $min_time, $max_time);
	}

/
	public function getGroupMessageHistory($start, $limit, $min_time, $max_time)
	{
		return $this->getHistory('get_history_group', $start, $limit, $min_time, $max_time);
	}

	
	private function getHistory($type, $start, $limit, $min_time, $max_time)
	{
		if (!isset($start) || !isset($limit) || !isset($min_time) || !isset($max_time)) return false;
		$options = array('start' => $start, 'limit' => $limit, 'min_time' => $min_time, 'max_time' => $max_time);
		return $this->_sendRequest($type, $options);
	}

	
	public function getSurveys()
	{
		return $this->_sendRequest('get_surveys');
	}

	
	public function getSurveyDetails()
	{
		$options = array('survey_id' => $surveyid);
		return $this->_sendRequest('get_survey_details');
	}

	
	public function getSurveyResults($surveyid, $start, $end)
	{
		$options = array('survey_id' => $surveyid, 'start_date' => $start, 'end_date' => $end);
		return $this->_sendRequest('get_surveys', $options);
	}


	public function getOptouts($time = null)
	{
		return $this->_sendRequest('get_optouts');
	}
}

;

class Contact
{
	var $number;
	var $first_name;
	var $last_name;
	var $custom1;
	var $custom2;
	var $custom3;

	var $groupID;

	
	function __construct($number, $firstname = '', $lastname = '', $custom1 = '', $custom2 = '', $custom3 = '')
	{
		$this->number = $number;
		$this->first_name = $firstname;
		$this->last_name = $lastname;
		$this->custom1 = $custom1;
		$this->custom2 = $custom2;
		$this->custom3 = $custom3;
	}
}

;

if (!function_exists('json_encode')) {
	function json_encode($a = false)
	{
		if (is_null($a)) return 'null';
		if ($a === false) return 'false';
		if ($a === true) return 'true';
		if (is_scalar($a)) {
			if (is_float($a)) {
			
				return floatval(str_replace(",", ".", strval($a)));
			}

			if (is_string($a)) {
				static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
				return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
			} else
				return $a;
		}
		$isList = true;
		for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
			if (key($a) !== $i) {
				$isList = false;
				break;
			}
		}
		$result = array();
		if ($isList) {
			foreach ($a as $v) $result[] = json_encode($v);
			return '[' . join(',', $result) . ']';
		} else {
			foreach ($a as $k => $v) $result[] = json_encode($k) . ':' . json_encode($v);
			return '{' . join(',', $result) . '}';
		}
	}
}


