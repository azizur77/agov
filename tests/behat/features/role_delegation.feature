Feature: Role delegation

  So user management can be delegated to site editors
  As a site editor I can alter user roles
  except for users that have the administrator role

  @api
  Scenario: Meta-tags are auto set
    Given an "administrator" user named "joe"
    Given an "Content editor" user named "bob"
    And I am logged in as a user with the "Site editor" role that does not force password change
    And I visit the user edit page for "bob"
    Then I "should" be able to change the "Site editor" role
    And I visit the user edit page for "joe"
    Then I "should not" be able to change the "administrator" role
