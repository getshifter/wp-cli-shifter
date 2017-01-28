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
class Archive extends WP_CLI_Command
{
	/**
	 * Delete an archive from the Shifter.
	 *
	 * ## OPTIONS
	 *
	 * <archive-id>
	 * : The archive_id.
	 *
	 * [--token=<token>]
	 * : The access token to communinate with the Shifter API.
	 *
	 * [--shifter-user=<username>]
	 * : The username for the Shifter.
	 *
	 * [--shifter-password=<password>]
	 * : The password for the Shifter.
	 *
	 * @subcommand delete
	 * @when before_wp_load
	 */
	public function delete( $args, $assoc_args )
	{
		$token = Functions::get_access_token( $args, $assoc_args );
		if ( Error::is_error( $token ) ) {
			WP_CLI::error( $token->get_message() );
		}

		$api = Functions::archive_api . '/' . $args[0];
		$result = Functions::http( 'DELETE', $api, null, $token );

		if ( Error::is_error( $result ) ) {
			WP_CLI::error( $result->get_message() );
		} else {
			WP_CLI::success( "🍺 Archive deleted successfully." );
		}
	}

	/**
	 * Get a list of archives from the Shifter.
	 *
	 * ## OPTIONS
	 *
	 * [--token=<token>]
	 * : The access token to communinate with the Shifter API.
	 *
	 * [--shifter-user=<username>]
	 * : The username for the Shifter.
	 *
	 * [--shifter-password=<password>]
	 * : The password for the Shifter.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, count. Default: table
	 *
	 * ## EXAMPLES
	 *
	 *   $ wp shifter archive list
	 *   Shifter Username: jack
	 *   Password (will be hidden):
	 *   +---------------------+---------------+---------------------------+
	 *   | archive_id          | archive_owner | archive_create_date       |
	 *   +---------------------+---------------+---------------------------+
	 *   | xxxx-xxxx-xxxx-xxxx | jack          | 2016-12-19T05:30:40+00:00 |
	 *   +---------------------+---------------+---------------------------+
	 *
	 *   $ wp shifter archive list --shifter-user=jack --shifter-password=xxxx --format=json | jq .
	 *   [
	 *     {
	 *       "archive_id": "xxxx-xxxx-xxxx-xxxx",
	 *       "archive_owner": "jack",
	 *       "archive_create_date": "2016-12-19T05:30:40+00:00"
	 *     }
	 *   ]
	 *
	 * @subcommand list
	 * @when before_wp_load
	 */
	public function _list( $args, $assoc_args )
	{
		if ( isset( $assoc_args['format'] ) ) {
			$format = $assoc_args['format'];
		} else {
			$format = 'table';
		}

		if ( ! in_array( $format, array( "table", "csv", "json" ) ) ) {
			WP_CLI::error( 'Invalid format: ' . $assoc_args['format'] );
		}

		$token = Functions::get_access_token( $args, $assoc_args );
		if ( Error::is_error( $token ) ) {
			WP_CLI::error( $token->get_message() );
		}

		$result = Functions::get_archive_list( $token );
		if ( Error::is_error( $result ) ) {
			WP_CLI::error( $result->get_message() );
		}

		WP_CLI\Utils\format_items( $format, $result['body'], array(
			'archive_id',
			'archive_owner',
			'archive_create_date'
		) );
	}

	/**
	 * Upload an archive to the Shifter.
	 *
	 * ## OPTIONS
	 *
	 * [<file>]
	 * : The *.zip archive to upload.
	 *
	 * [--token=<token>]
	 * : The access token to communinate with the Shifter API.
	 *
	 * [--shifter-user=<username>]
	 * : The username for the Shifter.
	 *
	 * [--shifter-password=<password>]
	 * : The password for the Shifter.
	 *
	 * ## EXAMPLES
	 *
	 *   $ wp shifter archive upload
	 *   Shifter Username: jack
	 *   Password (will be hidden):
	 *   Success: Logged in as jack
	 *   Success: Archive ID: db81bb93-13f8-41d1-9605-4cc397e52192
	 *
	 * @subcommand upload
	 */
	function upload( $args, $assoc_args )
	{
		$token = Functions::get_access_token( $args, $assoc_args );
		if ( Error::is_error( $token ) ) {
			WP_CLI::error( $token->get_message() );
		}

		$signed_url = Functions::get_pre_signed_url( $token );
		if ( Error::is_error( $signed_url ) ) {
			WP_CLI::error( $signed_url->get_message() );
		}

		if ( empty( $args[0] ) ) {
			WP_CLI::launch_self(
				"shifter archive create",
				array( Functions::tempdir() . '/archive.zip' ),
				$assoc_args,
				true,
				true,
				array( 'path' => WP_CLI::get_runner()->config['path'] )
			);
			WP_CLI::success( "Created an archive." );
		} else {
			$archive = $args[0];
			if ( ! is_file( $archive ) ) {
				WP_CLI::error( $archive . " doesn't exist." );
			}
		}

		$ch = curl_init();

		curl_setopt( $ch, CURLOPT_URL, $signed_url['url'] );
		curl_setopt( $ch, CURLOPT_PUT, 1 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			"Content-Type: application/zip",
		) );
		$fh_res = fopen( $archive, 'r' );
		curl_setopt( $ch, CURLOPT_INFILE, $fh_res );
		curl_setopt( $ch, CURLOPT_INFILESIZE, filesize( $archive ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$result = curl_exec( $ch );
		$info = curl_getinfo($ch);
		fclose( $fh_res );

		if ( 200 === $info['http_code'] ) {
			WP_CLI::success( "Archive ID: " . $signed_url['archive_id'] );
		} else {
			WP_CLI::error( "Sorry, something went wrong. We're working on getting this fixed as soon as we can." );
		}
	}

	/**
	 * Create a .zip archive from your WordPress for the Shifter.
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
	 *   $ wp shifter archive create
	 *   Success: Archived to 'archive.zip'.
	 *
	 *   # You can specific file name of the archive.
	 *   $ wp shifter archive create /path/to/hello.zip
	 *   Success: Archived to '/path/to/hello.zip'.
	 *
	 *   # You can use option `--exclude`.
	 *   $ wp shifter archive create --exclude=wp-config.php,wp-content/uploads/photo.jpg
	 *   Success: Archived to '/path/to/hello.zip'.
	 *
	 * @subcommand create
	 */
	function create( $args, $assoc_args )
	{
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress = WP_CLI\Utils\make_progress_bar( 'Archiving an archive: ', 5 );
		}

		$tmp_dir = Functions::tempdir( 'SFT' );
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress->tick();
		}

		$excludes = Functions::assoc_args_to_array( $assoc_args, "exclude" );

		Functions::rcopy( ABSPATH, $tmp_dir . '/webroot', $excludes );
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress->tick();
		}

		WP_CLI::launch_self(
			"db export",
			array( $tmp_dir . "/wp.sql" ),
			array(),
			true,
			true,
			array( 'path' => WP_CLI::get_runner()->config['path'] )
		);
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress->tick();
		}

		if ( empty( $args[0] ) ) {
			$archive = getcwd() . "/archive.zip";
		} else {
			$archive = $args[0];
		}

		$file = Functions::zip( $tmp_dir, $archive );
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress->tick();
		}

		Functions::rrmdir( $tmp_dir );
		if ( is_wp_error( $file ) ) {
			WP_CLI::error( $file->get_error_message() );
		}
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress->tick();
		}

		WP_CLI::success( sprintf( "Archived to '%s'.", $file ) );
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
	 *   $ wp shifter archive extract /path/to/archive.zip
	 *   Success: Extracted from '/path/to/archive.zip'.
	 *
	 *   # Delete extraneous files from WordPress files.
	 *   $ wp shifter archive extract /path/to/archive.zip --delete
	 *   Success: Extracted from '/path/to/archive.zip'.
	 *
	 *   # You can use option `--exclude`.
	 *   $ wp shifter archive extract archive.zip --exclude=wp-config.php
	 *   Success: Extracted from 'archive.zip'.
	 *
	 * @subcommand extract
	 */
	function extract( $args, $assoc_args )
	{
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress = WP_CLI\Utils\make_progress_bar( 'Extracting an archive: ', 7 );
		}

		if ( ! is_file( $args[0] ) ) {
			WP_CLI::error( "No such file or directory." );
		}
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress->tick();
		}

		$tmp_dir = Functions::tempdir( 'SFT' );
		$res = Functions::unzip( $args[0], $tmp_dir );
		if ( is_wp_error( $res ) ) {
			WP_CLI::error( $res->get_error_message() );
		}
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress->tick();
		}

		if ( ! is_dir( $tmp_dir . '/webroot' ) || ! is_file( $tmp_dir . '/wp.sql' ) ) {
			Functions::rrmdir( $tmp_dir );
			WP_CLI::error( sprintf( "Can't extract from '%s'.", $args[0] ) );
		}
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress->tick();
		}

		$excludes = Functions::assoc_args_to_array( $assoc_args, "exclude" );

		if ( ! empty( $assoc_args['delete'] ) ) {
			Functions::rempty( ABSPATH, $excludes );
		}
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress->tick();
		}

		Functions::rcopy( $tmp_dir . '/webroot', ABSPATH, $excludes );
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress->tick();
		}

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
				Functions::rrmdir( $tmp_dir );
				WP_CLI::error( sprintf( "Can't import database from '%s'.", $args[0] ) );
			}
		}
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress->tick();
		}

		Functions::rrmdir( $tmp_dir );
		if ( ! WP_CLI::get_config( 'quiet' ) ) {
			$progress->tick();
		}

		WP_CLI::success( sprintf( "Extracted from '%s'.", $args[0] ) );
	}
}
