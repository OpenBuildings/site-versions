<?php

/**
 * @group  jam.behavior.currency_auto
 */
class Jam_Behavior_Currency_AutoTest extends Testcase_Extended {

	public function data_model_before_check()
	{
		return array(
			array('66.102.0.0', array('country_name' => 'United Kingdom'), array(), 'GBP'),
			array('66.102.0.1', array('country_name' => 'France'), array(), 'EUR'),
			array('66.102.0.2', array('country_name' => 'Australia'), array(), 'EUR'),
			array('66.102.0.3', array('country_name' => 'Australia'), array('default' => 'USD'), 'USD'),
			array('66.102.0.4', array('country_name' => 'Australia'), array('countries' => array('Australia' => 'AUD'), 'default' => 'USD'), 'AUD'),
		);
	}

	/**
	 * @dataProvider data_model_before_check
	 * @covers Jam_Behavior_Currency_Auto::model_before_check
	 */
	public function test_model_before_check($ip, $geoip_record, $options, $expected)
	{
		$this->env->backup_and_set(array('Request::$client_ip' => $ip));

		$visitor = Jam::build('visitor');
		$behavior = $this->getMock('Jam_Behavior_Currency_Auto', array('geoip_record'), array($options));

		$behavior
			->expects($this->once())
			->method('geoip_record')
			->with($this->equalTo($ip))
			->will($this->returnValue($geoip_record));

		$behavior->model_before_check($visitor);

		$this->assertEquals($expected, $visitor->currency);
	}
}
