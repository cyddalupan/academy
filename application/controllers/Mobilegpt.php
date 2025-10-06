<?php
    defined('BASEPATH') OR exit('No direct script access allowed');

    class Mobilegpt extends CI_Controller {

        public function __construct()
        {
            parent::__construct();
            $this->load->database();
            $this->load->library('session');
            $this->load->model('user_model');
            $this->user_model->check_session_data();
        }

        public function index()
        {
            if ($this->input->get('device_id')) {
                $this->session->set_userdata('device_id', $this->input->get('device_id'));
            }

            if ($this->session->userdata('device_id')) {
                $query = $this->db->get_where('users', array('device_id' => $this->session->userdata('device_id')));
                if ($query->num_rows() > 0) {
                    $row = $query->row();
                    $this->session->set_userdata('user_id', $row->id);
                }
            }

            $user_id = $this->input->get('user_id');
            if ($user_id && !$this->session->userdata('user_id')) {
                $query = $this->db->get_where('users', array('id' => $user_id));
                if ($query->num_rows() > 0) {
                    $row = $query->row();
                    $this->session->set_userdata('user_id', $row->id);
                    $this->session->set_userdata('role_id', $row->role_id);
                    $this->session->set_userdata('role', get_user_role('user_role', $row->id));
                    $this->session->set_userdata('name', $row->first_name . ' ' . $row->last_name);
                    $this->session->set_userdata('is_instructor', $row->is_instructor);
                    $this->session->set_userdata('user_login', '1');
                }
            }

            if (!$this->session->userdata('user_id')) {
                $page_data['relogin_script'] = true;
            } else {
                $page_data['relogin_script'] = false;
            }

            $page_data['page_name'] = 'mobilegpt';
            $page_data['page_title'] = 'Mobile GPT';
            $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
        }
    }