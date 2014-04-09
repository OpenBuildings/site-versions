<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * @package    openbuildings\site-versions
 * @author     Ivan Kerin <ikerin@gmail.com>
 * @copyright  (c) 2013 OpenBuildings Ltd.
 * @license    http://spdx.org/licenses/BSD-3-Clause
 */
class Kohana_Model_Visitor extends Jam_Model {

	const SESSION_VARIABLE = 'visitor-1';

	/**
	 * Load a Model_Visitor object either from the current user or from the session if the user is not logged in
	 * If no visitor is available in the session, create one, assign it to the currently loaded user, and trigger "model.user_set" event
	 * @return Model_Visitor
	 */
	public static function load()
	{
		$user = Auth::instance()->get_user();

		if ($user AND $user->visitor)
		{
			return $user->visitor;
		}
		else
		{
			$visitor = static::session() ?: static::create_session();

			if ($user AND ! $visitor->user)
			{
				$user->visitor = $visitor;

				$visitor->meta()->events()->trigger('model.user_set', $visitor);

				$user->save();
			}

			return $visitor;
		}
	}

	/**
	 * Build a new Model_Visitor object, save it to the session and trigger model.create_session event
	 * @return Model_Visitor
	 */
	public static function create_session()
	{
		$visitor = Jam::build('visitor');

		static::session($visitor);

		$visitor->meta()->events()->trigger('model.create_session', $visitor);

		return $visitor;
	}

	/**
	 * Save / Load the model visitor from the session. Use SESSION_VARIABLE const for the name in the session
	 * @param  Model_Visitor $visitor
	 * @return Model_Visitor
	 */
	public static function session($visitor = NULL)
	{
		if ($visitor !== NULL)
		{
			Session::instance()->set(static::SESSION_VARIABLE, $visitor);
		}

		return Session::instance()->get(static::SESSION_VARIABLE);
	}

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'visitor_defaults' => Jam::behavior('visitor_defaults')
			))
			->associations(array(
				'user' => Jam::association('belongsto', array('inverse_of' => 'visitor')),
				'country' => Jam::association('belongsto', array('foreign_key' => 'country_id', 'foreign_model' => 'location')),
			))

			->fields(array(
				'id' => Jam::field('primary'),
				'ip' => Jam::field('ip'),
				'token' => Jam::field('string'),
				'currency' => Jam::field('string'),

			))
			->validator('currency', array('choice' => array('in' => array('EUR', 'GBP'))));
	}

	/**
	 * If the model is loaded, perform "save" otherwise save to the session
	 * @return Model_Visitor $this
	 */
	public function save_session()
	{
		if ($this->loaded())
		{
			$this->save();
		}
		else
		{
			$this->check_insist();
			static::session($this);
		}

		return $this;
	}

	/**
	 * Implement Serializable
	 * More lightweight than default serialization.
	 */
	public function serialize()
	{
		$fields = $this->as_array();

		$data = json_encode($fields);

		return $data;
	}

	/**
	 * Implement Serializable
	 * More lightweight than default serialization.
	 */
	public function unserialize($data)
	{
		$data = json_decode($data, TRUE);

		$this->_meta = Jam::meta($this);
		$this->_loaded = isset($data['id']);
		if ($this->_loaded)
		{
			$this->_original = (array) $data;
		}
		else
		{
			$this->_original = $this->meta()->defaults();
			$this->_changed = (array) $data;
		}
	}

}
