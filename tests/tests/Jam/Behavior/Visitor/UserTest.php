<?php

/**
 * @group  jam.behavior.visitor_user
 * @coversDefaultClass Jam_Behavior_Visitor_User
 */
class Jam_Behavior_Visitor_UserTest extends Testcase_Extended {

	/**
	 * @covers ::initialize
	 */
	public function test_initialze()
	{
		$behavior = new Jam_Behavior_Visitor_User(array());

		$meta = new Jam_Meta('test');

		$behavior->initialize($meta, 'visitor_defaults');

		$this->assertNotNull($meta->association('visitor'));
	}
}
