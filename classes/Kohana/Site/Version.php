<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\site-versions
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Site_Version {

	protected static $instances = array();

	/**
	 * Get all the versions from the config
	 * @return array
	 */
	public static function versions()
	{
		return Kohana::$config->load('site-versions.versions');	
	}

	/**
	 * Determince the current site version name
	 * @return string 
	 */
	public static function current_version_name()
	{
		$versions = static::versions();

		foreach ($versions as $version_name => $params) 
		{
			$domains = Arr::extract($params, array('domain', 'secure_domain'));

			if (array_search($_SERVER['HTTP_HOST'], $domains) !== FALSE)
			{
				return $version_name;
			}
		}

		end($versions);
		return key($versions);
	}

	/**
	 * Load the current version of the site (based on current_version_name) or load specific instance (from name)
	 * 
	 * @param  string $name 
	 * @return Site_Version
	 */
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

	protected $config;
	protected $name;

	public function __construct($name)
	{
		$this->name = $name;
		$this->config = Kohana::$config->load('site-versions.versions.'.$name);
	}

	/**
	 * @return string
	 */
	public function name()
	{
		return $this->name;
	}

	/**
	 * Get the normal domain from config
	 * @return string 
	 */
	public function domain()
	{
		return $this->config('domain');
	}

	/**
	 * Get the protocol (http / https), defaults to http
	 * @return string
	 */
	public function protocol()
	{
		return $this->config('protocol', 'http');
	}

	/**
	 * Get the normal base url 
	 * @return string
	 */
	public function base()
	{
		return $this->protocol().'://'.$this->domain();
	}

	/**
	 * Get the url with the normal base domain.
	 * E.g. site(/test/url) -> http://example.com/test/url
	 * @param  string $url 
	 * @return string      
	 */
	public function site($url)
	{
		return $this->base().'/'.ltrim($url, '/');
	}

	/**
	 * Get the domain used for secure connection if one is set, return normal domain if not
	 * @return string 
	 */
	public function secure_domain()
	{
		return $this->config('secure_domain', $this->domain());
	}

	/**
	 * Get the secure base url 
	 * @return string
	 */
	public function secure_base()
	{
		return 'https://'.$this->secure_domain();
	}

	/**
	 * Get the url with the secure base domain.
	 * E.g. site(/test/url) -> https://example.com/test/url
	 * @param  string $url 
	 * @return string
	 */
	public function secure_site($url)
	{
		return $this->secure_base().'/'.ltrim($url, '/');
	}

	/**
	 * Get a config parameter, or all the configuration parameters
	 * Can also set a "default" value in the second argument
	 * @param  string $name
	 * @param  string $default
	 * @return mixed
	 */
	public function config($name = NULL, $default = NULL)
	{
		return $name ? Arr::path($this->config, $name, $default) : $this->config;
	}

	/**
	 * Execute configuration for this site version. Update kohana config and load visitor from token
	 */
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

	/**
	 * Update kohana config parameters
	 * @param  array  $config_updates [description]
	 */
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

	/**
	 * Load visitor into the session from a given token
	 * @param  string $token 
	 */
	public function load_visitor($token)
	{
		$visitor = Jam::all('visitor')->where('token', '=', $token)->first();
		
		if ($visitor)
		{
			Model_Visitor::session($visitor);
		}
	}

	/**
	 * Get a parameter from configuration (under the params array)
	 * @param  string
	 * @return mixed
	 */
	public function param($name)
	{
		return $this->config('params.'.$name);
	}

	/**
	 * Get the query parameters required to transport current visitor accross domains
	 * @return array 
	 */
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

	/**
	 * Redirect to the secure version of the site, keeping the current visitor, even accross domains
	 * @throws HTTP_Exception If redirect exception
	 */
	public function redirect_to_secure()
	{
		if ($_SERVER['HTTP_HOST'] !== $this->secure_domain())
		{
			HTTP::redirect($this->secure_site(Request::initial()->uri()).URL::query($this->visitor_params(), FALSE));
		}
	}
}
