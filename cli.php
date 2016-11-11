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
	 * Create a .zip archive as a archive for the Shifter.
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : The name of the .zip file to archive. If omitted, it will be 'archive.zip'.
	 *
	 * [--exclude=<files>]
	 * : Exclude specfic files from the archive.
	 *
	 * ## EXAMPLES
	 *
	 *   # archive will be placed as `./archive.zip`.
	 *   $ wp shifter archive
	 *   Success: Archived to 'archive.zip'.
	 *
	 *   # You can specific file name of the archive.
	 *   $ wp shifter archive /path/to/hello.zip
	 *   Success: Archived to '/path/to/hello.zip'.
	 *
	 *   # You can use option `--exclude`.
	 *   $ wp shifter archive --exclude=wp-config.php,wp-content/uploads/photo.jpg
	 *   Success: Archived to '/path/to/hello.zip'.
	 *
	 * @subcommand archive
	 */
	function archive( $args, $assoc_args )
	{
		$res = Shifter_CLI::create_archive( $args, $assoc_args );
		WP_CLI::success( sprintf( "Archived to '%s'.", $res ) );
	}

	/**
	 * Extract the WordPress site from a .zip archive.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : The name of the .zip file to extract.
	 *
	 * [--delete]
	 * : Delete extraneous files from WordPress files.
	 *
	 * [--exclude=<files>]
	 * : Exclude specfic files to extract.
	 *
	 * ## EXAMPLES
	 *
	 *   $ wp shifter extract /path/to/archive.zip
	 *   Success: Extracted from '/path/to/archive.zip'.
	 *
	 *   # Delete extraneous files from WordPress files.
	 *   $ wp shifter extract /path/to/archive.zip --delete
	 *   Success: Extracted from '/path/to/archive.zip'.
	 *
	 *   # You can use option `--exclude`.
	 *   $ wp shifter extract archive.zip --exclude=wp-config.php
	 *   Success: Extracted from 'archive.zip'.
	 *
	 * @subcommand extract
	 */
	function extract( $args, $assoc_args )
	{
		$progress = new \cli\progress\Bar( 'Creating an archive: ', 7 );

		if ( ! is_file( $args[0] ) ) {
			WP_CLI::error( "No such file or directory." );
		}
		$progress->tick();

		$tmp_dir = Shifter_CLI::tempdir( 'SFT' );
		$res = Shifter_CLI::unzip( $args[0], $tmp_dir );
		if ( is_wp_error( $res ) ) {
			WP_CLI::error( $res->get_error_message() );
		}
		$progress->tick();

		if ( ! is_dir( $tmp_dir . '/webroot' ) || ! is_file( $tmp_dir . '/wp.sql' ) ) {
			Shifter_CLI::rrmdir( $tmp_dir );
			WP_CLI::error( sprintf( "Can't extract from '%s'.", $args[0] ) );
		}
		$progress->tick();

		$excludes = Shifter_CLI::assoc_args_to_array( $assoc_args, "exclude" );

		if ( ! empty( $assoc_args['delete'] ) ) {
			Shifter_CLI::rempty( ABSPATH, $excludes );
		}
		$progress->tick();

		Shifter_CLI::rcopy( $tmp_dir . '/webroot', ABSPATH, $excludes );
		$progress->tick();

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
		$progress->tick();

		Shifter_CLI::rrmdir( $tmp_dir );
		$progress->tick();

		WP_CLI::success( sprintf( "Extracted from '%s'.", $args[0] ) );
	}
}

WP_CLI::add_command( 'shifter', 'WP_CLI_Shifter'  );
