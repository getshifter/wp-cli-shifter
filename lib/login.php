<?php

namespace Shifter_CLI;
use WP_CLI_Command;
use WP_CLI;

/**
 * Manage archives for the Shifter.
 *
 * @subpackage commands/community
 * @maintainer Shifter Team
 */
class Login extends WP_CLI_Command
{
	/**
	 * Login into shifter and save token.
	 *
	 * @when before_wp_load
	 */
	public function __invoke( $args, $assoc_args )
	{
		Functions::save_local_token( '' );
		$token = Functions::get_access_token( $args, $assoc_args );
		if ( Error::is_error( $token ) ) {
			WP_CLI::error( $token->get_message() );
		}
		Functions::save_local_token( $token );

		WP_CLI::success( "Access token will expire 2 weeks later." );
	}
}
