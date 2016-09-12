<?php
/**
 * Remove a directory recursively.
 *
 * @param string $dir Path to the directory you want to remove.
 */
function rrmdir( $dir ) {
	$files = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $files as $fileinfo ) {
		$todo = ( $fileinfo->isDir() ? 'rmdir' : 'unlink' );
		$todo( $fileinfo->getRealPath() );
	}

	rmdir($dir);
}

/**
 * Create a temporary working directory
 *
 * @param string $prefix Prefix for the temporary directory you want to create.
 */
function tempdir( $prefix = '' ) {
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
 * @param string $source Path to the source directory.
 * @param string $dest   Path to the destination.
 */
function rcopy( $source, $dest ) {
	mkdir( $dest, 0755 );
	foreach (
	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator( $source, \RecursiveDirectoryIterator::SKIP_DOTS ),
		\RecursiveIteratorIterator::SELF_FIRST ) as $item
	) {
		if ( $item->isDir() ) {
			mkdir( $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName() );
		} else {
			copy( $item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName() );
		}
	}
}

/**
 * Create a zip archive from $source to $destination.
 *
 * @param string $source Path to the source directory.
 * @param string $dest   Path to the .zip file.
 */
function zip( $source, $destination ) {
	if ( ! extension_loaded( 'zip' ) || ! file_exists( $source ) ) {
		return false;
	}

	$zip = new ZipArchive();
	if ( ! $zip->open( $destination, ZIPARCHIVE::CREATE ) ) {
		return false;
	}

	$source = str_replace( '\\', '/', realpath( $source ) );

	if ( is_dir( $source ) === true ) {
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($source),
			RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ( $files as $file )
		{
			$file = str_replace( '\\', '/', $file );

			// Ignore "." and ".." folders
			if( in_array( substr( $file, strrpos( $file, '/' )+1 ), array( '.', '..' ) ) )
				continue;

			$file = realpath( $file );

			if ( is_dir( $file ) === true )
			{
				$zip->addEmptyDir( str_replace( $source . '/', '', $file . '/' ) );
			}
			else if ( is_file( $file ) === true )
			{
				$zip->addFromString( str_replace( $source . '/', '', $file ), file_get_contents( $file ) );
			}
		}
	} elseif ( is_file( $source ) === true ) {
		$zip->addFromString( basename( $source ), file_get_contents( $source ) );
	}

	return $zip->close();
}
