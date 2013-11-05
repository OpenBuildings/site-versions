<?php

class Model_Visitor extends Kohana_Model_Visitor {

	public static function initialize(Jam_Meta $meta)
	{
		parent::initialize($meta);

		$meta
			->behaviors(array(
				'visitor_tester' => Jam::behavior('visitor_tester'),
			));
	}
}