Feature: compile to phar
    In order to use php-optimize without installing it as a dependency
    As a developer
    I need to be able to compile it to a phar


    Scenario: A clean run of installing a phar
        Given the "build" folder does not contain "php-optimize.phar"
        When i run the shell command "./bin/compile"
        Then i should see the file "php-optimize.phar" in the "build" folder
