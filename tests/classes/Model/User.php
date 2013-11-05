<?php

class Model_User extends Model_Auth_User {

	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);

		$meta
			->behaviors(array(
				'visitable' => Jam::behavior('visitable'),
				'buyer' => Jam::behavior('buyer'),
			));
	}
}