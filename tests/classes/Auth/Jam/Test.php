<?php defined('SYSPATH') OR die('No direct access allowed.');

class Auth_Jam_Test extends Auth_Jam {

	protected function _autologin_cookie($token = NULL, $expires = NULL)
	{
		if ($token === FALSE)
		{
			unset($_COOKIE['authautologin']);
		}
		elseif ($token !== NULL) 
		{
			$_COOKIE['authautologin'] = $token;
		}
		else
		{
			return Arr::get($_COOKIE, 'authautologin');
		}

		return $this;
	}
}