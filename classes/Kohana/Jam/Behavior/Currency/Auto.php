<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\site-versions
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Jam_Behavior_Currency_Auto extends Jam_Behavior {

	public $_ip_field = 'ip';

	public $_field = 'currency';

	public $_default = 'EUR';

	public $_countries = array(
		'United Kingdom' => 'GBP',
	);

	/**
	 * @codeCoverageIgnore
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta
			->field($this->_field, Jam::field('string'))
			->validator($this->_field, array('currency' => TRUE));
	}

	public function model_before_check(Jam_Model $model)
	{
		if ( ! $model->loaded() AND ! $model->{$this->_field})
		{
			$info = $this->geoip_record($model->{$this->_ip_field});

			$model->{$this->_field} = Arr::get($this->_countries, Arr::get($info, 'country_name'), $this->_default);
		}
	}

	public function geoip_record($ip)
	{
		if ( ! extension_loaded('geoip'))
			return array();
		
		return @ geoip_record_by_name($ip);
	}
}
