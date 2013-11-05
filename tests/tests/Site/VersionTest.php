<?php

/**
 * @group  site_version
 */
class Site_VersionTest extends Testcase_Extended {

	public function data_current_version_name()
	{
		return array(
			array(
				'example.com',
				array(
					'normal' => array(
						'domains' => 'example.com',
					),
					'whiteversion' => array(
						'domains' => 'whiteversion.example.com',
					),
				),
				'normal',
			),

			array(
				'whiteversion.example.com',
				array(
					'whiteversion' => array(
						'domains' => 'whiteversion.example.com',
					),
					'normal' => array(
						'domains' => 'example.com',
					),
				),
				'whiteversion',
			),

			array(
				'test.example.com',
				array(
					'whiteversion' => array(
						'domains' => 'whiteversion.example.com',
					),
					'normal' => array(
						'domains' => 'example.com',
					),
				),
				'normal',
			),

			array(
				'test.example.com',
				array(
					'whiteversion' => array(
						'domains' => array('whiteversion.example.com', 'test.example.com'),
					),
					'normal' => array(
						'domains' => 'example.com',
					),
				),
				'whiteversion',
			),
		);
	}

	/**
	 * @dataProvider data_current_version_name
	 * @covers Site_Version::current_version_name
	 */
	public function test_current_version_name($host, $versions, $expceted)
	{
		$this->env->backup_and_set(array(
			'HTTP_HOST' => $host,
			'site-versions.versions' => $versions,
		));

		$version = Site_Version::current_version_name();

		$this->assertEquals($expceted, $version);
	}

	/**
	 * @covers Site_Version::instance
	 * @covers Site_Version::__construct
	 * @covers Site_Version::name
	 * @covers Site_Version::config
	 */
	public function test_instance()
	{
		$this->env->backup_and_set(array(
			'site-versions.versions' => array(
				'test' => array('config'),
			),
		));

		$class = $this->getMockClass('Site_Version', array('current_version_name'));

		$class::staticExpects($this->once())
			->method('current_version_name')
			->will($this->returnValue('test'));

		$instance = $class::instance();

		$this->assertInstanceOf('Site_Version', $instance);
		$this->assertEquals(array('config'), $instance->config());
		$this->assertEquals('test', $instance->name());
		
		$instance2 = $class::instance();

		$this->assertSame($instance, $instance2);
	}

	/**
	 * @covers Site_Version::update_kohana_config
	 */
	public function test_update_kohana_config()
	{
		$this->env->backup_and_set(array(
			'auth.services' => array(
				'facebook' => array(
					'auth' => array(
						'appId' => 'param1',
						'secret' => 'param2',
					)
				)
			),
			'site-versions.versions' => array(
				'test' => array(),
			),
		));
		$version = new Site_Version('test');

		$version->update_kohana_config(array(
			'auth.services.facebook' => array(
				'auth' => array(
					'secret' => 'changed_param2',
				)
			)
		));

		$expceted = array(
			'auth' => array(
				'appId' => 'param1',
				'secret' => 'changed_param2',
			)
		);
		
		$this->assertEquals($expceted, Kohana::$config->load('auth.services.facebook'));
	}

	/**
	 * @covers Site_Version::configure
	 */
	public function test_configure()
	{
		$this->env->backup_and_set(array(
			'_GET' => array(
				'_SV_VISITOR_TOKEN' => '123',
			),
			'site-versions.versions' => array(
				'test' => array(
					'config' => array('some_config')
				),
			),
		));

		$version = $this->getMock('Site_Version', array('update_kohana_config', 'load_visitor'), array('test'));

		$version
			->expects($this->once())
			->method('update_kohana_config')
			->with($this->equalTo(array('some_config')));

		$version
			->expects($this->once())
			->method('load_visitor')
			->with($this->equalTo('123'));

		$version->configure();
	}

	/**
	 * @covers Site_Version::load_visitor
	 */
	public function test_load_visitor()
	{
		$visitor = Jam::find('visitor', 1);

		$this->env->backup_and_set(array(
			'site-versions.versions' => array(
				'test' => array(),
			),
		));

		$version = new Site_Version('test');

		$version->load_visitor($visitor->token);


		$loaded = Model_Visitor::load();

		$this->assertEquals($loaded->as_array(), $visitor->as_array());
	}

	/**
	 * @covers Site_Version::domains
	 */
	public function test_domains()
	{
		$this->env->backup_and_set(array(
			'site-versions.versions' => array(
				'test' => array(
					'domains' => 'test.example.com',
				),
			),
		));

		$version = new Site_Version('test');

		$domains = $version->domains();

		$expceted = array('test.example.com');

		$this->assertEquals($expceted, $domains);
	}

	public function data_protocol()
	{
		return array(
			array(array('protocol' => 'https'), 'https'),
			array(array('protocol' => 'http'), 'http'),
			array(array(), 'http'),
		);
	}

	/**
	 * @dataProvider data_protocol
	 * @covers Site_Version::protocol
	 */
	public function test_protocol($config, $expected)
	{
		$this->env->backup_and_set(array(
			'site-versions.versions' => array(
				'test' => $config,
			),
		));

		$version = new Site_Version('test');

		$protocol = $version->protocol();

		$this->assertEquals($expected, $protocol);
	}


	public function data_base()
	{
		return array(
			array(array('protocol' => 'https', 'domains' => 'example.com'), 'https://example.com'),
			array(array('protocol' => 'http', 'domains' => array('test.example.com', 'whitelable.example.com')), 'http://test.example.com'),
		);
	}

	/**
	 * @dataProvider data_base
	 * @covers Site_Version::base
	 */
	public function test_base($config, $expected)
	{
		$this->env->backup_and_set(array(
			'site-versions.versions' => array(
				'test' => $config,
			),
		));

		$version = new Site_Version('test');

		$base = $version->base();

		$this->assertEquals($expected, $base);
	}
}
