# Gemini TDD Environment for the `cyd` Directory

This document outlines the process for setting up a Test-Driven Development (TDD) environment using PHPUnit for the custom PHP code within the `/cyd` directory.

## Branching Strategy

**All new development and testing for the `cyd` directory should be done on the `beta` branch.**

## 1. Introduction

The `cyd` directory contains custom PHP scripts that operate independently of the main CodeIgniter application. To ensure code quality and facilitate TDD, we will use PHPUnit, the standard testing framework for PHP.

See `DATABASE.md` for the database format.

This setup will allow you to run tests from the terminal to verify the functionality of the scripts inside `cyd`.

## 2. Setup Instructions

All commands should be run from within the `cyd` directory.

### Step 2.1: Install PHPUnit

We will add PHPUnit as a development dependency using Composer.

```bash
cd cyd
composer require --dev phpunit/phpunit
```

### Step 2.2: Create PHPUnit Configuration File

Create a new file named `phpunit.xml.dist` in the `cyd` directory. This file tells PHPUnit where to find the tests.

**File: `/root/tdd/academy/cyd/phpunit.xml.dist`**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="CYD Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### Step 2.3: Create a `tests` Directory

Create a directory to hold your test files.

```bash
mkdir tests
```

### Step 2.4: Create an Example Test

To verify the setup, create a simple test file.

**File: `/root/tdd/academy/cyd/tests/ExampleTest.php`**
```php
<?php
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testThatTestsAreWorking()
    {
        $this->assertTrue(true);
    }
}
```

## 3. Running Tests

To run your tests, execute the PHPUnit binary from within the `cyd` directory:

```bash
./vendor/bin/phpunit
```

You should see output indicating that 1 test passed.

## 4. Next Steps

You can now create new test files in the `cyd/tests` directory. For example, to test the `utils.php` file, you could create a `UtilsTest.php` file. You would need to include the file you want to test at the top of your test file.

**Example: `/root/tdd/academy/cyd/tests/UtilsTest.php`**
```php
<?php
use PHPUnit\Framework\TestCase;

// Include the file to be tested
require_once __DIR__ . '/../utils.php';

class UtilsTest extends TestCase
{
    public function testSomethingInUtils()
    {
        // Assuming you have a function named 'my_function' in utils.php
        // $result = my_function();
        // $this->assertEquals('expected_value', $result);
        $this->assertTrue(true); // Placeholder assertion
    }
}

## Database Credentials

- **Username:** root
- **Password:** (empty)
```

## 5. Known Issues

### OpenSSL Version Mismatch

When running PHPUnit tests, you may encounter the following error:

```
php: /lib/x86_64-linux-gnu/libcrypto.so.1.1: version `OPENSSL_1_1_1' not found (required by php)
```

This is due to a system-level issue with the PHP installation and a mismatch in the OpenSSL library version. This is an environment issue and cannot be fixed by modifying the code.
