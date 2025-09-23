**Overall Goal:** Improve the stability and observability of the PHP application. This involves two parts:
1.  Refactoring `cyd/error_viewer.php` to display logs from three files (`../error_log`, `error_log`, and `api/error_log`), show all log content upon loading, and then run a cleanup process to remove entries older than the current day.
2.  Hardening the `cyd/api/lawgpt_premium.php` endpoint by truncating user queries to 390 characters to prevent Tavily API failures and implementing centralized error logging to ensure all fatal errors are captured in `cyd/api/error_log`.

- [x] **Task 1: `error_viewer.php` - Add `api/error_log` Path:** Update the script to include the new log file located at `cyd/api/error_log`.
- [x] **Task 1: `error_viewer.php` - Display Full Logs:** Modify the `display_log` function to remove the 500-line limit and show the entire content of each log file.
- [x] **Task 1: `error_viewer.php` - Create Cleanup Function:** Implement a new `clean_log_file($file_path)` function that reads a log file, removes all entries dated before the current day (parsing the confirmed `[DD-Mon-YYYY]` timestamp), and overwrites the file with the cleaned content.
- [x] **Task 1: `error_viewer.php` - Integrate Cleanup Flow:** Update the main script to first display all logs and then call the `clean_log_file()` function for each log file.
- [x] **Task 2: `lawgpt_premium.php` - Implement Query Truncation:** Before calling the Tavily API, truncate the user's message to 390 characters to prevent query length errors.
- [x] **Task 2: `lawgpt_premium.php` - Centralize Error Logging:** Add `ini_set` directives and `error_log` calls to ensure all errors (including from Tavily and Grok-4) are consistently logged to `cyd/api/error_log`.
- [x] **Task 3: Unit Test Setup:** Create a new test file at `cyd/tests/ErrorViewerTest.php`.
- [x] **Task 3: Test Cleanup Logic:** Write a PHPUnit test for the `clean_log_file` function. The test will create a temporary log file with mixed dates and assert that only today's entries remain after the function runs.
- [x] **Task 3: Test Display Logic:** Write a PHPUnit test for the `display_log` function. The test will use output buffering to capture the function's HTML output and assert that it correctly matches the contents of a temporary test file.