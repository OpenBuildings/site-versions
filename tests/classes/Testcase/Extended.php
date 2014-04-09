<?php

use Openbuildings\EnvironmentBackup as EB;

abstract class Testcase_Extended extends PHPUnit_Framework_TestCase {

	public $env;

	public function setUp()
	{
		parent::setUp();
		Database::instance()->begin();
		$this->env = new EB\Environment(array(
			'static' => new EB\Environment_Group_Static(),
			'config' => new EB\Environment_Group_Config(),
			'globals' => new EB\Environment_Group_Globals(),
			'server' => new EB\Environment_Group_Server(),
		));

		Session::instance()->restart();
	}

	public function tearDown()
	{
		Database::instance()->rollback();

		$this->env->restore();

		parent::tearDown();
	}
}
