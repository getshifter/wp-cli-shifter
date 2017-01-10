Feature: Test that `wp shifter archive` commands loads.

  Scenario: Create and delete project
    Given an empty directory

    When I run `wp shifter project create --archive-id=$(wp shifter archive list --shifter-user=$SHIFTER_USER --shifter-password=$SHIFTER_PASS --format=json | jq -r .[0].archive_id) --project-name="Behat Test" --php-version=7.0 --shifter-user=$SHIFTER_USER --shifter-password=$SHIFTER_PASS`
    Then STDOUT should contain:
      """
      Success:
      """

