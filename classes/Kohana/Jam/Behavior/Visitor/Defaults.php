<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\site-versions
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Jam_Behavior_Visitor_Defaults extends Jam_Behavior {

	public $_default_currency = 'GBP';
	public $_default_country_code = 'GB';

	public $_countries_currencies = array(
		'United Kingdom' => 'GBP',
	);

	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta
			->events()
				->bind('model.create_session', array($this, 'set_geoip_defaults'))
				->bind('model.create_session', array($this, 'set_new_token'));
	}

	public function set_geoip_defaults(Model_Visitor $visitor)
	{
		$geoip_record = $this->geoip_record($visitor->ip);

		$this->_set_new_country($visitor, $geoip_record);
		$this->_set_new_currency($visitor, $geoip_record);
	}

	public function set_new_token(Model_Visitor $visitor)
	{
		$visitor->token = uniqid();
	}

	protected function _set_new_country(Model_Visitor $visitor, $geoip_record)
	{
		$short_name = Arr::get( (array) $geoip_record, 'country_code', $this->_default_country_code);

		$visitor->country = Jam::all('location')->where('short_name', '=', $short_name)->first_insist();
	}

	protected function _set_new_currency(Model_Visitor $visitor, $geoip_record)
	{
		$visitor->currency = Arr::get($this->_countries_currencies, Arr::get( (array) $geoip_record, 'country_name'), $this->_default_currency);
	}

	public function geoip_record($ip)
	{
		if ( ! extension_loaded('geoip'))
			return array();
		
		return @ geoip_record_by_name($ip);
	}
}
