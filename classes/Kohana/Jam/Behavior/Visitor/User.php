<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\site-versions
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Jam_Behavior_Visitor_User extends Jam_Behavior {

	/**
	 * @codeCoverageIgnore
	 */
	public function initialize(Jam_Meta $meta, $name)
	{
		parent::initialize($meta, $name);

		$meta
			->association('visitor', Jam::association('hasone', array('inverse_of' => 'user')));
	}
}
