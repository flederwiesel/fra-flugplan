<?php

class Model
{
	public $id = 0;
	public $icao = null;
	public $name = null;

	public function __construct($id, $icao, $name)
	{
		$this->id = $id;
		$this->icao = $icao;
		$this->name = $name;

		return $this;
	}
};

class Aircraft
{
	public $id = 0;
	public $model = 0;
	public $reg = null;

	public function __construct($id, $model, $reg)
	{
		$this->id = $id;
		$this->model = $model;
		$this->reg = $reg;

		return $this;
	}
};

class Airline
{
	public $id = 0;
	public $code = null;
	public $name = null;

	public function __construct($id, $code, $name)
	{
		$this->id = $id;
		$this->code = $code;
		$this->name = $name;

		return $this;
	}
};

class Airport
{
	public $id = 0;
	public $iata = null;
	public $icao = null;
	public $name = null;

	public function __construct($id, $iata, $icao, $name)
	{
		$this->id = $id;
		$this->iata = $iata;
		$this->icao = $icao;
		$this->name = $name;

		return $this;
	}
};

?>
