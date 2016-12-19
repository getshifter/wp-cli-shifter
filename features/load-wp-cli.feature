Feature: Test that `wp shifter` commands loads.

  Scenario: `wp shifter` commands should be available.
    Given a WP install

    When I run `wp help shifter`
    Then the return code should be 0

    When I run `wp help shifter archive`
    Then the return code should be 0

    When I run `wp help shifter extract`
    Then the return code should be 0

  Scenario: Tests for `wp shifter archive`.
    Given a WP install

    When I run `wp shifter archive`
    Then STDOUT should contain:
      """
      Success: Archived to
      """
    And STDOUT should contain:
      """
      archive.zip
      """
    And the archive.zip file should exist

    When I run `wp shifter archive ./hello.zip`
    Then STDOUT should contain:
      """
      Success: Archived to
      """
    And STDOUT should contain:
      """
      hello.zip
      """
    And the hello.zip file should exist

    When I run `wp shifter archive /tmp/archive.zip`
    Then STDOUT should contain:
      """
      Success: Archived to
      """
    And STDOUT should contain:
      """
      /tmp/archive.zip
      """
    And the /tmp/archive.zip file should exist

    When I try `wp shifter archive foo/bar/hello.zip`
    Then the return code should be 1
    Then STDERR should contain:
      """
      Error: No such file or directory.
      """

  Scenario: Tests for the `wp shifter extract`
    Given a WP install
    And a wp-content/plugins/example.php file:
      """
      // Plugin Name: Example Plugin
      // Network: true
      """
    And I run `wp shifter archive /tmp/archive.zip`
    And I run `wp plugin uninstall example`

    When I try `wp shifter extract foo/bar/hello.zip`
    Then the return code should be 1
    Then STDERR should contain:
      """
      Error: No such file or directory.
      """

    When I run `wp shifter extract /tmp/archive.zip --exclude=wp-content/plugins/example.php --delete`
    Then STDOUT should contain:
      """
      Success: Extracted from '/tmp/archive.zip'.
      """
    And the wp-content/plugins/example.php file should not exist

    When I run `wp shifter extract /tmp/archive.zip`
    Then STDOUT should contain:
      """
      Success: Extracted from '/tmp/archive.zip'.
      """
    And the wp-content/plugins/example.php file should exist

    When I run `wp core version`
    Then the return code should be 0

  Scenario: Upload an archive
    Given a WP install

    When I run `wp shifter archive`
    Then STDOUT should contain:
      """
      Success: Archived to
      """
    And STDOUT should contain:
      """
      archive.zip
      """
    And the archive.zip file should exist

    When I run `wp shifter upload archive.zip --shifter-user=$SHIFTER_USER --shifter-password=$SHIFTER_PASS`
    Then STDOUT should contain:
      """
      Success: 🍺 Archive uploaded successfully.
      """

    When I run `wp shifter list --shifter-user=$SHIFTER_USER --shifter-password=$SHIFTER_PASS`
    Then STDOUT should be a table containing rows:
      | archive_id | archive_owner | archive_create_date |

    When I run `wp shifter delete $(wp shifter list --shifter-user=$SHIFTER_USER --shifter-password=$SHIFTER_PASS --format=json | jq -r .[0].archive_id) --shifter-user=$SHIFTER_USER --shifter-password=$SHIFTER_PASS`
    Then STDOUT should contain:
      """
      Success: 🍺 Archive deleted successfully.
      """
