<?php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

require_once( dirname( __FILE__ ) . "/lib/error.php" );
require_once( dirname( __FILE__ ) . "/lib/functions.php" );
require_once( dirname( __FILE__ ) . "/lib/archive.php" );
require_once( dirname( __FILE__ ) . "/lib/project.php" );
require_once( dirname( __FILE__ ) . "/lib/login.php" );

/**
 * WP-CLI commands for the Shifter.
 *
 * @subpackage commands/community
 * @maintainer Shifter Team
 */
class WP_CLI_Shifter extends WP_CLI_Command
{
	private $version = "v1.6.0";

	/**
	 * Prints current version of the shifter/cli.
	 *
	 * @when before_wp_load
	 */
	public function version()
	{
		WP_CLI::line( $this->version );
	}
}

WP_CLI::add_command( 'shifter', 'WP_CLI_Shifter' );
WP_CLI::add_command( 'shifter login', 'Shifter_CLI\Login' );
WP_CLI::add_command( 'shifter archive', 'Shifter_CLI\Archive' );
WP_CLI::add_command( 'shifter project', 'Shifter_CLI\Project' );
