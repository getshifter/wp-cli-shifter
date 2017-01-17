<?php

namespace Shifter_CLI;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use ZipArchive;
use cli\prompt;

class Functions
{
	const archive_api = "https://hz0wknz3a2.execute-api.us-east-1.amazonaws.com/production/archives";
	const project_api = "https://hz0wknz3a2.execute-api.us-east-1.amazonaws.com/production/projects";
	const container_api = "https://hz0wknz3a2.execute-api.us-east-1.amazonaws.com/production/containers";

	public static function get_pre_signed_url( $token )
	{
		$api = self::archive_api . "?task=integration";

		$result = self::post( $api, array(), $token );

		if ( Error::is_error( $result ) ) {
			return $result;
		} elseif ( ! empty( $result['body']['url'] ) ) {
			return $result['body'];
		} else {
			return new Error( "Sorry, something went wrong. We're working on getting this fixed as soon as we can." );
		}
	}

	public static function get_access_token( $args, $assoc_args )
	{
		if ( ! empty( $assoc_args['token'] ) ) {
			return $assoc_args['token'];
		} elseif ( self::get_local_token() && ( empty( $assoc_args['shifter-user'] ) && empty( $assoc_args['shifter-password'] ) ) ) {
			return self::get_local_token();
		} else {
			if ( ! empty( $assoc_args['shifter-user'] ) && ! empty( $assoc_args['shifter-password'] ) ) {
				$username = $assoc_args['shifter-user'];
				$password = $assoc_args['shifter-password'];
			} else {
				$user = self::prompt_user_and_pass();
				$username = $user['user'];
				$password = $user['pass'];
			}
			return self::auth( $username, $password );
		}
	}

	public static function get_project( $args, $assoc_args )
	{
		$token = self::get_access_token( $args, $assoc_args );
		if ( Error::is_error( $token ) ) {
			return $token;
		}
		return self::get( self::project_api . '/' . $args[0], $token );
	}

	public static function get_project_list( $args, $assoc_args )
	{
		$token = self::get_access_token( $args, $assoc_args );
		if ( Error::is_error( $token ) ) {
			return $token;
		}
		return self::get( self::project_api, $token );
	}

	public static function get_archive_list( $token )
	{
		return self::get( self::archive_api, $token );
	}

	public static function get_local_token()
	{
		$home = self::get_home_dir();
		if ( is_file( $home . '/.wp-cli/.shifter.key' ) ) {
			return file_get_contents( $home . '/.wp-cli/.shifter.key' );
		} else {
			return "";
		}
	}

	public static function save_local_token( $token )
	{
		$home = self::get_home_dir();
		$file = $home . '/.wp-cli/.shifter.key';
		file_put_contents( $file, $token );
	}

	/**
	 * Authentication at shifter
	 */
	public static function auth( $username, $password )
	{
		$result = self::post(
			"https://hz0wknz3a2.execute-api.us-east-1.amazonaws.com/production/login",
			json_encode( array(
				"username" => $username,
				"password" => $password
			) )
		);

		if ( Error::is_error( $result ) ) {
			return $result;
		} elseif ( ! empty( $result['body']['AccessToken'] ) ) {
			return $result['body']['AccessToken'];
		} else {
			return new Error( $result['body']['message'] );
		}
	}

	/**
	 * Plompt login with user and password with prompt.
	 *
	 * @return bool or WP_CLI::Error()
	 */
	public static function prompt_user_and_pass()
	{
		$user = trim( \cli\prompt(
			'Shifter Username',
			$default = false,
			$marker = ': ',
			$hide = false
		) );

		$pass = trim( \cli\prompt(
			'Password (will be hidden)',
			$default = false,
			$marker = ': ',
			$hide = true
		) );

		return array( 'user' => $user, 'pass' => $pass );
	}

	public static function get_home_dir()
	{
		$home = getenv( 'HOME' );
		if ( !$home ) {
			// sometime in windows $HOME is not defined
			$home = getenv( 'HOMEDRIVE' ) . getenv( 'HOMEPATH' );
		}
		return preg_replace( "#/$#", "", $home );
	}

	/**
	 * Remove a directory recursively.
	 *
	 * @param  string $dir Path to the directory you want to remove.
	 * @return void
	 */
	public static function rrmdir( $dir )
	{
		self::rempty( $dir );

		rmdir( $dir );
	}

	/**
	 * Empty a directory recursively.
	 *
	 * @param  string $dir     Path to the directory you want to remove.
	 * @param  array  $exclude An array of the files to exclude.
	 * @return void
	 */
	public static function rempty( $dir, $excludes = array() )
	{
		$dir = untrailingslashit( $dir );

		$files = self::get_files( $dir, RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $files as $fileinfo ) {
			if ( $fileinfo->isDir() ) {
				$skip = false;
				foreach ( $excludes as $exclude ) {
					if ( 0 === strpos( $exclude, $files->getSubPathName() ) ) {
						$skip = true;
					}
				}
				if ( ! $skip ) {
					rmdir( $fileinfo->getRealPath() );
				}
			} else {
				if ( ! in_array( $files->getSubPathName(), $excludes ) ) {
					unlink( $fileinfo->getRealPath() );
				}
			}
		}
	}

	/**
	 * Create a temporary working directory
	 *
	 * @param  string $prefix Prefix for the temporary directory you want to create.
	 * @return string         Path to the temporary directory.
	 */
	public static function tempdir( $prefix = '' )
	{
		$tempfile = tempnam( sys_get_temp_dir(), $prefix );
		if ( file_exists( $tempfile ) ) {
			unlink( $tempfile );
		}
		mkdir( $tempfile );
		if ( is_dir( $tempfile ) ) {
			return $tempfile;
		}
	}

	/**
	 * Copy directory recursively.
	 *
	 * @param  string $source  Path to the source directory.
	 * @param  string $dest    Path to the destination.
	 * @param  array  $exclude An array of the files to exclude.
	 * @return void
	 */
	public static function rcopy( $src, $dest, $exclude = array() )
	{
		$src = untrailingslashit( $src );
		$dest = untrailingslashit( $dest );

		if ( ! is_dir( $dest ) ) {
			mkdir( $dest, 0755 );
		}

		$iterator = self::get_files( $src );
		foreach ( $iterator as $item ) {
			if ( $item->isDir() ) {
				if ( ! is_dir( $dest . '/' . $iterator->getSubPathName() ) ) {
					mkdir( $dest . '/' . $iterator->getSubPathName() );
				}
			} else {
				if ( ! in_array( $iterator->getSubPathName(), $exclude ) ) {
					copy( $item, $dest . '/' . $iterator->getSubPathName() );
				}
			}
		}
	}

	/**
	 * Create a zip archive from $source to $destination.
	 *
	 * @param string $source Path to the source directory.
	 * @param string $dest   Path to the .zip file.
	 * @return string        Path to the .zip file or WP_Error object.
	 */
	public static function zip( $src, $destination )
	{
		$src = untrailingslashit( $src );

		if ( ! is_dir( $src ) ) {
			return new \WP_Error( "error", "No such file or directory." );
		}

		if ( ! is_dir( dirname( $destination ) ) ) {
			return new \WP_Error( "error", "No such file or directory." );
		}

		if ( ! extension_loaded( 'zip' ) || ! file_exists( $src ) ) {
			return new \WP_Error( "error", "PHP Zip extension is not installed. Please install it." );
		}

		$destination = realpath( dirname( $destination ) ) . "/" . basename( $destination );

		$zip = new ZipArchive();
		if ( ! $zip->open( $destination, ZIPARCHIVE::CREATE ) ) {
			return new \WP_Error( "error", "No such file or directory." );
		}

		$iterator = self::get_files( $src );

		foreach ( $iterator as $item ) {
			if ( $item->isDir() ) {
				$zip->addEmptyDir( str_replace( $src . '/', '', $item . '/' ) );
			} else {
				$zip->addFromString( str_replace( $src . '/', '', $item ), file_get_contents( $item ) );
			}
		}

		$zip->close();

		if ( ! is_file( $destination ) ) {
			return new \WP_Error( "error", "No such file or directory." );
		}

		return $destination;
	}

	/**
	 * Unzip
	 *
	 * @param string $src  Path to the .zip archive.
	 * @param string $dest Path to extract .zip.
	 * @return string      `true` or WP_Error object.
	 */
	public static function unzip( $src, $dest )
	{
		if ( ! is_file( $src ) ) {
			return new \WP_Error( "No such file or directory." );
		}

		$zip = new ZipArchive;
		$res = $zip->open( $src );
		if ( true === $res ) {
			// extract it to the path we determined above
			$zip->extractTo( $dest );
			$zip->close();
			return true;
		}

		return new \WP_Error( "Can not open {$src}." );
	}

	/**
	 * Get file's iterator object from the directory.
	 *
	 * @param string $dir   Path to the directory.
	 * @param string $flags Flags for the `RecursiveIteratorIterator()`.
	 * @return string       Literator object of the `RecursiveIteratorIterator()`.
	 */
	public static function get_files( $dir, $flags = RecursiveIteratorIterator::SELF_FIRST )
	{
		$dir = untrailingslashit( $dir );

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
			$flags
		);

		return $iterator;
	}

	/**
	 * Get an array from `$assoc_args`.
	 *
	 * @param array  $assoc_args   `$assoc_args` of the WP-CLI.
	 * @param string $field        Field name.
	 * @return string              An array of args.
	 */
	public static function assoc_args_to_array( $assoc_args, $field )
	{
		if ( ! empty( $assoc_args[$field] ) ) {
			$args = preg_split( "/,/", $assoc_args[$field] );
			return array_map( 'trim', $args );
		} else {
			return array();
		}
	}

	/**
	 * Post $post to $url.
	 *
	 * @param stirng $url The URL.
	 * @param mixed $post An array or string of the post data.
	 * @return array The HTTP response and body.
	 */
	public static function post( $url, $post, $token = null )
	{
		return self::http( 'POST', $url, $post, $token );
	}

	/**
	 * Send http get to $url.
	 *
	 * @param stirng $url The URL.
	 * @return array The HTTP response and body.
	 */
	public static function get( $url, $token = null )
	{
		return self::http( 'GET', $url, null, $token );
	}

	/**
	 * Send http get to $url.
	 *
	 * @param string $method The HTTP method.
	 * @param stirng $url The URL.
	 * @param array | string $params The parameters for POST.
	 * @param string $token The token for the API.
	 * @return array The HTTP response and body.
	 */
	public static function http( $method, $url, $params = null, $token = null )
	{
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
		if ( $token ) {
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
				"Authorization: " . $token,
			) );
		}
		if ( is_array( $params ) ) {
			curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
		} elseif ( $params ) {
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		}
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$result = json_decode( curl_exec( $ch ), true );
		$info = curl_getinfo($ch);

		if ( ! empty( $result['errorMessage'] ) ) {
			return new Error( $result['errorMessage'] );
		} elseif ( 200 === $info['http_code'] ) {
			return array( 'info' => $info, 'body' => $result );
		} else {
			if ( ! empty( $result['message'] ) ) {
				return new Error( $result['message'] );
			} elseif ( ! empty( $result['errorMessage'] ) ) {
				return new Error( $result['errorMessage'] );
			} else {
				return new Error( "Sorry, something went wrong. We're working on getting this fixed as soon as we can." );
			}
		}
	}
}
