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


	public function data_model_set_geoip_defaults()
	{
		return array(
			array('66.102.0.0', array('country_code' => 'GB', 'country_name' => 'United Kingdom'), array(), array('currency' => 'GBP', 'country' => 'United Kingdom')),
			array('66.102.0.1', array('country_code' => 'FR', 'country_name' => 'France'), array(), array('currency' => 'GBP', 'country' => 'France')),
			array('66.102.0.2', array('country_code' => 'AU', 'country_name' => 'Australia'), array(), array('currency' => 'GBP', 'country' => 'Australia')),
			array('66.102.0.3', array('country_code' => 'AU', 'country_name' => 'Australia'), array('default_currency' => 'USD'), array('currency' => 'USD', 'country' => 'Australia')),
			array('66.102.0.4', array('country_code' => 'AU', 'country_name' => 'Australia'), array('countries_currencies' => array('Australia' => 'AUD'), 'default_currency' => 'USD'), array('currency' => 'AUD', 'country' => 'Australia')),
			array('66.102.0.5', array('country_name' => 'Australia'), array('default_country_code' => 'FR'), array('currency' => 'GBP', 'country' => 'France'))
		);
	}

	/**
	 * @dataProvider data_model_set_geoip_defaults
	 * @covers Jam_Behavior_Visitor_Defaults::set_geoip_defaults
	 * @covers Jam_Behavior_Visitor_Defaults::_set_new_country
	 * @covers Jam_Behavior_Visitor_Defaults::_set_new_currency
	 */
	public function test_model_set_geoip_defaults($ip, $geoip_record, $options, $expected)
	{
		$this->env->backup_and_set(array('Request::$client_ip' => $ip));

		$visitor = Jam::build('visitor');
		$behavior = $this->getMock('Jam_Behavior_Visitor_Defaults', array('geoip_record'), array($options));

		$behavior
			->expects($this->once())
			->method('geoip_record')
			->with($this->equalTo($ip))
			->will($this->returnValue($geoip_record));

		$behavior->set_geoip_defaults($visitor);

		foreach ($expected as $field => $expected_value) 
		{
			$this->assertEquals($expected_value, is_object($visitor->{$field}) ? $visitor->{$field}->name() : $visitor->{$field});
		}
	}
}
