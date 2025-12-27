<?php

require_once "CsrfException.php";

class CsrfExceptionInvalidToken extends CsrfException
{
	public function __construct(string $message)
	{
		parent::__construct($message, 403);
	}
}

?>
