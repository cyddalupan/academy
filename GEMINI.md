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

## Application Notes: `/cyd/api/lawgpt_premium.php`

This section documents key operational details and refactoring work performed on the `lawgpt_premium.php` script.

### Overview

The `/cyd/api/lawgpt_premium.php` script serves as the backend for the "lawGPT" AI chat service. It receives conversation history from a client, orchestrates calls to external APIs for web search (Tavily) and AI chat completion (Grok-4), and streams the response back. It also logs the conversation to a database.

### Key Challenges & Solutions Implemented

The script was refactored to address several stability and performance issues:

1.  **Payload Size Crashes (HTTP 500):**
    *   **Problem:** Large conversation histories caused the script to crash when sending excessive data to the Grok-4 API.
    *   **Solution:** A multi-step truncation strategy was implemented before the API call:
        *   Web search results are capped at **8,000 characters**.
        *   The total payload to the AI is capped at **32,000 characters**.
        *   If the limit is exceeded, the script first shortens the web search results. If still over the limit, it removes the oldest messages from the conversation history.

2.  **Script Timeouts (HTTP 503):**
    *   **Problem:** Slow responses from the Grok-4 API caused the PHP script to hit its maximum execution time, resulting in a 503 error.
    *   **Solution:** The script's `max_execution_time` was proactively increased to **300 seconds** (5 minutes) using `ini_set()`.

3.  **Lack of Debugging:**
    *   **Problem:** No straightforward way to view PHP error logs.
    *   **Solution:** A new debugging utility was created at `/cyd/error_viewer.php`. It is secured with a token and can be accessed via `.../cyd/error_viewer.php?token=a3k9d2p5j8f1g7h4`.

4.  **Unconditional Web Search:**
    *   **Problem:** The script performed a web search on every request, regardless of need.
    *   **Solution:** The web search call was made conditional, controlled by a `$web_search` boolean flag sent from the client.
