<?php

class SimpleMapTest extends WP_UnitTestCase
{
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
		file_put_contents( $dir . "/dir01/dir01.txt", "" );
		mkdir( $dir . "/dir02" );
		file_put_contents( $dir . "/dir02/dir02.txt", "" );

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
