<?php
// Define the path to the CodeIgniter index.php file
define('FCPATH', realpath(__DIR__ . '/../../') . '/');
define('APPPATH', FCPATH . 'application/');
define('BASEPATH', FCPATH . 'system/');

// Include the CodeIgniter bootstrap file
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