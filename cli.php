<?php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

require_once( dirname( __FILE__ ) . "/lib/functions.php" );
require_once( dirname( __FILE__ ) . "/lib/archive.php" );
require_once( dirname( __FILE__ ) . "/lib/project.php" );

/**
 * WP-CLI commands for the Shifter.
 *
 * @subpackage commands/community
 * @maintainer Shifter Team
 */
class WP_CLI_Shifter extends WP_CLI_Command
{
	private $version = "v1.5.0";

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
WP_CLI::add_command( 'shifter archive', 'WP_CLI_Shifter_Archive' );
WP_CLI::add_command( 'shifter project', 'WP_CLI_Shifter_Project' );
