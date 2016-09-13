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
      Success: Backup to
      """
    And STDOUT should contain:
      """
      archive.zip
      """
    And the archive.zip file should exist

    When I run `wp shifter backup hello.zip`
    Then STDOUT should contain:
      """
      Success: Backup to
      """
    And STDOUT should contain:
      """
      hello.zip
      """
    And the hello.zip file should exist

    When I run `wp shifter backup /tmp/backup.zip`
    Then STDOUT should contain:
      """
      Success: Backup to
      """
    And STDOUT should contain:
      """
      /tmp/backup.zip
      """
    And the /tmp/backup.zip file should exist

    When I try `wp shifter backup foo/bar/hello.zip`
    Then the return code should be 1
    Then STDERR should contain:
      """
      Error: No such file or directory.
      """
