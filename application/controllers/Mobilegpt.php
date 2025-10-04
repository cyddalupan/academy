<?php
    defined('BASEPATH') OR exit('No direct script access allowed');

    class Mobilegpt extends CI_Controller {

        public function __construct()
        {
            parent::__construct();
            $this->load->database();
            $this->load->library('session');
            // $this->user_model->check_session_data();
        }

        public function index()
        {
            $page_data['page_name'] = 'mobilegpt';
            $page_data['page_title'] = 'Mobile GPT';
            $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
        }
    }