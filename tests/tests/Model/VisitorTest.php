<?php

/**
 * @group  model.visitor
 * @coversDefaultClass Model_Visitor
 */
class Model_VisitorTest extends Testcase_Extended {

	/**
	 * @covers ::load
	 */
	public function test_load_loaded_models()
	{
		$user = Jam::find('user', 1);

		Auth::instance()->force_login($user);

		$this->assertInstanceOf('Model_Visitor', Model_Visitor::load());
		$this->assertEquals(1, Model_Visitor::load()->id());
	}

	/**
	 * @covers ::load
	 */
	public function test_load_loaded_user()
	{
		$user = Jam::find('user', 2);
		$loaded_visitor = Jam::build('visitor');

		Auth::instance()->force_login($user);
		Model_Visitor::session($loaded_visitor);


		$result = Model_Visitor::load();

		$this->assertSame($loaded_visitor, $result);
		$this->assertTrue($loaded_visitor->loaded());
		$this->assertNotEquals(1, $loaded_visitor->id());
		$this->assertSame($loaded_visitor->user, $user);
	}

	/**
	 * @covers ::load
	 */
	public function test_load_loaded_user2()
	{
		$user = Jam::find('user', 2);

		Auth::instance()->force_login($user);

		$result = Model_Visitor::load();

		$this->assertSame($result->user, $user);
		$this->assertTrue($result->loaded());
		$this->assertNotEquals(1, $result->id());
	}


	/**
	 * @covers ::create_session
	 */
	public function test_create_session()
	{
		$visitor = Model_Visitor::create_session();
		$this->assertInstanceOf('Model_Visitor', $visitor);
		$this->assertFalse($visitor->loaded());
		$this->assertTrue($visitor->create_session_called);
	}

	/**
	 * @covers ::session
	 */
	public function test_session()
	{
		$visitor = Jam::build('visitor');

		Model_Visitor::session($visitor);

		$this->assertEquals(Session::instance()->get(Model_Visitor::SESSION_VARIABLE), $visitor);

		$result = Model_Visitor::session();

		$this->assertEquals($result, $visitor);
	}

	/**
	 * @covers ::save_session
	 */
	public function test_save_session()
	{
		$visitor = $this->getMock('Model_Visitor', array('check_insist'), array('visitor'));
		$visitor->set(array('token' => '123'));

		$visitor
			->expects($this->once())
			->method('check_insist');

		$visitor->save_session();

		$loaded = Model_Visitor::session();

		$this->assertEquals($loaded->as_array(), $visitor->as_array());
	}

	/**
	 * @covers ::serialize
	 * @covers ::unserialize
	 */
	public function test_serialize()
	{
		$visitor = Jam::build('visitor', array(
			'user_id' => 18301,
			'ip' => '231.43.243.132',
			'token' => uniqid(),
			'country_id' => 18323
		));

		$result = unserialize(serialize($visitor));

		$this->assertEquals($result->as_array(), $visitor->as_array());
	}
}
