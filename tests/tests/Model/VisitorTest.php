<?php

/**
 * @group  model.visitor
 */
class Model_VisitorTest extends Testcase_Extended {

	/**
	 * @covers Model_Visitor::load
	 */
	public function test_load_loaded_models()
	{
		$user = Jam::find('user', 1);

		Auth::instance()->force_login($user);

		$this->assertInstanceOf('Model_Visitor', Model_Visitor::load());
		$this->assertEquals(1, Model_Visitor::load()->id());
	}

	/**
	 * @covers Model_Visitor::load
	 */
	public function test_load_loaded_user()
	{
		$user = Jam::find('user', 2);
		$loaded_visitor = Jam::build('visitor');

		Auth::instance()->force_login($user);

		$class = $this->getMockClass('Model_Visitor', array('session'));

		$class::staticExpects($this->once())
			->method('session')
			->will($this->returnValue($loaded_visitor));

		$result = $class::load();

		$this->assertSame($loaded_visitor, $result);
		$this->assertTrue($loaded_visitor->loaded());
		$this->assertNotEquals(1, $loaded_visitor->id());
		$this->assertSame($loaded_visitor->user, $user);
	}

	/**
	 * @covers Model_Visitor::load
	 */
	public function test_load_loaded_user2()
	{
		$user = Jam::find('user', 2);
		$created_visitor = Jam::build('visitor');

		Auth::instance()->force_login($user);

		$class = $this->getMockClass('Model_Visitor', array('session', 'create_session'));

		$class::staticExpects($this->once())
			->method('session')
			->will($this->returnValue(NULL));

		$class::staticExpects($this->once())
			->method('create_session')
			->will($this->returnValue($created_visitor));

		$result = $class::load();

		$this->assertSame($created_visitor, $result);
		$this->assertTrue($created_visitor->loaded());
		$this->assertNotEquals(1, $created_visitor->id());
		$this->assertSame($created_visitor->user, $user);
	}


	/**
	 * @covers Model_Visitor::create_session
	 */
	public function test_create_session()
	{
		$class = $this->getMockClass('Model_Visitor', array('session'));

		$class::staticExpects($this->once())
			->method('session')
			->with($this->isInstanceOf('Model_Visitor'));

		$visitor = $class::create_session();
		$this->assertInstanceOf('Model_Visitor', $visitor);
		$this->assertFalse($visitor->loaded());
		$this->assertTrue($visitor->create_session_called);
	}

	/**
	 * @covers Model_Visitor::session
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
	 * @covers Model_Visitor::save_session
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
	 * @covers Model_Visitor::serialize
	 * @covers Model_Visitor::unserialize
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
