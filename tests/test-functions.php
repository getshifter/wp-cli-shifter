<?php

class SimpleMapTest extends WP_UnitTestCase
{
	/**
	 * @test
	 */
	public function auth()
	{
		$res = Shifter_CLI::auth( "foo", "bar" );
		$this->assertTrue( is_wp_error( $res ) );
		$this->assertSame( "User does not exist.", $res->get_error_message() );

		$res = Shifter_CLI::auth( getenv( "SHIFTER_USER" ), getenv( "SHIFTER_PASS" ) );
		$this->assertTrue( ! is_wp_error( $res ) );
		$this->assertTrue( !! $res->AccessToken );
	}

	/**
	 * Tests for the `Shifter_CLI::rempty()`.
	 *
	 * @test
	 * @since 0.1.0
	 */
	public function rempty()
	{
		$dir = self::mockdir();
		$files = Shifter_CLI::get_files( $dir );
		$this->assertSame( 7, iterator_count($files) );

		$dir = self::mockdir();
		Shifter_CLI::rempty( $dir );
		$files = Shifter_CLI::get_files( $dir );
		$this->assertSame( 0, iterator_count($files) );

		$dir = self::mockdir();

		Shifter_CLI::rempty( $dir, array(
			"dir02/dir02-01.txt",
			"dir01/dir01-01/dir01-01-01.txt"
		) );
		$files = Shifter_CLI::get_files( $dir );
		$this->assertSame( 5, iterator_count($files) );
	}

	/**
	 * Tests for the `Shifter_CLI::tempdir()`.
	 *
	 * @test
	 * @since 0.1.0
	 */
	public function tempdir()
	{
		$dir = Shifter_CLI::tempdir();
		$this->assertTrue( is_dir( $dir ) ); // $dir should exists.
	}

	/**
	 * Tests for the `Shifter_CLI::rrmdir()`.
	 *
	 * @test
	 * @since 0.1.0
	 */
	public function rrmdir()
	{
		$dir = self::mockdir();
		$this->assertTrue( is_dir( $dir ) ); // $dir should exists.

		Shifter_CLI::rrmdir( $dir );
		$this->assertFalse( is_dir( $dir ) ); // $dir should not exists.
	}

	/**
	 * Tests for the `Shifter_CLI::rcopy()`.
	 *
	 * @test
	 * @since 0.1.0
	 */
	public function rcopy()
	{
		$src = self::mockdir();
		$this->assertTrue( is_dir( $src ) ); // $dir should exists.

		$dest = Shifter_CLI::tempdir();
		$this->assertTrue( is_dir( $dest ) ); // $dir should exists.
		$this->assertTrue( self::md5sum( $src ) !== self::md5sum( $dest ) );

		// Copy directory recursively then check md5.
		Shifter_CLI::rcopy( $src, $dest );
		$this->assertTrue( self::md5sum( $src ) === self::md5sum( $dest ) );

		$dest = Shifter_CLI::tempdir();
		Shifter_CLI::rcopy( $src, $dest, array( "dir01/dir01-01.txt" ) );
		$this->assertFalse( is_file( $dest . '/dir01/dir01-01.txt' ) );
		$this->assertTrue( is_file( $dest . '/dir01/dir01-02.txt' ) );
		$this->assertTrue( is_file( $dest . '/dir02/dir02-01.txt' ) );
	}

	/**
	 * Tests for the `Shifter_CLI::zip()`.
	 *
	 * @test
	 * @since 0.1.0
	 */
	public function zip()
	{
		$src = self::mockdir();
		$this->assertTrue( is_dir( $src ) ); // $dir should exists.

		$dir = Shifter_CLI::tempdir();

		// zip $src
		Shifter_CLI::zip( $src, $dir . '/archive.zip' );
		$this->assertTrue( is_file( $dir . '/archive.zip' ) );

		// unzip to $dir . "/tmp"
		mkdir( $dir . "/tmp" );
		Shifter_CLI::unzip( $dir . '/archive.zip', $dir . "/tmp" );
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
		$dir = Shifter_CLI::tempdir();
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

		$iterator = Shifter_CLI::get_files( $dir );

		$md5 = array();
		foreach ( $iterator as $item ) {
			if ( ! $item->isDir() ) {
				$md5[] = md5_file( $item );
			}
		}

		return md5( implode( '', $md5 ) );
	}
}
