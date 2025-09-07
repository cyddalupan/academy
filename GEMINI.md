check error_log file when encountered a problem

# How to Run Tests

This document describes how to run the unit tests for the code in the `cyd/` directory.

## Prerequisites

The testing framework, PHPUnit, is installed as a development dependency via Composer.

## Running Tests

To execute the entire test suite, run the following command from the root of the project:

```bash
/root/academy/cyd/vendor/bin/phpunit /root/academy/cyd/tests
```

To run a single test file, provide the path to the test file:

```bash
/root/academy/cyd/vendor/bin/phpunit /root/academy/cyd/tests/MyTest.php
```

This command will automatically find and run all tests located in the `cyd/tests/` directory, based on the configuration in `cyd/phpunit.xml`.

## Adding New Tests

1.  Create a new test file in the `cyd/tests/` directory. It's recommended to follow the existing directory structure. For example, a test for `cyd/chat/chat.php` could be placed in `cyd/tests/chat/ChatTest.php`.
2.  Your test class should extend `PHPUnit\Framework\TestCase`.
3.  Add public methods prefixed with `test` to your class. These will be your test cases.

The test runner will automatically discover and run any new tests you add.
