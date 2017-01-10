<?php

/**
 * Manage projects for the Shifter.
 *
 * @subpackage commands/community
 * @maintainer Shifter Team
 */
class WP_CLI_Shifter_Project extends WP_CLI_Command
{
	/**
	 * Delete an archive from the Shifter.
	 *
	 * ## OPTIONS
	 *
	 * <site-id>
	 * : The site-id.
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
		$token = Shifter_CLI::get_access_token( $args, $assoc_args );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, Shifter_CLI::project_api . '/' . $args[0] );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'DELETE' );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			"Authorization: " . $token,
		) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$result = json_decode( curl_exec( $ch ) );
		$info = curl_getinfo($ch);

		if ( 200 === $info['http_code'] ) {
			if ( empty( $result->errorMessage ) ) {
				WP_CLI::success( "🍺 Project deleted successfully." );
			} else {
				WP_CLI::error( $result->errorMessage );
			}
		} else {
			WP_CLI::error( "Sorry, something went wrong. We're working on getting this fixed as soon as we can." );
		}
	}

	/**
	 * Delete an project from the Shifter.
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

		$result = Shifter_CLI::get_project_list( $args, $assoc_args );

		if ( 200 === $result['info']['http_code'] ) {
			// `docker_url` sometimes doesn't exist. So it can't display it.
			WP_CLI\Utils\format_items( $format, $result['body'], array(
				'site_id',
				'site_name',
				'site_owner',
				'update_time'
			) );
		} else {
			WP_CLI::error( "Incorrect token." );
		}
	}

	/**
	 * Create a project from your archive for the Shifter.
	 *
	 * ## OPTIONS
	 *
	 * --archive-id=<archive-id>
	 * : The ID of the archive. Try `wp shifter archive list`.
	 *
	 * --project-name=<project-name>
	 * : The name of the new project.
	 *
	 * --php-version=<php-version>
	 * : The PHP version. `5.5` or `5.6` or `7.0`.
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
	 *   $ wp shifter project create --archive-id=xxxx --php-version=7.0 --project-name="hello" ...
	 *   Success: xxxx-xxxx-xxxx-xxxx
	 *
	 * @subcommand create
	 * @when before_wp_load
	 */
	function create( $args, $assoc_args )
	{
		$token = Shifter_CLI::get_access_token( $args, $assoc_args );
		$assoc_args['token'] = $token;

		$result = Shifter_CLI::get_archive_list( $args, $assoc_args );

		if ( 200 !== $result['info']['http_code'] ) {
			WP_CLI::error( "Incorrect token." );
		}

		// Check archive-id exists.
		$archive_id = null;
		foreach ( $result['body'] as $archive ) {
			if ( $assoc_args['archive-id'] === $archive['archive_id'] ) {
				$archive_id = $assoc_args['archive-id'];
			}
		}

		if ( empty( $archive_id ) ) {
			WP_CLI::error( "Archive is not found." );
		}

		$api = Shifter_CLI::project_api . '/?archive_id=' . $archive_id;
		$result = Shifter_CLI::post( $api, array(
			"projectName" => $assoc_args['project-name'],
			"phpVersion" => $assoc_args['php-version'],
		), $token );

		if ( 200 === $result['info']['http_code'] ) {
			$api = Shifter_CLI::container_api . '/' . $result['body']['site_id'];
			$result = Shifter_CLI::post( $api, array(), $token );
			if ( 200 === $result['info']['http_code'] ) {
				WP_CLI::success( $result['body']['site_id'] );
				exit;
			}
		}

		WP_CLI::error( "Sorry, something went wrong. We're working on getting this fixed as soon as we can." );
	}
}
