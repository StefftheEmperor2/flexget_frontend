<?php

/**
 * File for Class Flexget_Api
 *
 * @author     Stefan Bischoff
 * @version    $Id:$ 2020-03-31
 * @package
 * @subpackage
 *
 */

/**
 * Flexget_Api
 *
 * @author     Stefan Bischoff
 * @version    $Id:$ 2020-03-31
 * @package
 * @subpackage
 **/
class Flexget_Api
{
	protected $url;
	protected $username;
	protected $password;
	protected $session_id;
	/**
	 * @return mixed
	 */
	public function get_url()
	{
		return $this->url;
	}

	/**
	 * @param mixed $url
	 * @return Flexget_Api
	 */
	public function set_url($url)
	{
		$this->url = $url;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function get_username()
	{
		return $this->username;
	}

	/**
	 * @param mixed $username
	 * @return Flexget_Api
	 */
	public function set_username($username)
	{
		$this->username = $username;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function get_password()
	{
		return $this->password;
	}

	/**
	 * @param mixed $password
	 * @return Flexget_Api
	 */
	public function set_password($password)
	{
		$this->password = $password;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function get_session_id()
	{
		return $this->session_id;
	}

	/**
	 * @param mixed $session_id
	 * @return Flexget_Api
	 */
	public function set_session_id($session_id)
	{
		$this->session_id = $session_id;
		return $this;
	}

	/**
	 * @param $endpoint
	 * @return bool|string|null
	 * @throws Api_Exception
	 */
	public function get($endpoint, $with_login_retry = NULL)
	{
		$response = NULL;
		$api_url = $this->get_url();

		$ch = curl_init($api_url . $endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		$http_header = [];
		$session_id = $this->get_session_id();
		if (isset($session_id))
		{
			curl_setopt($ch, CURLOPT_COOKIE, 'session='.$session_id);
		}

		if (count($http_header))
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
		}
		$raw_response = curl_exec($ch);
		list($headers, $body) = explode("\r\n\r\n", $raw_response);
		$error_code = curl_errno($ch);

		if ($error_code)
		{
			curl_close($ch);
			throw new Api_Curl_Exception(curl_error($ch), $error_code);
		}

		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		$matches = [];
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);
		foreach($matches[1] as $item)
		{
			$cookie = [];
			parse_str($item, $cookie);
			if (isset($cookie['session']))
			{
				$this->set_session_id($cookie['session']);
			}
		}

		$does_match = preg_match('/^Content-Type:\s*(.*)\r\n/mi', $headers, $matches);
		$content_type = NULL;
		if ($does_match)
		{
			$content_type = $matches[1];
		}
		$response = $body;
		if ($content_type == 'application/json')
		{
			$response = json_decode($response, TRUE);
		}

		if ($http_code === 401 AND ( ! isset($with_login_retry) OR $with_login_retry === TRUE))
		{
			$this->login();
			$response = $this->get($endpoint, FALSE);
			$http_code = 0;
		}

		if ($http_code !== 0 AND (int) $http_code !== 200)
		{
			curl_close($ch);
			throw new Api_Http_Exception('', $http_code);
		}
		curl_close($ch);

		return $response;
	}

	public function post($endpoint, $data, $with_login_retry = NULL)
	{
		$ch = curl_init($this->get_url().$endpoint);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		$http_header = [
			'Content-Type: application/json'
		];
		$session_id = $this->get_session_id();
		if (isset($session_id))
		{
			curl_setopt($ch, CURLOPT_COOKIE, 'session='.$session_id);
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
		$raw_response = curl_exec($ch);
		list($headers, $body) = explode("\r\n\r\n", $raw_response);
		$matches = [];
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);
		foreach($matches[1] as $item)
		{
			$cookie = [];
			parse_str($item, $cookie);
			if (isset($cookie['session']))
			{
				$this->set_session_id($cookie['session']);
			}
		}

		$does_match = preg_match('/^Content-Type:\s*(.*)\r\n/mi', $headers, $matches);
		$content_type = NULL;
		if ($does_match)
		{
			$content_type = $matches[1];
		}
		$response = $body;
		if ($content_type == 'application/json')
		{
			$response = json_decode($response, TRUE);
		}
		$error_code = curl_errno($ch);

		if ($error_code)
		{
			curl_close($ch);
			throw new Api_Curl_Exception(curl_error($ch), $error_code);
		}

		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($http_code === 401 AND ( ! isset($with_login_retry) OR $with_login_retry === TRUE))
		{
			$this->login();
			$response = $this->post($endpoint, $data, FALSE);
			$http_code = 0;
		}

		if ($http_code !== 0 AND ! in_array((int) $http_code, [200, 201, 203, 204, 205, 206, 207, 208], TRUE))
		{
			curl_close($ch);
			throw new Api_Http_Exception('', $http_code);
		}

		curl_close($ch);

		return $response;
	}

	public function login()
	{
		$username = $this->get_username();
		$password = $this->get_password();
		return $this->post('/api/auth/login', ['username' => $username, 'password' => $password]);
	}

	public function reload_config()
	{
		return $this->post('/api/server/manage', ['operation' => 'reload']);
	}

	/**
	 * @return bool|string|null
	 * @throws Api_Exception
	 */
	public function get_lists()
	{
		$list = [];
		$list_from_api = $this->get('/api/entry_list');
		if (is_array($list_from_api))
		{
			$list = $list_from_api;
		}

		return  $list;
	}

	public function add_list($list_name)
	{
		return $this->post('/api/entry_list', ['name' => $list_name]);
	}

	public function add_to_list($list_id, List_Entry $entry)
	{
		return $this->post('/api/entry_list/'.$list_id.'/entries', [
			'title' => $entry->get_title(),
			'original_url' => $entry->get_original_url(),
			'url' => $entry->get_url()
		]);
	}

	public function list_entry_exists($list_id, List_Entry $entry)
	{
		$list_entries = $this->get('/api/entry_list/'.$list_id.'/entries');
		$exists = FALSE;

		foreach ($list_entries as $list_entry)
		{
			if ($list_entry['title'] == $entry->get_title())
			{
				$exists = TRUE;
				break;
			}

		}

		return $exists;
	}
}