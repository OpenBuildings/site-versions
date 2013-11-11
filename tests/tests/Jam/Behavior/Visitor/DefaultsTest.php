<?php

/**
 * @group  jam.behavior.visitor_defaults
 */
class Jam_Behavior_Visitor_DefaultsTest extends Testcase_Extended {

	/**
	 * @covers Jam_Behavior_Visitor_Defaults::initialize
	 */
	public function test_initialze()
	{
		$behavior = $this->getMock('Jam_Behavior_Visitor_Defaults', array('set_new_token'), array(array()));

		$behavior
			->expects($this->once())
			->method('set_new_token');

		$meta = new Jam_Meta('test');
		
		$behavior->initialize($meta, 'visitor_defaults');

		$visitor = Jam::build('visitor');

		$meta->events()->trigger('model.create_session', $visitor);
	}

	/**
	 * @covers Jam_Behavior_Visitor_Defaults::set_new_token
	 */
	public function test_set_new_token()
	{
		$behavior = new Jam_Behavior_Visitor_Defaults(array());

		$visitor = Jam::build('visitor');

		$this->assertEquals('', $visitor->token);

		$behavior->set_new_token($visitor);

		$this->assertStringMatchesFormat('%c%c%c%c%c%c%c%c%c%c%c%c%c',$visitor->token);
	}
}
