Feature: Test that `wp shifter` commands loads.

  Scenario: `wp shifter` commands should be available.
    Given a WP install

    When I run `wp help shifter`
    Then the return code should be 0

    When I run `wp help shifter archive`
    Then the return code should be 0

    When I run `wp shifter archive`
    Then STDOUT should contain:
      """
      Success: Archived to 'archive.zip'
      """

    When I run `wp shifter archive hello.zip`
    Then STDOUT should contain:
      """
      Success: Archived to 'hello.zip'
      """
