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
			$matches = $params['matches'];

			if ($matches($_SERVER['HTTP_HOST']))
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
	public static function instance($name = NULL, $domain = NULL, $protocol = NULL)
	{
		if ($name === NULL)
		{
			$name = 'current';
		}

		if ( ! isset(static::$instances[$name]))
		{
			$version_name = ($name == 'current') ? static::current_version_name() : $name;
			static::$instances[$version_name] = static::$instances[$name] = new Site_Version($version_name, $domain, $protocol);
		}

		return static::$instances[$name];
	}

	protected $config;
	protected $name;
	protected $current_domain;
	protected $current_secure;

	public function __construct($name, $domain = NULL, $secure = NULL)
	{
		$this->name = $name;
		$this->config = Kohana::$config->load('site-versions.versions.'.$name);

		$this->current_domain = ($domain === NULL AND isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : $domain;
		$this->current_secure = ($secure === NULL AND isset($_SERVER['HTTPS'])) ? $_SERVER['HTTPS'] === 'on' : $secure;
	}

	/**
	 * @return string
	 */
	public function name()
	{
		return $this->name;
	}

	public function current_domain()
	{
		return $this->current_domain;
	}

	public function current_secure()
	{
		return $this->current_secure;
	}

	/**
	 * Get the normal domain from config
	 * @return string
	 */
	public function domain()
	{
		$domain = $this->config('domain', $this->config('secure_domain'));

		return $domain instanceof Closure ? $domain($this) : $domain;
	}

	/**
	 * Get the domain used for secure connection if one is set, return normal domain if not
	 * @return string
	 */
	public function secure_domain()
	{
		$secure_domain = $this->config('secure_domain');

		return $secure_domain instanceof Closure ? $secure_domain($this) : $secure_domain;
	}

	/**
	 * Get the protocol (http / https), defaults to https
	 * @return string
	 */
	public function protocol()
	{
		return $this->config('protocol', 'https');
	}

	/**
	 * Get the normal base url
	 * @return string
	 */
	public function base()
	{
		if ($this->config('domain'))
		{
			return $this->protocol().'://'.$this->domain();
		}
		else
		{
			return $this->secure_base();
		}
	}

	/**
	 * Get the normal base url
	 * @return string
	 */
	public function secure_base()
	{
		return 'https://'.$this->secure_domain();
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

		if (($routes = $this->config('routes')))
		{
			$this->set_routes($routes);
		}

		if (array_key_exists('_SV_VISITOR_TOKEN', $_GET))
		{
			static::load_visitor($_GET['_SV_VISITOR_TOKEN']);
		}
	}

	/**
	 * Update kohana config parameters
	 * @param  array  $config_updates
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
	 * Add / Overwrite routes, specific for the site version
	 * @param  array  $routes
	 */
	public function set_routes(array $routes)
	{
		foreach ($routes as $name => $options)
		{
			Route::set($name, $options[0], Arr::get($options, 1, array()))
				->defaults(Arr::get($options, 2, array()));
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

	public function set_param($name, $value)
	{
		$this->config['params'][$name] = $value;
		return $this;
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

		$params = array('_SV_VISITOR_TOKEN' => $visitor->token);

		return $params;
	}

	/**
	 * Redirect to the secure version of the site, keeping the current visitor, even accross domains
	 * @throws HTTP_Exception If redirect exception
	 */
	public function redirect_to_secure()
	{
		if ( ! Request::initial()->secure())
		{
			HTTP::redirect($this->secure_site(Request::initial()->uri()).URL::query($this->visitor_params(), FALSE));
		}
	}

	public function secure_uri($uri)
	{
		if ($this->current_secure())
		{
			return $uri;
		}
		else
		{
			return $this->secure_site($uri).URL::query($this->visitor_params(), FALSE);
		}
	}
}
