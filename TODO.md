Overall Goal: Create a new page at `/mobilegpt` that loads a globally available Angular widget, hides the header and footer, and is verified by a new unit test.

- [x] **Create Controller File:** Create a new, empty file named `Mobilegpt.php` in the `application/controllers/` directory to house the page's backend logic.

- [x] **Define Controller Class & Constructor:** In `Mobilegpt.php`, define the `Mobilegpt` class extending `CI_Controller` and add the `__construct` method to load the `database`, `session`, and run `user_model->check_session_data()` to ensure it integrates correctly with the application.
    ```php
    <?php
    defined('BASEPATH') OR exit('No direct script access allowed');

    class Mobilegpt extends CI_Controller {

        public function __construct()
        {
            parent::__construct();
            $this->load->database();
            $this->load->library('session');
            $this->user_model->check_session_data();
        }
    }
    ```

- [x] **Implement Controller Index Method:** Add the `index` method to the `Mobilegpt` class. This method will set the `$page_data` needed to render the page within the site's theme template.
    ```php
    public function index()
    {
        $page_data['page_name'] = 'mobilegpt';
        $page_data['page_title'] = 'Mobile GPT';
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }
    ```

- [x] **Create View File:** Create a new file named `mobilegpt.php` in `application/views/frontend/default-new/`. This file will contain the specific HTML for the new page.

- [x] **Add Widget and Hiding CSS to View:** In `mobilegpt.php`, add the `<chatbot-widget>` tag to load the Angular element and an inline `<style>` block to visually hide the header and footer.
    ```html
    <chatbot-widget></chatbot-widget>

    <style type="text/css">
        header, section.footer {
            display: none !important;
        }
    </style>
    ```

- [x] **Create Test File:** Create a new test file named `Mobilegpt_test.php` in the `application/tests/controllers/` directory to verify the new controller's functionality.

- [x] **Implement Unit Test:** In `Mobilegpt_test.php`, add a test case that makes a `GET` request to the `mobilegpt` page and asserts that the response HTML contains both the `<chatbot-widget>` tag and the inline style block that hides the header and footer.
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

- [x] **Install Dependencies and Run Tests:** Execute the PHPUnit test suite from the root of the project to confirm that the new test passes and that no existing functionality has been broken.

    1.  **Install Dependencies:** Run `php composer.phar install` to ensure all dependencies for the CodeIgniter application are installed.
    2.  **Run Tests:** Execute the test suite with the command: `./vendor/bin/phpunit`

- **Note on Testing:** Due to missing CodeIgniter core files, a mocking strategy has been implemented in `application/tests/bootstrap.php` to allow individual tests to run. This involves mocking `get_instance()` and `get_frontend_settings()` functions. This approach should be used for all future tests.