# Gemini TDD Environment

This document outlines the process for setting up a Test-Driven Development (TDD) environment for this project.

## Composer

This project uses [Composer](https://getcomposer.org/) to manage PHP dependencies. A `composer.phar` executable is included in the root of this project. All `composer` commands should be run from the project root directory using `php composer.phar`.

## Project Structure Overview

This project contains two distinct PHP applications:

1.  **CodeIgniter Application (Main Application):** This is the primary application built on the CodeIgniter framework. Its codebase is located in the `application` and `system` directories. It has its own `composer.json` file in the project root.
2.  **`cyd` Application (Custom API):** This is a collection of custom PHP scripts and APIs located in the `cyd` directory. It operates independently of the CodeIgniter application and has its own `composer.json` file in the `cyd` directory.

Due to this separation, each application has its own dedicated testing environment and dependencies. The following sections detail the setup and execution procedures for each.

## Branching Strategy

**All new development and testing should be done on the `beta` branch.**

---

## 1. Testing the CodeIgniter Application

The main application is built with CodeIgniter. To enable a flexible and uniform testing strategy, we use a generic PHPUnit setup with a custom `TestCase` base class. This approach allows us to write tests that can easily interact with the CodeIgniter framework without being tightly coupled to a specific testing library.

### 1.1. Setup Instructions

#### Step 1.1.1: Install Dependencies

From the project root, run the following command to install the dependencies defined in the root `composer.json` file:

```bash
php composer.phar install
```

#### Step 1.1.2: PHPUnit Configuration

A `phpunit.xml.dist` file is provided in the root of the project. This file is configured to use a custom bootstrap file to load the CodeIgniter environment and our custom `TestCase`.

**File: `/root/tdd/academy/phpunit.xml.dist`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="application/tests/bootstrap.php"
         colors="true"
         verbose="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="CodeIgniter Application Test Suite">
            <directory>application/tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### 1.1.3: The Bootstrap File

The `application/tests/bootstrap.php` file is responsible for loading the CodeIgniter environment and the custom `TestCase` class.

**File: `/root/tdd/academy/application/tests/bootstrap.php`**

```php
<?php
// Define the path to the CodeIgniter index.php file
define('FCPATH', realpath(__DIR__ . '/../../') . '/');
define('APPPATH', FCPATH . 'application/');
define('BASEPATH', FCPATH . 'system/');

// Mock CodeIgniter core functions
function &get_instance() {
    $ci = new stdClass();
    $ci->load = new stdClass();
    $ci->load->view = function($view, $data = []) {
        extract($data);
        ob_start();
        include APPPATH . 'views/' . $view . '.php';
        return ob_get_clean();
    };
    return $ci;
}

function get_frontend_settings($key) {
    return 'default-new';
}

// Include the TestCase file
require_once APPPATH . 'tests/TestCase.php';
```

### 1.2. The `TestCase` Base Class

To simplify testing, a `TestCase` base class is provided at `application/tests/TestCase.php`. All new tests for the CodeIgniter application should extend this class. It provides a `request()` helper method to simulate HTTP requests to the application.

**File: `/root/tdd/academy/application/tests/TestCase.php`**

```php
<?php
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var CI_Controller
     */
    protected $CI;

    public function setUp(): void
    {
        parent::setUp();

        // Manually load the CodeIgniter instance
        $this->CI = &get_instance();
    }

    /**
     * Make a request to the application
     *
     * @param string $method
     * @param string $uri
     * @param array  $params
     * @return string
     */
    public function request(string $method, string $uri, array $params = []): string
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        // Set the parameters
        if ($method === 'GET') {
            $_GET = $params;
        } else {
            $_POST = $params;
        }

        // Capture the output
        ob_start();
        try {
            $view = $this->CI->load->view;
            return $view($uri, $params);
        } finally {
            ob_end_clean();
        }
    }
}
```

### 1.3. Running Tests

**Important:** Due to the mocking strategy employed, it is strongly recommended to run each test file individually to avoid potential conflicts and ensure a clean testing environment.

To run an individual test file, provide the path to the file:

```bash
./vendor/bin/phpunit application/tests/controllers/Mobilegpt_test.php
```

While it is technically possible to run all tests at once, it is not recommended:

```bash
# Not recommended
./vendor/bin/phpunit
```

### 1.4. Writing Tests

Create new test files in the `application/tests` directory. Your test classes should extend the `TestCase` class.

**Example: `application/tests/controllers/Mobilegpt_test.php`**

```php
<?php
class Mobilegpt_test extends TestCase
{
    public function test_index()
    {
        $output = $this->request('GET', 'mobilegpt/index');
        $this->assertStringContainsString('<chatbot-widget></chatbot-widget>', $output);
        $this->assertStringContainsString('<style type="text/css">', $output);
        $this->assertStringContainsString('header, section.footer {', $output);
    }
}
```

### 1.5. Test Implementation Notes

The current testing setup for the CodeIgniter application employs a mocking strategy to facilitate unit testing without a full-blown CodeIgniter environment. This is a "hack" necessary to test controllers in isolation.

- **Mocking Core Functions:** The `application/tests/bootstrap.php` file mocks the `get_instance()` function. This function normally returns the CodeIgniter super-object, but in our test environment, it returns a `stdClass` object with a mocked `load->view()` method.

- **`TestCase::request()` Method:** The `request()` method in the `TestCase` class does not dispatch a request through the entire CodeIgniter framework. Instead, it directly loads and renders the view file associated with the controller method. This is why the first argument to `$this->request()` in `Mobilegpt_test.php` is `'mobilegpt/index'`, which is the path to the view file, not a route.

This approach allows us to test the output of our views and simple controller logic without the overhead of the full framework. However, it's important to be aware of this limitation when writing tests, as it does not test the full routing and controller lifecycle.

---

## 2. Testing the `cyd` Directory

The `cyd` directory contains custom PHP scripts that operate independently of the main CodeIgniter application. We use a separate PHPUnit setup to test this code.

### 2.1. Setup Instructions

All commands should be run from within the `cyd` directory.

#### Step 2.1.1: Install Dependencies

From the `cyd` directory, run the following command to install the dependencies defined in the `cyd/composer.json` file. Note that we are using the `composer.phar` from the root directory.

```bash
cd cyd
php ../composer.phar install
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

        $log_content = "[$today 10:00:00] Today's log entry\n";
        $log_content .= "[$yesterday 12:00:00] Yesterday's log entry\n";

        file_put_contents($this->test_log_file, $log_content);

        clean_log_file($this->test_log_file);

        $cleaned_content = file_get_contents($this->test_log_file);

        $this->assertStringContainsString("[$today 10:00:00] Today's log entry", $cleaned_content);
        $this->assertStringNotContainsString("[$yesterday 12:00:00] Yesterday's log entry", $cleaned_content);
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

## 5. Testing Challenges

### Unit Test Environment

It is important to note that this is a unit test environment only. Tests are run from the command line and do not have access to a web server. This means that tests that rely on web server functionality, such as `$_SERVER` variables, will not work as expected.
