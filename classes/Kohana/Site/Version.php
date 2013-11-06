<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Site_Version {

	protected static $instances = array();

	public static function instance($name = NULL)
	{
		if ($name === NULL) 
		{
			$name = 'current';
		}

		if ( ! isset(static::$instances[$name])) 
		{
			$version_name = ($name == 'current') ? static::current_version_name() : $name;
			static::$instances[$version_name] = static::$instances[$name] = new Site_Version($version_name);
		}

		return static::$instances[$name];
	}

	public static function versions()
	{
		return Kohana::$config->load('site-versions.versions');	
	}

	public static function current_version_name()
	{
		foreach (static::versions() as $version_name => $params) 
		{
			if ($_SERVER['HTTP_HOST'] == $params['domain'])
			{
				return $version_name;
			}
		}

		end($versions);
		return key($versions);
	}

	protected $config;
	protected $name;

	public function __construct($name)
	{
		$this->name = $name;
		$this->config = Kohana::$config->load('site-versions.versions.'.$name);
	}

	public function name()
	{
		return $this->name;
	}

	public function config($name = NULL, $default = NULL)
	{
		return $name ? Arr::path($this->config, $name, $default) : $this->config;
	}

	public function configure()
	{
		if (($config_updates = $this->config('config'))) 
		{
			$this->update_kohana_config($config_updates);
		}

		if (array_key_exists('_SV_VISITOR_TOKEN', $_GET))
		{
			$this->load_visitor($_GET['_SV_VISITOR_TOKEN']);
		}
	}

	public function update_kohana_config(array $config_updates)
	{
		foreach ($config_updates as $key => $value) 
		{
			list($file, $config_key) = explode('.', $key, 2);

			$config = Kohana::$config->load($file);

			$config_value = Kohana::$config->load($key);
			$config_value = Arr::merge($config_value, $value);

			$config->set($config_key, $config_value);
		}
	}

	public function load_visitor($token)
	{
		$visitor = Jam::all('visitor')->where('token', '=', $token)->first();

		if ($visitor)
		{
			Model_Visitor::session($visitor);
		}
	}

	public function param($name)
	{
		return $this->config('params.'.$name);
	}

	public function google_campaing_query()
	{
		if (array_key_exists('utm_source', $_GET))
			return array();

		$google_campaign = $this->config('google_campain', array());

		return $google_campaign;
	}

	public function visitor_params()
	{
		if ( ! $this->config('unified_visitor', TRUE)) 
			return array();

		$visitor = Model_Visitor::load();
		if ( ! $visitor->loaded()) 
		{
			$visitor->save();
		}

		return array('_SV_VISITOR_TOKEN' => $visitor->token);
	}

	public function domain()
	{
		return $this->config('domain');
	}

	public function protocol()
	{
		return $this->config('protocol', 'http');
	}

	public function base()
	{
		return $this->protocol().'://'.$this->domain();
	}

	public function site($url)
	{
		return $this->base().$url;
	}

	public function secure_domain()
	{
		return $this->config('secure_domain', $this->domain());
	}

	public function secure_base()
	{
		return 'https://'.$this->domain();
	}

	public function secure_site()
	{
		return $this->secure_base().$url;
	}

	public function redirect_to_secure()
	{
		if ($_SERVER['HTTP_HOST'] !== $this->secure_domain())
		{
			HTTP::redirect($this->secure_site(Request::initial()->uri()).URL::query($this->visitor_params(), FALSE));
		}
	}
}
