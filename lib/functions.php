<?php

class Shifter_CLI
{
	/**
	 * Authentication at shifter
	 */
	public static function auth( $username, $password )
	{
		$res = wp_remote_post(
			"https://hz0wknz3a2.execute-api.us-east-1.amazonaws.com/production/login",
			array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.1',
				'blocking' => true,
				'headers' => array(),
				'body' => json_encode( array(
					"username" => $username,
					"password" => $password
				) ),
				'cookies' => array(),
			)
		);

		if ( 200 === $res['response']['code'] ) {
		} else {
			$message = json_decode( $res['body'] )->message;
			return new WP_Error( $res['response']['code'], $message );
		}
	}

	/**
	 * Plompt login with user and password with prompt.
	 *
	 * @return bool or WP_CLI::Error()
	 */
	public static function prompt_user_and_pass()
	{
		$region = 'us-east-1';

		$user = trim( cli\prompt(
			'Shifter Username',
			$default = false,
			$marker = ': ',
			$hide = false
		) );

		$pass = trim( cli\prompt(
			'Password (will be hidden)',
			$default = false,
			$marker = ': ',
			$hide = true
		) );

		return array( 'user' => $user, 'pass' => $pass );
	}

	/**
	 * Create an archive.
	 *
	 * @param  array $args The `$args` for the WP-CLI.
	 * @param  array $assoc_args The `$assoc_args` for the WP-CLI.
	 * @return string The path to archive.
	 */
	public static function create_archive( $args, $assoc_args )
	{
		$progress = new \cli\progress\Bar( 'Creating an archive: ', 5 );

		$tmp_dir = self::tempdir( 'SFT' );
		$progress->tick();

		$excludes = self::assoc_args_to_array( $assoc_args, "exclude" );

		self::rcopy( ABSPATH, $tmp_dir . '/webroot', $excludes );
		$progress->tick();

		WP_CLI::launch_self(
			"db export",
			array( $tmp_dir . "/wp.sql" ),
			array(),
			true,
			true,
			array( 'path' => WP_CLI::get_runner()->config['path'] )
		);
		$progress->tick();

		if ( empty( $args[0] ) ) {
			$archive = getcwd() . "/archive.zip";
		} else {
			$archive = $args[0];
		}

		$file = self::zip( $tmp_dir, $archive );
		$progress->tick();

		self::rrmdir( $tmp_dir );
		if ( is_wp_error( $file ) ) {
			WP_CLI::error( $file->get_error_message() );
		}
		$progress->tick();

		return $file;
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
			return new WP_Error( "error", "No such file or directory." );
		}

		if ( ! is_dir( dirname( $destination ) ) ) {
			return new WP_Error( "error", "No such file or directory." );
		}

		if ( ! extension_loaded( 'zip' ) || ! file_exists( $src ) ) {
			return false;
		}

		$destination = realpath( dirname( $destination ) ) . "/" . basename( $destination );

		$zip = new ZipArchive();
		if ( ! $zip->open( $destination, ZIPARCHIVE::CREATE ) ) {
			return new WP_Error( "", "No such file or directory." );
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
			return new WP_Error( "error", "No such file or directory." );
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
			return new WP_Error( "No such file or directory." );
		}

		$zip = new ZipArchive;
		$res = $zip->open( $src );
		if ( true === $res ) {
			// extract it to the path we determined above
			$zip->extractTo( $dest );
			$zip->close();
			return true;
		}

		return new WP_Error( "Can not open {$src}." );
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
}
