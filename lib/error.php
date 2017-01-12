<?php

namespace Shifter_CLI;

final class Error
{
	private $error_message;

	public function __construct( $error_message )
	{
		$this->error_message = $error_message;
	}

	public function get_message()
	{
		return $this->error_message;
	}

	public static function is_error( $thing )
	{
		$class_name = get_class();
		return ( $thing instanceof $class_name );
	}
}
