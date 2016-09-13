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
	 * : The name of the .zip file to archive. If omitted, it will be 'archive.zip'.
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
	function backup( $args )
	{
		$tmp_dir = Shifter_CLI::tempdir( 'SFT' );
		Shifter_CLI::rcopy( ABSPATH, $tmp_dir . '/webroot' );
		WP_CLI::launch_self(
			"db export",
			array( $tmp_dir . "/wp.sql" ),
			array(),
			true,
			true,
			array( 'path' => WP_CLI::get_runner()->config['path'] )
		);

		if ( empty( $args[0] ) ) {
			$archive = getcwd() . "/archive.zip";
		} else {
			$archive = $args[0];
		}

		$res = Shifter_CLI::zip( $tmp_dir, $archive );
		Shifter_CLI::rrmdir( $tmp_dir );
		if ( is_wp_error( $res ) ) {
			WP_CLI::error( $res->get_error_message() );
		}

		WP_CLI::success( sprintf( "Backup to '%s'.", $res ) );
	}

	/**
	 * Recovery the WordPress site from a .zip archive.
	 *
	 * ## OPTIONS
	 * <file>
	 * : The name of the .zip file to recovery.
	 *
	 * [--delete]
	 * : Delete extraneous files from WordPress files.
	 *
	 * ## EXAMPLES
	 * $ wp shifter recovery /path/to/backup.zip
	 * Success: recoveried from '/path/to/backup.zip'.
	 *
	 * @subcommand recovery
	 */
	function recovery( $args, $assoc_args )
	{
		if ( ! is_file( $args[0] ) ) {
			WP_CLI::error( "No such file or directory." );
		}
		$tmp_dir = Shifter_CLI::tempdir( 'SFT' );
		$res = Shifter_CLI::unzip( $args[0], $tmp_dir );
		if ( is_wp_error( $res ) ) {
			WP_CLI::error( $res->get_error_message() );
		}

		if ( ! is_dir( $tmp_dir . '/webroot' ) || ! is_file( $tmp_dir . '/wp.sql' ) ) {
			Shifter_CLI::rrmdir( $tmp_dir );
			WP_CLI::error( sprintf( "Can't recovery from '%s'.", $args[0] ) );
		}

		if ( empty( $assoc_args['delete'] ) ) {
			Shifter_CLI::rempty( ABSPATH );
		}

		Shifter_CLI::rcopy( $tmp_dir . '/webroot', ABSPATH );

		if ( is_file( $tmp_dir . "/wp.sql" ) ) {
			$result = WP_CLI::launch_self(
				"db import",
				array( $tmp_dir . "/wp.sql" ),
				array(),
				true,
				true,
				array( 'path' => WP_CLI::get_runner()->config['path'] )
			);
			if ( $result->return_code ) {
				Shifter_CLI::rrmdir( $tmp_dir );
				WP_CLI::error( sprintf( "Can't import database from '%s'.", $args[0] ) );
			}
		}

		Shifter_CLI::rrmdir( $tmp_dir );
		WP_CLI::success( sprintf( "Recoveried from '%s'.", $args[0] ) );
	}
}

WP_CLI::add_command( 'shifter', 'WP_CLI_Shifter'  );
