<?php defined('SYSPATH') OR die('No direct script access.');

class Jam_Behavior_Visitor_Tester extends Jam_Behavior {

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta->events()
			->bind('model.create_session', array($this, 'create_session'))
			->bind('model.build_purchase', array($this, 'build_purchase'));
	}

	public function create_session(Model_Visitor $payment)
	{
		$payment->create_session_called = TRUE;
	}

	public function build_purchase(Model_Visitor $payment)
	{
		$payment->build_purchase_called = TRUE;
	}
}
