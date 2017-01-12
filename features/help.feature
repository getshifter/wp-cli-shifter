Feature: Test that `wp help shifter` commands loads.

  Scenario: `wp shifter archive` commands should be available.
    Given an empty directory

    When I run `wp help shifter`
    Then the return code should be 0

    When I run `wp help shifter archive`
    Then the return code should be 0

    When I run `wp help shifter archive extract`
    Then the return code should be 0
