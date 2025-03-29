<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Ebook_manager extends CI_Controller
{ 
    public function __construct()
    {
        parent::__construct();
        // Your own constructor code
        $this->load->database();
        $this->load->library('session');
        $this->load->model('addons/ebook_model');
        // $this->load->library('stripe');
        /*cache control*/
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');

        if(!$this->session->userdata('user_id')){
            $this->session->set_flashdata('error_message', get_phrase('please_login_first'));
            redirect('home/login', 'refresh');
        }
    }

    public function all_ebooks(){
        $page_data['page_title'] = 'Ebook list';
        if($this->session->userdata('user_login')){
            $page_data['ebooks'] = $this->ebook_model->get_ebooks_by_user_id();
        }else{
            $page_data['ebooks'] = $this->ebook_model->get_all_ebooks()->result_array();
        }
        $page_data['page_name'] = 'all_ebooks';
        $this->load->view('backend/index',$page_data);
    }
    
    public function add_ebook()
    {
        $page_data['page_title'] = 'Add ebook';
        $page_data['page_name'] = 'add_ebook';
        $this->load->view('backend/index',$page_data);
    }

   

    //ebook category management

    public function ebook_category($param1 = "", $param2 = "")
    {
        if($param1 == "add"){
            $status = $this->ebook_model->add_ebook_category();
            if($status){
                $this->session->set_flashdata('flash_message', get_phrase('ebook_category_added_successfully'));
            }else{
                $this->session->set_flashdata('error_message', get_phrase('there_is_already_a_ebook_category_with_this_name'));
            }
        redirect(site_url('addons/ebook_manager/ebook_category'), 'refresh');

        }
        if($param1 == "delete"){
            $response = $this->ebook_model->delete_ebook_category($param2);
            $this->session->set_flashdata('flash_message', get_phrase('ebook_category_deleted_successfully'));
            redirect(site_url('addons/ebook_manager/ebook_category'), 'refresh');
        }
        if($param1 == "update")
        {
            $response = $this->ebook_model->update_ebook_category($param2);
            if($response == true){
                $this->session->set_flashdata('flash_message', get_phrase('ebook_category_updated_successfully'));
            }else{
                $this->session->set_flashdata('error_message', get_phrase('there_is_already_a_ebook_with_this_name'));
            }
            redirect(site_url('addons/ebook_manager/ebook_category'), 'refresh');
        }

        $page_data['categories'] = $this->ebook_model->get_ebook_categories();
        $page_data['page_title'] = 'Ebook Category';
        $page_data['page_name'] = 'ebook_category';
        $this->load->view('backend/index', $page_data);
    }

    public function add_ebook_category(){
        $this->load->view("backend/admin/ebook_category_add");
    }

    public function edit_ebook_category($category_id = ""){
        $data['ebook_category'] = $this->ebook_model->get_ebook_categories($category_id)->row_array();
        $this->load->view('backend/admin/ebook_category_edit', $data);
    }
    
    //ebook management
    public function edit_ebook($ebook_id = " ")
    {
       
        $page_data['ebook'] = $this->ebook_model->get_all_ebooks($ebook_id)->row_array();
        $page_data['page_title'] = get_phrase('edit_ebook');
        $page_data['page_name'] = 'ebook_edit';
        $this->load->view('backend/index', $page_data);
    }
    public function ebook($param1 = "", $param2 = "")
    {
        if($param1 == "add"){
            $response = $this->ebook_model->add_ebook();
            if($response){
                $this->session->set_flashdata('flash_message', get_phrase('ebook_added_successfully'));
            }else{
                $this->session->set_flashdata('error_message', get_phrase('ebook_add_unccessfull'));

            }
            redirect(site_url("addons/ebook_manager/ebook"), 'refresh');
        }
        if($param1 == "update"){
            $response = $this->ebook_model->update_ebook($param2);
            if($response){
                $this->session->set_flashdata('flash_message', get_phrase('ebook_updated_successfully'));
            }else{
                $this->session->set_flashdata('error_message', get_phrase('ebook_update_unsuccessfull'));

            }
            redirect(site_url("addons/ebook_manager/ebook"), 'refresh');
        }
        if($param1 == "status" && $this->session->userdata('admin_login')){
            $response = $this->ebook_model->update_ebook_status($param2);
            if($response){
                $this->session->set_flashdata('flash_message', get_phrase('ebook_activate_successfully'));
            }else{
                $this->session->set_flashdata('flash_message', get_phrase('ebook_deactivate_successfully'));

            }
            redirect(site_url("addons/ebook_manager/ebook"), 'refresh');

        }
        if($param1 == "delete"){
            $response = $this->ebook_model->delete_ebook($param2);
            if($response){
                $this->session->set_flashdata('flash_message', get_phrase('ebook_deleted_successfully'));
            }else{
                $this->session->set_flashdata('error_message', get_phrase('ebook_delete_unsuccessfull'));

            }
            redirect(site_url("addons/ebook_manager/ebook"), 'refresh');
        }

        if($this->session->userdata('user_login')){
            $page_data['ebooks'] = $this->ebook_model->get_ebooks_by_user_id()->result_array();
        }else{
            $page_data['ebooks'] = $this->ebook_model->get_all_ebooks()->result_array();
        }
        $page_data['page_name'] = 'all_ebooks';
        $page_data['page_title'] = "ebook_list";
        $this->load->view('backend/index', $page_data);
    }

    public function payment_history($revenue_type = "", $param1 = "")
    {
        if ($param1 != "") {
            $date_range                   = $this->input->get('date_range');
            $date_range                   = explode(" - ", $date_range);
            $page_data['timestamp_start'] = strtotime($date_range[0] . ' 00:00:00');
            $page_data['timestamp_end']   = strtotime($date_range[1] . ' 23:59:59');
        } else {
            $page_data['timestamp_start'] = strtotime(date("m/01/Y 00:00:00"));
            $page_data['timestamp_end']   = strtotime(date("m/t/Y 23:59:59"));
        }

        if($revenue_type == "admin_revenue" && $this->session->userdata('admin_login')){
            $page_data['payment_history'] = $this->ebook_model->get_revenue_by_user_type($page_data['timestamp_start'], $page_data['timestamp_end'], 'admin_revenue');
            $page_data['page_title'] = "ebook_admin_revenue";
            $page_data['page_name'] = "ebook_admin_revenue";
            $this->load->view('backend/index', $page_data);
        }else{
            $page_data['payment_history'] = $this->ebook_model->get_revenue_by_user_type($page_data['timestamp_start'], $page_data['timestamp_end'], 'instructor_revenue');
            $page_data['page_title'] = "ebook_instructor_revenue";
            $page_data['page_name'] = "ebook_instructor_revenue";
            $this->load->view('backend/index', $page_data);

        }

    }

    
}