# Gemini TDD Environment

This document outlines the process for setting up a Test-Driven Development (TDD) environment for this project.

## Branching Strategy

**All new development and testing should be done on the `beta` branch.**

---

## Testing Environment Setup

This project contains two distinct PHP applications, each with its own testing environment:

1.  **CodeIgniter Application (Main Application):** Located in the `application` and `system` directories.
2.  **`cyd` Application (Custom API):** Located in the `cyd` directory.

### 1. CodeIgniter Application

#### 1.1. Setup

From the project root, install the dependencies:

```bash
php composer.phar install
```

#### 1.2. Running Tests

To run an individual test file, provide the path to the file. This is the recommended way to run tests.

```bash
./vendor/bin/phpunit application/tests/controllers/Mobilegpt_test.php
```

### 2. `cyd` Application

#### 2.1. Setup

From the `cyd` directory, install the dependencies:

```bash
cd cyd
php ../composer.phar install
```

If it doesn't exist, create a `phpunit.xml.dist` file in the `cyd` directory:

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

And a `tests` directory:
```bash
mkdir tests
```


#### 2.2. Running Tests

To run all tests in the `cyd` directory, execute the PHPUnit binary from within the `cyd` directory:

```bash
cd cyd
./vendor/bin/phpunit
```

---

## Testing Philosophy: "Logic Verification"

Our testing approach focuses on verifying the application's logic with minimal reliance on external tools or complex mocking. The goal is to confirm that the code behaves as expected without necessarily performing real I/O operations (like database writes or full HTTP requests).

### Testing Controller Logic

The current test environment for the CodeIgniter application does not support direct testing of controller logic. The `TestCase::request()` method bypasses the controller and renders the view directly.

### Testing View Logic (Simplified View Testing)

The recommended approach is to test the logic within the view files. This is done by isolating the specific logic you want to test and preventing the parts of the view that are not relevant to the test from being rendered.

This can be achieved by:
1.  Adding a conditional check in the view to exclude certain parts, such as headers and footers, when the view is being rendered in a test environment.
2.  Passing a specific variable from the test to the view to activate the conditional check.

#### Example

In your test, pass an `is_test` variable to the `request` method:
```php
$output = $this->request('GET', 'frontend/default-new/index', ['page_name' => 'my_page', 'is_test' => true]);
```

In your `index.php` view, use this variable to conditionally include the header and footer:
```php
if (!isset($is_test)) {
    include 'header.php';
}

// ... view content ...

if (!isset($is_test)) {
    include 'footer.php';
}
```

### Dos and Don'ts

*   **DO:** Test the logic within your view files.
*   **DO:** Isolate the logic you want to test by simplifying the view.
*   **DO:** Use a specific variable (e.g., `is_test`) to control which parts of the view are rendered in the test environment.
*   **DO:** Add mock functions and methods to the `application/tests/bootstrap.php` file as needed to satisfy the dependencies of the simplified view.
*   **DO NOT:** Attempt to test controller logic directly.
*   **DO NOT:** Attempt to create a complete mock environment for the entire application.
*   **DO NOT:** Modify the core `TestCase.php` file unless absolutely necessary.
*   **DO NOT:** Hesitate to add mock functions for CodeIgniter's built-in functions (e.g., `site_url`, `get_settings`) in the `bootstrap.php` file.