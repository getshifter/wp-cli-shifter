Feature: Test that `wp shifter archive` commands loads.

  Scenario: Upload an archive
    Given a WP install

    When I run `wp shifter archive create`
    Then STDOUT should contain:
      """
      Success: Archived to
      """
    And STDOUT should contain:
      """
      archive.zip
      """
    And the archive.zip file should exist

    When I run `wp shifter archive upload archive.zip --shifter-user=$SHIFTER_USER --shifter-password=$SHIFTER_PASS`
    Then STDOUT should contain:
      """
      Success: 🍺 Archive uploaded successfully.
      """

  Scenario: Create and delete project
    Given an empty directory

    When I run `wp shifter project create --archive-id=$(wp shifter archive list --shifter-user=$SHIFTER_USER --shifter-password=$SHIFTER_PASS --format=json | jq -r .[0].archive_id) --project-name="Behat Test" --php-version=7.0 --shifter-user=$SHIFTER_USER --shifter-password=$SHIFTER_PASS`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp shifter project delete $(wp shifter project list --shifter-user=$SHIFTER_USER --shifter-password=$SHIFTER_PASS --format=json | jq -r .[0].site_id) --shifter-user=$SHIFTER_USER --shifter-password=$SHIFTER_PASS`
    Then STDOUT should contain:
      """
      Success: 🍺 Project deleted successfully.
      """
