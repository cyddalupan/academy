<?php
// Define the path to the CodeIgniter index.php file
define('FCPATH', realpath(__DIR__ . '/../../') . '/');
define('APPPATH', FCPATH . 'application/');
define('BASEPATH', FCPATH . 'system/');

// Mock CodeIgniter core functions
function &get_instance() {
    $ci = new stdClass();
    $ci->load = new stdClass();
    $view_closure = function($view, $data = []) {
        extract($data);
        ob_start();
        include APPPATH . 'views/' . $view . '.php';
        return ob_get_clean();
    };
    $ci->load->view = $view_closure->bindTo($ci);

    $ci->load->database = function() {};
    $ci->load->library = function($library) {};
    $ci->load->model = function($model) {};


    // Mock models needed by the views
    $mock_result = new stdClass();
    $mock_result->result_array = function() { return []; };
    $mock_result->num_rows = function() { return 0; };
    $mock_result->row = function() { return null; };
    $mock_result->result = function() { return []; };

    $ci->crud_model = new stdClass();
    $ci->crud_model->get_categories = function() use ($mock_result) { return $mock_result; };
    $ci->crud_model->get_sub_categories = function($id) use ($mock_result) { return $mock_result; };
    $ci->crud_model->get_latest_blogs = function() use ($mock_result) { return $mock_result; };
    $ci->crud_model->get_course_by_id = function($id) use ($mock_result) { return $mock_result; };
    $ci->crud_model->get_course_thumbnail_url = function($id) { return ''; };
    $ci->crud_model->get_all_blogs = function($id) use ($mock_result) { return $mock_result; };
    $ci->crud_model->get_all_languages = function() { return []; };
    $ci->crud_model->get_system_languages = function() { return []; };

    $ci->ebook_model = new stdClass();
    $ci->ebook_model->get_ebook_by_id = function($id) use ($mock_result) { return $mock_result; };

    // Mock session for views
    $ci->session = new class {
        private $userdata = [];

        public function userdata($key) {
            return $this->userdata[$key] ?? null;
        }

        public function set_userdata($key, $value) {
            $this->userdata[$key] = $value;
        }

        public function flashdata($key) {
            return null;
        }

        public function set_flashdata($key, $value) {
            // do nothing
        }
    };

    // Mock uri for views
    if (!class_exists('Mock_Uri')) {
        class Mock_Uri {
            public function segment($segment) {
                return null;
            }
        }
    }
    $ci->uri = new Mock_Uri();

    $ci->user_model = new stdClass();
    $ci->user_model->check_session_data = function() {};

    return $ci;
}

// Mock global helper functions
function get_settings($key) { return null; }
function get_frontend_settings($key) { return 'default-new'; }
function getIsoCode($language) { return 'en'; }
function base_url($uri = '') { return 'http://localhost/'; }
function get_seo_data() { return []; }
function get_phrase($key) { return $key; }
function site_phrase($key) { return $key; }
function site_url($uri = '') { return 'http://localhost/' . $uri; }
function currency($price = '') { return $price; }
function get_cart_items() { return []; }
function get_categories() { return []; }
function get_sub_categories($category_id) { return []; }
function get_latest_blogs() { return []; }
function current_url() { return 'http://localhost/test-url'; }
function get_current_banner() { return ''; }
function validate_cart_items() { return []; }
function get_top_courses() { return []; }
function get_latest_courses() { return []; }


// Include the TestCase file
require_once APPPATH . 'tests/TestCase.php';