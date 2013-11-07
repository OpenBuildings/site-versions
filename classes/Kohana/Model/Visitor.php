<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana_Model_Visitor extends Jam_Model {

	const SESSION_VARIABLE = 'visitor-1';
	
	/**
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

	public static function create_session()
	{
		$visitor = Jam::build('visitor');

		static::session($visitor);

		$visitor->meta()->events()->trigger('model.create_session', $visitor);

		return $visitor;
	}

	public static function session($visitor = NULL)
	{
		if ($visitor !== NULL)
		{
			Session::instance()->set(static::SESSION_VARIABLE, $visitor);
		}

		return Session::instance()->get(static::SESSION_VARIABLE);
	}

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

	public function serialize()
	{
		$fields = $this->as_array();

		$data = json_encode($fields);

		return $data;
	}

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

	/**
	 * @codeCoverageIgnore
	 */
	public static function initialize(Jam_Meta $meta)
	{
		$meta
			->behaviors(array(
				'currency_auto' => Jam::behavior('currency_auto'),
				'visitor_defaults' => Jam::behavior('visitor_defaults'),
			))
			->associations(array(
				'user' => Jam::association('belongsto', array('inverse_of' => 'visitor')),
				'country' => Jam::association('belongsto', array('foreign_key' => 'country_id', 'foreign_model' => 'location')),
				'purchase' => Jam::association('belongsto', array('inverse_of' => 'current_visitor')),
			))

			->fields(array(
				'id' => Jam::field('primary'),
				'ip' => Jam::field('ip'),
				'token' => Jam::field('string'),
			));
	}

	public function build_purchase()
	{
		$this->build('purchase', array(
			'currency' => $this->currency,
			'creator' => $this->user,
			'billing_address' => array(
				'country' => $this->country,
			)
		));

		$this->meta()->events()->trigger('model.build_purchase', $this);

		$this->purchase->current_visitor = $this;

		return $this->purchase;
	}

	public function purchase()
	{
		if ( ! $this->purchase) 
		{
			return $this->build_purchase();
		}

		return $this->purchase;
	}
}
