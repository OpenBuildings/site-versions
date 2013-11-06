<?php

class Model_User extends Model_Auth_User {

	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);

		$meta
			->behaviors(array(
				'visitor_user' => Jam::behavior('visitor_user'),
				'buyer' => Jam::behavior('buyer'),
			));
	}
}