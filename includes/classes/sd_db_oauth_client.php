<?php
if(!defined('IN_PRGM')) exit();

class sd_oauth_session_value_class
{
	var $id=0;
	var $session='';
	var $state='';
	var $access_token='';
	var $access_token_secret='';
	var $authorized=0;
	var $expiry;
	var $type='';
	var $server='';
	var $creation='';
	var $refresh_token='';
};

class sd_db_oauth_client extends oauth_client_class
{
	var $service = '';
	var $session = '';
	var $userid = 0;
	var $session_cookie = 'oauth_session';
	var $session_path = '/';
	var $sessions = array();

	function SetupSession(&$session)
	{
		if(isset($this->session) && strlen($this->session) ||
       isset($_COOKIE[$this->session_cookie]))
		{
			if($this->debug)
				$this->OutputDebug(strlen($this->session) ? 'Checking OAuth session '.$this->session : 'Checking OAuth session from cookie '.$_COOKIE[$this->session_cookie]);
			if(!$this->GetOAuthSession(strlen($this->session) ? $this->session : $_COOKIE[$this->session_cookie], $this->server, $session))
				return($this->SetError('OAuth session error: '.$this->error));
		}
		else
		{
			if($this->debug)
				$this->OutputDebug('No OAuth session is set');
			$session = null;
		}
		if(!isset($session))
		{
      if($this->exit_on_ajax && Is_Ajax_Request()) return false; //SD
			if($this->debug)
				$this->OutputDebug('Creating a new OAuth session');
			if(!$this->CreateOAuthSession($this->userid, $session))
				return($this->SetError('OAuth session error: '.$this->error));
			setcookie($this->session_cookie, $session->session, TIME_NOW+(86400*7), $this->session_path);
		}
		$this->session = $session->session;
		return true;
	}

	function GetStoredState(&$state)
	{
		if(!$this->SetupSession($session)) return false;
		$state = $session->state;
		return true;
	}

	function CreateOAuthSession($userid, &$session)
	{
    global $DB;

		$session = new sd_oauth_session_value_class;
		$session->state = md5(time().rand());
		$session->session = md5($session->state.time().rand());
		$session->access_token = '';
		$session->access_token_secret = '';
		$session->authorized = 0;
		$session->expiry = null;
		$session->type = '';
		$session->server = $this->server;
		$session->creation = gmstrftime("%Y-%m-%d %H:%M:%S");
		$session->refresh_token = '';

    //SD: for site-wide tokens only (userid = 0): first check if an authorized
    // token exists for same server to prevent extra unauthorized rows:
    if(empty($userid) &&
       ($tmp = $DB->query_first('SELECT access_token, access_token_secret'.
                                ' FROM {oauth_session}'.
                                " WHERE server = '%s' AND authorized = '1' AND IFNULL(session,'') <> ''".
                                ' ORDER BY creation DESC LIMIT 1',
                                $DB->escape_string($this->server))))
    {
      $session->authorized = 1;
      $session->access_token = $tmp['access_token'];
      $session->access_token_secret = $tmp['access_token_secret'];
      unset($tmp);
    }

		if(!$DB->query('INSERT INTO {oauth_session} (session, state, access_token, access_token_secret,'.
                   ' expiry, authorized, type, server, creation, refresh_token)'.
                   " VALUES('%s', '%s', '%s', '%s', null, '%s', '%s', '%s', '%s', '%s')",
       $session->session, $session->state,
       $DB->escape_string($session->access_token),
       $DB->escape_string($session->access_token_secret),
       $session->authorized?1:0, $DB->escape_string($session->type),
       $DB->escape_string($session->server),
       $DB->escape_string($session->creation), $session->refresh_token))
			return false;

		$session->id = $DB->insert_id();
		return true;
	}

	function SetOAuthSession(&$oauth_session, $session)
	{
		$oauth_session = new sd_oauth_session_value_class;
		$oauth_session->id = (int)$session['id'];
		$oauth_session->session = $session['session'];
		$oauth_session->state = $session['state'];
		$oauth_session->access_token = $session['access_token'];
		$oauth_session->access_token_secret = $session['access_token_secret'];
		$oauth_session->expiry = $session['expiry'];
		$oauth_session->authorized = !empty($session['authorized'])?1:0;
		$oauth_session->type = $session['type'];
		$oauth_session->server = $session['server'];
		$oauth_session->creation = $session['creation'];
		$oauth_session->refresh_token = $session['refresh_token'];
	}

	function GetUserSession($userid, &$oauth_session)
	{
		if($this->debug)
			$this->OutputDebug('Getting the OAuth session for user '.intval($userid));

    global $DB;
    $DB->result_type = MYSQL_ASSOC;
		if(!$row = $DB->query_first('SELECT *'.
                                ' FROM {oauth_session}'.
                                " WHERE userid = %d AND server = '%s' LIMIT 1",
                                $DB->escape_string($userid),
                                $DB->escape_string($this->server)))
		{
			$oauth_session = null;
			return true;
		}

		$this->SetOAuthSession($oauth_session, $row);
		$this->sessions[$oauth_session->session][$this->server] = $oauth_session;
		$this->session = $oauth_session->session;
		return true;
	}

	function GetOAuthSession($session, $server, &$oauth_session)
	{
		if(isset($this->sessions[$session][$server]))
		{
			$oauth_session = $this->sessions[$session][$server];
			return true;
		}

    global $DB;
    $DB->result_type = MYSQL_ASSOC;
		if(!$row = $DB->query_first('SELECT *'.
                                ' FROM {oauth_session}'.
                                " WHERE session = '%s' AND server = '%s' LIMIT 1",
                                $DB->escape_string($session),
                                $DB->escape_string($server)))
    {
			$oauth_session = null;
			return true;
		}

		$this->SetOAuthSession($oauth_session, $row);
		$this->sessions[$session][$server] = $oauth_session;
		return true;
	}

	function StoreAccessToken($access_token)
	{
		if(!$this->SetupSession($session))
			return false;

		$session->access_token = $access_token['value'];
		$session->access_token_secret = (isset($access_token['secret']) ? $access_token['secret'] : '');
		$session->authorized = (!empty($access_token['authorized']) ? 1 : 0);
		$session->expiry = (isset($access_token['expiry']) && ($access_token['expiry'] != '0000-00-00 00:00:00') ? $access_token['expiry'] : null);
		if(isset($access_token['type']))
			$session->type = $access_token['type'];
		$session->refresh_token = (isset($access_token['refresh']) ? $access_token['refresh'] : '');
		if(!$this->GetOAuthSession($session->session, $this->server, $oauth_session))
			return($this->SetError('OAuth session error: '.$this->error));

		if(!isset($oauth_session))
		{
			$this->error = 'the session to store the access token was not found';
			return false;
		}

		$oauth_session->access_token = $session->access_token;
		$oauth_session->access_token_secret = $session->access_token_secret;
		$oauth_session->authorized = (!empty($session->authorized) ? 1 : 0);
		$oauth_session->expiry = (isset($session->expiry) && ($session->expiry != '0000-00-00 00:00:00')  ? "'".$session->expiry."'": 'null');
		$oauth_session->type = (isset($session->type) ? $session->type : '');
		$oauth_session->refresh_token = $session->refresh_token;
    global $DB;
    $DB->query('UPDATE {oauth_session} SET'.
               " session='%s', state='%s', access_token='%s', access_token_secret='%s',".
               " expiry = %s, authorized='%s', type='%s', server='%s', creation='%s',".
               " refresh_token='%s', userid=%d".
               " WHERE id=%d",
                $oauth_session->session,
                $oauth_session->state,
                $oauth_session->access_token,
                $oauth_session->access_token_secret,
                $oauth_session->expiry,
                $oauth_session->authorized,
                $oauth_session->type,
                $oauth_session->server,
                $oauth_session->creation,
                $oauth_session->refresh_token,
                $this->userid,
                $oauth_session->id
               );
    return true;
	}

	function GetAccessToken(&$access_token)
	{
		if($this->userid)
		{
			if(!$this->GetUserSession($this->userid, $session))
				return false;
			if(!isset($session))
      {
        $access_token = array();
        return true;
      }
		}
		else
		{
			if(!$this->SetupSession($session))
				return false;
		}
		if(strlen($session->access_token))
		{
			$access_token = array(
				'value'=>$session->access_token,
				'secret'=>$session->access_token_secret
			);
			if(isset($session->authorized))
				$access_token['authorized'] = $session->authorized;
			if(isset($session->expiry) && ($session->expiry != '0000-00-00 00:00:00'))
				$access_token['expiry'] = $session->expiry;
			if(strlen($session->type))
				$access_token['type'] = $session->type;
			if(strlen($session->refresh_token))
				$access_token['refresh'] = $session->refresh_token;
		}
		else
			$access_token = array();
		return true;
	}

	function ResetAccessToken()
	{
		if($this->debug)
			$this->OutputDebug('Resetting the access token status for OAuth server located at '.$this->access_token_url);
		SetCookie($this->session_cookie, '', 0, $this->session_path);
		return true;
	}

	function SetUser($userid)
	{
    global $DB;
    if(empty($userid) || ($userid<1)) return false;
		if(strlen($this->session) === 0)
			$this->SetError('OAuth session was not yet established');
		if(!$DB->query('UPDATE {oauth_session}'.
                   " SET userid=%d WHERE session='%s' AND server='%s'",
                   $userid, $DB->escape_string($this->session),
                   $DB->escape_string($this->server)))
    {
			return false;
    }
		$this->userid = (int)$userid;
		return true;
	}
};
