<?php

class Model_Product extends Jam_Model {

	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->fields(array(
				'id' => Jam::field('primary'),
				'name' => Jam::field('string'),
				'currency' => Jam::field('string'),
				'price' => Jam::field('float'),
			))
			->validator('name', 'price', 'currency', array(
				'present' => TRUE
			));
	}
}