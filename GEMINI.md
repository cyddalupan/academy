# Gemini TDD Environment

This document outlines the process for setting up a Test-Driven Development (TDD) environment for this project. The project consists of two main parts: a CodeIgniter application and a custom API in the `cyd` directory. Each part has its own testing setup.

## Branching Strategy

**All new development and testing should be done on the `beta` branch.**

---

## 1. Testing the CodeIgniter Application

The main application is built with CodeIgniter. We will use `codeigniter3-phpunit` to enable unit testing.

### 1.1. Setup Instructions

#### Step 1.1.1: Install `codeigniter3-phpunit`

We will add the testing library as a development dependency using Composer.

```bash
composer require --dev kenjis/codeigniter3-phpunit
```

#### Step 1.1.2: Run the Installation Script

The library provides an installation script that creates the necessary directories and files for testing.

```bash
php vendor/kenjis/codeigniter3-phpunit/install.php
```

This will create a `tests` directory inside the `application` directory, along with some example tests.

#### Step 1.1.3: Configure `phpunit.xml`

The installation script will also create a `phpunit.xml.dist` file in the root of the project. You may need to edit this file to configure the test database connection.

### 1.2. Running Tests

To run the CodeIgniter tests, execute the PHPUnit binary from the root of the project:

```bash
./vendor/bin/phpunit
```

### 1.3. Writing Tests

You can create new test files in the `application/tests` directory. Tests for controllers should go in `application/tests/controllers`, models in `application/tests/models`, and so on.

**Example: `application/tests/controllers/Welcome_test.php`**
```php
<?php
class Welcome_test extends TestCase
{
    public function test_index()
    {
        $output = $this->request('GET', 'welcome/index');
        $this->assertStringContainsString('<title>Welcome to CodeIgniter</title>', $output);
    }
}
```

---

## 2. Testing the `cyd` Directory

The `cyd` directory contains custom PHP scripts that operate independently of the main CodeIgniter application. We use a separate PHPUnit setup to test this code.

### 2.1. Setup Instructions

All commands should be run from within the `cyd` directory.

#### Step 2.1.1: Install PHPUnit

We will add PHPUnit as a development dependency using Composer.

```bash
cd cyd
composer require --dev phpunit/phpunit
```

#### Step 2.1.2: Create PHPUnit Configuration File

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

#### Step 2.1.3: Create a `tests` Directory

Create a directory to hold your test files.

```bash
mkdir tests
```

### 2.2. Running Tests

To run all tests in the `cyd` directory, execute the PHPUnit binary from within the `cyd` directory:

```bash
cd cyd
./vendor/bin/phpunit
```

To run a specific test file, provide the path to the file:

```bash
cd cyd
./vendor/bin/phpunit tests/ErrorViewerTest.php
```

### 2.3. Writing Tests

You can create new test files in the `cyd/tests` directory. When testing scripts that have dependencies or security checks, you may need to take extra steps.

**Example: `cyd/tests/ErrorViewerTest.php`**

This test file demonstrates how to test the `error_viewer.php` script.

```php
<?php
use PHPUnit\Framework\TestCase;

// Set the security token to bypass the access control check
$_GET['token'] = 'a3k9d2p5j8f1g7h4';

// Include the file to be tested
require_once __DIR__ . '/../error_viewer.php';

class ErrorViewerTest extends TestCase
{
    private $test_log_file;

    protected function setUp(): void
    {
        $this->test_log_file = __DIR__ . '/test_log.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->test_log_file)) {
            unlink($this->test_log_file);
        }
    }

    public function testCleanLogFile()
    {
        $today = date('d-M-Y');
        $yesterday = date('d-M-Y', strtotime('-1 day'));

        $log_content = "[$today 10:00:00] Today\'s log entry\n";
        $log_content .= "[$yesterday 12:00:00] Yesterday\'s log entry\n";

        file_put_contents($this->test_log_file, $log_content);

        clean_log_file($this->test_log_file);

        $cleaned_content = file_get_contents($this->test_log_file);

        $this->assertStringContainsString("[$today 10:00:00] Today\'s log entry", $cleaned_content);
        $this->assertStringNotContainsString("[$yesterday 12:00:00] Yesterday\'s log entry", $cleaned_content);
    }

    public function testDisplayLog()
    {
        $log_content = "Test log entry";
        file_put_contents($this->test_log_file, $log_content);

        ob_start();
        display_log($this->test_log_file);
        $output = ob_get_clean();

        $this->assertStringContainsString("Test log entry", $output);
    }
}
```

---

## 3. Known Issues

### OpenSSL Version Mismatch

When running PHPUnit tests, you may encounter the following error:

```
php: /lib/x86_64-linux-gnu/libcrypto.so.1.1: version `OPENSSL_1_1_1' not found (required by php)
```

This is due to a system-level issue with the PHP installation and a mismatch in the OpenSSL library version. This is an environment issue and cannot be fixed by modifying the code.

---

## 4. Application Notes: `/cyd/api/lawgpt_premium.php`

This section documents key operational details and refactoring work performed on the `lawgpt_premium.php` script.

### Overview

The `/cyd/api/lawgpt_premium.php` script serves as the backend for the "lawGPT" AI chat service. It receives conversation history from a client, orchestrates calls to external APIs for web search (Tavily) and AI chat completion (Grok-4), and streams the response back. It also logs the conversation to a database.

### Key Challenges & Solutions Implemented

The script was refactored to address several stability and performance issues:

1.  **Payload Size Crashes (HTTP 500):**
    *   **Problem:** Large conversation histories caused the script to crash when sending excessive data to the Grok-4 API.
    *   **Solution:** A multi-step truncation strategy was implemented before the API call.

2.  **Script Timeouts (HTTP 503):**
    *   **Problem:** Slow responses from the Grok-4 API caused the PHP script to hit its maximum execution time.
    *   **Solution:** The script's `max_execution_time` was proactively increased to **300 seconds** (5 minutes).

3.  **Lack of Debugging:**
    *   **Problem:** No straightforward way to view PHP error logs.
    *   **Solution:** A new debugging utility was created at `/cyd/error_viewer.php`.

4.  **Unconditional Web Search:**
    *   **Problem:** The script performed a web search on every request, regardless of need.
    *   **Solution:** The web search call was made conditional.