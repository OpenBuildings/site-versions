<?php

/**
 * @group  site_version
 * @coversDefaultClass Site_Version
 */
class Site_VersionTest extends Testcase_Extended {

	public function data_current_version_name()
	{
		return array(
			array(
				'example.com',
				array(
					'normal' => array(
						'matches' => function($domain) {
							return preg_match('/example\.com/', $domain);
						}
					),
					'whiteversion' => array(
						'matches' => function($domain) {
							return preg_match('/whiteversion\.example\.com/', $domain);
						}
					),
				),
				'normal',
			),

			array(
				'whiteversion.example.com',
				array(
					'whiteversion' => array(
						'matches' => function($domain) {
							return $domain == 'whiteversion.example.com';
						}
					),
					'normal' => array(
						'matches' => function($domain) {
							return $domain == 'example.com';
						}
					),
				),
				'whiteversion',
			),

			array(
				'test.example.com',
				array(
					'whiteversion' => array(
						'matches' => function($domain) {
							return $domain == 'whiteversion.example.com';
						}
					),
					'normal' => array(
						'matches' => function($domain) {
							return $domain == 'example.com';
						}
					),
				),
				'normal',
			),

			array(
				'me.shop.example.com',
				array(
					'meshop' => array(
						'matches' => function($domain) {
							return preg_match('/[a-z]*\.shop\.example\.com/', $domain);
						}
					),
					'normal' => array(
						'matches' => function($domain) {
							return $domain == 'example.com';
						}
					),
				),
				'meshop',
			),
		);
	}

	/**
	 * @dataProvider data_current_version_name
	 * @covers ::current_version_name
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
	 * @covers ::versions
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
	 * @covers ::instance
	 * @covers ::__construct
	 * @covers ::name
	 * @covers ::config
	 */
	public function test_instance()
	{
		$matches = function ($domain) {
			return $domain == 'example.com';
		};

		$this->env->backup_and_set(array(
			'site-versions.versions' => array(
				'test' => array(
					'config' => 'test',
					'matches' => $matches
				),
				'test2' => array('config2'),
			),
			'HTTP_HOST' => 'example.com',
		));

		$instance = Site_Version::instance();

		$this->assertInstanceOf('Site_Version', $instance);
		$this->assertEquals(array('config' => 'test', 'matches' => $matches), $instance->config());
		$this->assertEquals('test', $instance->name());
		$this->assertEquals('example.com', $instance->current_domain());

		$instance2 = Site_Version::instance();

		$this->assertSame($instance, $instance2);

		$instance3 = Site_Version::instance('test');

		$this->assertSame($instance, $instance3);

		$instance4 = Site_Version::instance('test2', 'new.example.com');

		$this->assertInstanceOf('Site_Version', $instance4);
		$this->assertEquals(array('config2'), $instance4->config());
		$this->assertEquals('test2', $instance4->name());
		$this->assertEquals('new.example.com', $instance4->current_domain());
	}

	/**
	 * @covers ::update_kohana_config
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
	 * @covers ::set_routes
	 */
	public function test_set_routes()
	{
		$this->env->backup_and_set(array(
			'site-versions.versions' => array(
				'test' => array(),
			),
		));

		$this->assertCount(1, Route::all());

		$version = new Site_Version('test');

		$version->set_routes(array(
			'homepage' => array(
				'home(/<id>)',
				array('action' => '\d+'),
				array(
					'controller' => 'test',
					'action' => 'index',
				)
			)
		));

		$this->assertCount(2, Route::all());

		$expected = new Route('home(/<id>)', array('action' => '\d+'));
		$expected->defaults(array(
			'controller' => 'test',
			'action' => 'index',
		));

		$this->assertEquals($expected, Route::get('homepage'));
	}

	/**
	 * @covers ::configure
	 */
	public function test_configure()
	{
		$this->env->backup_and_set(array(
			'_GET' => array(
				'_SV_VISITOR_TOKEN' => '123',
			),
			'site-versions.versions' => array(
				'test' => array(
					'config' => array('some_config'),
					'routes' => array('homepage' => 'home(/<id>)'),
				),
			),
		));

		$version = $this->getMock('Site_Version', array('load_visitor', 'update_kohana_config', 'set_routes'), array('test'));

		$version
			->expects($this->once())
			->method('load_visitor')
			->with($this->equalTo('123'));

		$version
			->expects($this->once())
			->method('update_kohana_config')
			->with($this->equalTo(array('some_config')));

		$version
			->expects($this->once())
			->method('set_routes')
			->with($this->equalTo(array('homepage' => 'home(/<id>)')));

		$version->configure();
	}

	/**
	 * @covers ::load_visitor
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
	 * @covers ::domain
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

	/**
	 * @covers ::domain
	 */
	public function test_domain_no_insecure()
	{
		$this->env->backup_and_set(array(
			'site-versions.versions' => array(
				'test' => array(
					'secure_domain' => 'test.example.com',
				),
			),
		));

		$version = new Site_Version('test');

		$domain = $version->domain();

		$expceted = 'test.example.com';

		$this->assertEquals($expceted, $domain);
	}


	/**
	 * @covers ::domain
	 */
	public function test_domain_closure()
	{
		$this->env->backup_and_set(array(
			'site-versions.versions' => array(
				'test' => array(
					'domain' => function (Site_Version $version) {
						return 'test!';
					}
				),
			),
		));

		$version = new Site_Version('test');

		$domain = $version->domain();

		$this->assertEquals('test!', $domain);
	}


	public function data_protocol()
	{
		return array(
			array(array('protocol' => 'https'), 'https'),
			array(array('protocol' => 'http'), 'http'),
			array(array(), 'https'),
		);
	}

	/**
	 * @dataProvider data_protocol
	 * @covers ::protocol
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
	 * @covers ::visitor_params
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
			array(array('domain' => 'example.com'), 'https://example.com'),
			array(array('secure_domain' => 'example.com'), 'https://example.com'),
			array(array('protocol' => 'http', 'domain' => 'test.example.com'), 'http://test.example.com'),
		);
	}

	/**
	 * @dataProvider data_base
	 * @covers ::base
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

	public function data_secure_uri()
	{
		return array(
			array(
				'best.example.com',
				null,
				'/test',
				'https://best.secure.example.com/test?_SV_VISITOR_TOKEN=53a0216a7ba6f',
			),
			array(
				'best.secure.example.com',
				'on',
				'/test',
				'/test',
			),
		);
	}

	/**
	 * @dataProvider data_secure_uri
	 * @covers ::secure_uri
	 */
	public function test_secure_uri($domain, $https, $uri, $expected)
	{
		$this->env->backup_and_set(array(
			'site-versions.versions' => array(
				'test' => array(
					'domain' => 'best.example.com',
					'secure_domain' => 'best.secure.example.com',
				),
			),
			'HTTP_HOST' => $domain,
			'HTTPS' => $https,
		));

		$visitor = Model_Visitor::load();
		$visitor->token = '53a0216a7ba6f';

		$version = new Site_Version('test');

		$uri = $version->secure_uri($uri);

		$this->assertEquals($expected, $uri);
	}

	public function test_redirect_to_secure()
	{
		$this->env->backup_and_set(array(
			'HTTP_HOST' => 'we-do-wood.example.com',
			'Request::$initial' => null,
			'Site_Version::$instances' => null,
			'site-versions.versions' => array(
				'test' => array(
					'domain' => 'best.example.com',
					'secure_domain' => 'best.secure.example.com',
				),
			),
		));

		Request::factory('http://we-do-wood.example.com');

		$previous_secure = Request::initial()->secure() ?: FALSE;

		$version = Site_Version::instance('test');

		Request::initial()->secure(TRUE);
		$version->redirect_to_secure();

		Request::initial()->secure(FALSE);

		try
		{
			$version->redirect_to_secure();
			$this->fail('Redirect to secure domain expected');
		}
		catch (HTTP_Exception_302 $e)
		{
		}

		Request::initial()->secure($previous_secure);
	}
}
