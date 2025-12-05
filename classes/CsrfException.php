<?php

class CsrfException extends RuntimeException
{
	public function __construct(string $message = "", int $code = 500)
	{
		parent::__construct($message, $code);

		$uniqid = uniqid();
		$trace = $this->getTraceAsString();

		syslog(
			LOG_ERR,
			"{$uniqid} {$this->file}({$this->line}): {$this->message}\n {$trace}"
		);
	}
}

?>
