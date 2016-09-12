<?php

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

require_once( dirname( __FILE__ ) . "/lib/functions.php" );

/**
 * WP-CLI commands for the Shifter.
 *
 * @subpackage commands/community
 * @maintainer Shifter Team
 */
class WP_CLI_Shifter extends WP_CLI_Command
{
	/**
	 * Create a .zip archive as a backup for the Shifter.
	 *
	 * ## OPTIONS
	 * [<file>]
	 * The name of the .zip file to archive. If omitted, it will be 'archive.zip'.
	 *
	 * ## EXAMPLES
	 * $ wp shifter backup
	 * Success: Backuped to 'archive.zip'.
	 *
	 * $ wp shifter backup /path/to/hello.zip
	 * Success: Backuped to '/path/to/hello.zip'.
	 *
	 * @subcommand backup
	 */
	function backup( $args ) {
		$tmp_dir = tempdir( 'sft' );
		rcopy( ABSPATH, $tmp_dir . '/webroot' );
		WP_CLI::launch_self(
			"db export",
			array( $tmp_dir . "/wp.sql" ),
			array(),
			true,
			true,
			array( 'path' => WP_CLI::get_runner()->config['path'] )
		);

		if ( empty( $args[0] ) ) {
			$archive = "archive.zip";
		} else {
			$archive = $args[0];
		}

		zip( $tmp_dir, $archive );
		rrmdir( $tmp_dir );

		WP_CLI::success( sprintf( "Backup to '%s'.", $archive ) );
	}
}

WP_CLI::add_command( 'shifter', 'WP_CLI_Shifter'  );
