<?php

class Shifter_CLI
{
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
	 * @param  string $dir Path to the directory you want to remove.
	 * @return void
	 */
	public static function rempty( $dir )
	{
		$dir = untrailingslashit( $dir );

		$files = self::get_files( $dir, RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $files as $fileinfo ) {
			$todo = ( $fileinfo->isDir() ? 'rmdir' : 'unlink' );
			$todo( $fileinfo->getRealPath() );
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
	 * @param  string $source Path to the source directory.
	 * @param  string $dest   Path to the destination.
	 * @return void
	 */
	public static function rcopy( $src, $dest )
	{
		$src = untrailingslashit( $src );
		$dest = untrailingslashit( $dest );

		if ( ! is_dir( $dest ) ) {
			mkdir( $dest, 0755 );
		}

		$iterator = self::get_files( $src );
		foreach ( $iterator as $item ) {
			if ( $item->isDir() ) {
				mkdir( $dest . '/' . $iterator->getSubPathName() );
			} else {
				copy( $item, $dest . '/' . $iterator->getSubPathName() );
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
}
