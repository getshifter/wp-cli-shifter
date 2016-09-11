<?php

function tempdir( $dir = false, $prefix = '' ) {
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
