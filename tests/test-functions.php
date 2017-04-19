<?php

use Shifter_CLI\Functions;
use Shifter_CLI\Error;

class SimpleMapTest extends WP_UnitTestCase
{
	/**
	 * @test
	 */
	public function error_test()
	{
		$e = new Error( 'error!' );
		$this->assertTrue( Error::is_error( $e ) );
		$this->assertSame( 'error!', $e->get_message() );
	}

	/**
	 * @test
	 */
	public function auth()
	{
		$res = Functions::auth( "foo", "bar" );
		$this->assertTrue( Error::is_error( $res ) );
		$this->assertSame( 'Invalid Username or Password', $res->get_message() );

		$res = Functions::auth( getenv( "SHIFTER_USER" ), "bar" );
		$this->assertTrue( Error::is_error( $res ) );
		$this->assertSame( 'Invalid Username or Password', $res->get_message() );

		$res = Functions::auth( getenv( "SHIFTER_USER" ), getenv( "SHIFTER_PASS" ) );
		$this->assertTrue( !! $res );
	}

	/**
	 * Tests for the `Functions::rempty()`.
	 *
	 * @test
	 * @since 0.1.0
	 */
	public function rempty()
	{
		$dir = self::mockdir();
		$files = Functions::get_files( $dir );
		$this->assertSame( 7, iterator_count($files) );

		$dir = self::mockdir();
		Functions::rempty( $dir );
		$files = Functions::get_files( $dir );
		$this->assertSame( 0, iterator_count($files) );

		$dir = self::mockdir();

		Functions::rempty( $dir, array(
			"dir02/dir02-01.txt",
			"dir01/dir01-01/dir01-01-01.txt"
		) );
		$files = Functions::get_files( $dir );
		$this->assertSame( 5, iterator_count($files) );
	}

	/**
	 * Tests for the `Functions::tempdir()`.
	 *
	 * @test
	 * @since 0.1.0
	 */
	public function tempdir()
	{
		$dir = Functions::tempdir();
		$this->assertTrue( is_dir( $dir ) ); // $dir should exists.
	}

	/**
	 * Tests for the `Functions::rrmdir()`.
	 *
	 * @test
	 * @since 0.1.0
	 */
	public function rrmdir()
	{
		$dir = self::mockdir();
		$this->assertTrue( is_dir( $dir ) ); // $dir should exists.

		Functions::rrmdir( $dir );
		$this->assertFalse( is_dir( $dir ) ); // $dir should not exists.
	}

	/**
	 * Tests for the `Functions::rcopy()`.
	 *
	 * @test
	 * @since 0.1.0
	 */
	public function rcopy()
	{
		$src = self::mockdir();
		$this->assertTrue( is_dir( $src ) ); // $dir should exists.

		$dest = Functions::tempdir();
		$this->assertTrue( is_dir( $dest ) ); // $dir should exists.
		$this->assertTrue( self::md5sum( $src ) !== self::md5sum( $dest ) );

		// Copy directory recursively then check md5.
		Functions::rcopy( $src, $dest );
		$this->assertTrue( self::md5sum( $src ) === self::md5sum( $dest ) );

		$dest = Functions::tempdir();
		Functions::rcopy( $src, $dest, array( "dir01/dir01-01.txt" ) );
		$this->assertFalse( is_file( $dest . '/dir01/dir01-01.txt' ) );
		$this->assertTrue( is_file( $dest . '/dir01/dir01-02.txt' ) );
		$this->assertTrue( is_file( $dest . '/dir02/dir02-01.txt' ) );
	}

	/**
	 * Tests for the `Functions::zip()`.
	 *
	 * @test
	 * @since 0.1.0
	 */
	public function zip()
	{
		$src = self::mockdir();
		$this->assertTrue( is_dir( $src ) ); // $dir should exists.

		$dir = Functions::tempdir();

		// zip $src
		Functions::zip( $src, $dir . '/archive.zip' );
		$this->assertTrue( is_file( $dir . '/archive.zip' ) );

		// unzip to $dir . "/tmp"
		mkdir( $dir . "/tmp" );
		Functions::unzip( $dir . '/archive.zip', $dir . "/tmp" );
		$this->assertTrue( self::md5sum( $src ) === self::md5sum( $dir . "/tmp" ) );
	}

	/**
	 * Create files and directories as mock for the test.
	 *
	 * @since  0.1.0
	 * @return string $dir Path to the temporary directory.
	 */
	public static function mockdir()
	{
		$dir = Functions::tempdir();
		mkdir( $dir . "/dir01" );
		file_put_contents( $dir . "/dir01/dir01-01.txt", time() );
		file_put_contents( $dir . "/dir01/dir01-02.txt", time() );
		mkdir( $dir . "/dir01/dir01-01" );
		file_put_contents( $dir . "/dir01/dir01-01/dir01-01-01.txt", time() );
		mkdir( $dir . "/dir02" );
		file_put_contents( $dir . "/dir02/dir02-01.txt", time() );

		return $dir;
	}

	/**
	 * Create a md5 hash from directory.
	 *
	 * @since  0.1.0
	 * @param  strint $dir Path to the directory.
	 * @return string      Hash of the files in the derectory.
	 */
	public static function md5sum( $dir )
	{
		if ( ! is_dir( $dir ) ) {
			return false;
		}

		$iterator = Functions::get_files( $dir );

		$md5 = array();
		foreach ( $iterator as $item ) {
			if ( ! $item->isDir() ) {
				$md5[] = md5_file( $item );
			}
		}

		return md5( implode( '', $md5 ) );
	}
}
