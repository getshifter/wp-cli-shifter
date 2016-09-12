Feature: Test that `wp shifter` commands loads.

  Scenario: `wp shifter` commands should be available.
    Given a WP install

    When I run `wp help shifter`
    Then the return code should be 0

    When I run `wp help shifter backup`
    Then the return code should be 0

    When I run `wp shifter backup`
    Then STDOUT should contain:
      """
      Success: Backup to 'archive.zip'
      """
    And the archive.zip file should exist

    When I run `wp shifter backup hello.zip`
    Then STDOUT should contain:
      """
      Success: Backup to 'hello.zip'
      """
    And the hello.zip file should exist
