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
						'domain' => 'example.com',
					),
					'whiteversion' => array(
						'domain' => 'whiteversion.example.com',
					),
				),
				'normal',
			),

			array(
				'whiteversion.example.com',
				array(
					'whiteversion' => array(
						'domain' => 'whiteversion.example.com',
					),
					'normal' => array(
						'domain' => 'example.com',
					),
				),
				'whiteversion',
			),

			array(
				'test.example.com',
				array(
					'whiteversion' => array(
						'domain' => 'whiteversion.example.com',
					),
					'normal' => array(
						'domain' => 'example.com',
					),
				),
				'normal',
			),

			array(
				'test.example.com',
				array(
					'whiteversion' => array(
						'domain' => 'whiteversion.example.com',
						'secure_domain' => 'test.example.com',
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
	 * @covers Site_Version::versions
	 */
	public function test_versions()
	{
		$expceted = array('test1' => array('test1'), 'test2' => array('test2'));

		$this->env->backup_and_set(array(
			'site-versions.versions' => $expceted,
		));

		$versions = Site_Version::versions();

		$this->assertEquals($expceted, $versions);
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
				'test' => array('config' => 'test', 'domain' => 'example.com'),
				'test2' => array('config2'),
			),
			'HTTP_HOST' => 'example.com',
		));

		$instance = Site_Version::instance();

		$this->assertInstanceOf('Site_Version', $instance);
		$this->assertEquals(array('config' => 'test', 'domain' => 'example.com'), $instance->config());
		$this->assertEquals('test', $instance->name());

		$instance2 = Site_Version::instance();

		$this->assertSame($instance, $instance2);

		$instance3 = Site_Version::instance('test');

		$this->assertSame($instance, $instance3);

		$instance4 = Site_Version::instance('test2');

		$this->assertInstanceOf('Site_Version', $instance4);
		$this->assertEquals(array('config2'), $instance4->config());
		$this->assertEquals('test2', $instance4->name());
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
	 * @covers Site_Version::domain
	 */
	public function test_domain()
	{
		$this->env->backup_and_set(array(
			'site-versions.versions' => array(
				'test' => array(
					'domain' => 'test.example.com',
				),
			),
		));

		$version = new Site_Version('test');

		$domain = $version->domain();

		$expceted = 'test.example.com';

		$this->assertEquals($expceted, $domain);
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

	public function data_visitor_params()
	{
		return array(
			array(
				array(
					'unified_visitor' => FALSE
				),
				array()
			),
			array(
				array(
					'unified_visitor' => TRUE
				),
				array(
					'_SV_VISITOR_TOKEN' => '53a0216a7ba6f'
				)
			),
		);
	}

	/**
	 * @dataProvider data_visitor_params
	 * @covers Site_Version::visitor_params
	 */
	public function test_visitor_params($config, $expected)
	{
		$this->env->backup_and_set(array(
			'site-versions.versions' => array(
				'test' => $config,
			),
			'_COOKIE' => array('_ga' => 'GA1.2.1356520872.1376395956'),
		));

		$version = new Site_Version('test');

		$visitor = Model_Visitor::load();
		$visitor->token = '53a0216a7ba6f';

		$visitor_params = $version->visitor_params();


		$this->assertEquals($expected, $visitor_params);
	}

	public function data_base()
	{
		return array(
			array(array('protocol' => 'https', 'domain' => 'example.com'), 'https://example.com'),
			array(array('protocol' => 'http', 'domain' => 'test.example.com'), 'http://test.example.com'),
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

	public function data_secure_url()
	{
		return array(
			array(
				array('protocol' => 'https', 'domain' => 'example.com', 'secure_domain' => 'secure.example.com'),
				'example.com',
				'/test',
				'https://secure.example.com/test?_SV_VISITOR_TOKEN=53a0216a7ba6f',
			),
			array(
				array('protocol' => 'https', 'domain' => 'example.com', 'secure_domain' => 'secure.example.com'),
				'secure.example.com',
				'/test',
				'/test',
			),
		);
	}

	/**
	 * @dataProvider data_secure_url
	 * @covers Site_Version::secure_url
	 */
	public function test_secure_url($config, $domain,$uri, $expected)
	{
		$this->env->backup_and_set(array(
			'site-versions.versions' => array(
				'test' => $config,
			),
			'HTTP_HOST' => $domain,
		));

		$visitor = Model_Visitor::load();
		$visitor->token = '53a0216a7ba6f';

		$version = new Site_Version('test');

		$uri = $version->secure_uri($uri);

		$this->assertEquals($expected, $uri);
	}
}
