<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Ebook extends CI_Controller
{ 
    public function __construct()
    {
        parent::__construct();
        // Your own constructor code
        $this->load->model('addons/ebook_model');
        $this->load->database();
        $this->load->library('session');
        // $this->load->library('stripe');
        /*cache control*/
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
    }  
    public function index(){
    
    }

    public function ebooks()
    {
        if (!$this->session->userdata('layout')) {
            $this->session->set_userdata('layout', 'list');
        }
        $layout = $this->session->userdata('layout');
        $selected_category_id = "all";
        $selected_price = "all";
        $selected_rating = "all";
        $search_text = "";
        // Get the category ids
        if (isset($_GET['category']) && !empty($_GET['category'] && $_GET['category'] != "all")) {
            $selected_category_id = $this->ebook_model->get_category_id($_GET['category']);
            
        }

        // Get the selected price
        if (isset($_GET['price']) && !empty($_GET['price'])) {
            $selected_price = $_GET['price'];
        }

       

        // Get the selected rating
        if (isset($_GET['rating']) && !empty($_GET['rating'])) {
            $selected_rating = $_GET['rating'];
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search_text = $_GET['search'];
            $page_data['search_value'] = $search_text;
        }



        if ($selected_category_id == "all" && $selected_price == "all" && $selected_rating == 'all' && empty($_GET['search'])) {
            // if (!addon_status('scorm_course')) {
            //     $this->db->where('course_type', 'general');
            // }
            $this->db->where('is_active', 1);
            $total_rows = $this->db->get('ebook')->num_rows();
            $config = array();
            $config = pagintaion($total_rows, 6);
            // $config['per_page'] = 6;
            $config['base_url']  = base_url('addons/ebook/ebooks/');
            $this->pagination->initialize($config);
            // if (!addon_status('scorm_course')) {
            //     $this->db->where('course_type', 'general');
            // }
            $this->db->where('is_active', 1);
            $page_data['ebooks'] = $this->db->get('ebook', $config['per_page'], $this->uri->segment(4))->result_array();
            $page_data['total_result'] = $total_rows;

        }
        
        else {
            $ebooks = $this->ebook_model->filter_ebook($selected_category_id, $selected_price, $selected_rating, $search_text);
            $page_data['ebooks'] = $ebooks;
            $page_data['total_result'] = count($ebooks);
        }
         
        $page_data['page_name']  = "ebook_page";
        $page_data['page_title'] = site_phrase('ebooks');
        $page_data['layout']     = $layout;
        $page_data['selected_category_id']     = $selected_category_id;
        $page_data['selected_price']     = $selected_price;
        $page_data['selected_rating']     = $selected_rating;
        $page_data['total_active_ebooks'] = $this->ebook_model->get_active_ebook()->num_rows();
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    public function ebook_details($slug ="", $ebook_id = "")
    {
        $page_data['page_name'] = "ebook_details";
        $page_data['page_title'] = get_phrase('Ebook details');
        $page_data['ebook_id'] = $ebook_id;
        $this->load->view('frontend/' . get_frontend_settings('theme') . '/index', $page_data);
    }

    public function my_ebooks(){
        if(!$this->session->userdata('user_login')){
            $this->session->set_flashdata('error_message', get_phrase('please_login_first'));
            redirect('home/login', 'refresh');
        }
        $page_data['page_name'] = "my_ebooks";
        $page_data['page_title'] = site_phrase('my_ebooks');
        $page_data['my_ebooks'] = $this->ebook_model->my_ebooks();
        $this->load->view('frontend/'.get_frontend_settings('theme').'/index', $page_data);
    
    }











    function buy($ebook_id = ""){
        if(!$this->session->userdata('user_login')){
            $this->session->set_flashdata('error_message', get_phrase('please_login_first'));
            redirect('home/login', 'refresh');
        }

        if($ebook_id == ""){
            $this->session->set_flashdata('error_message', get_phrase('please_enter_numeric_valid_ebook_id'));
            redirect(site_url('ebooks'), 'refresh');
        }

        $ebook_details = $this->ebook_model->get_ebooks_list($ebook_id)->row_array();
        //$instructor_details = $this->user_model->get_all_user($page_data['ebook_details']['user_id'])->row_array();
        
        $items = array();
        $total_payable_amount = 0;

        //item detail
        $item_details['id'] = $ebook_details['ebook_id'];
        $item_details['title'] = $ebook_details['title'];
        $item_details['thumbnail'] = $this->ebook_model->get_ebook_thumbnail_url($ebook_details['ebook_id']);
        $item_details['creator_id'] = $ebook_details['user_id'];
        $item_details['discount_flag'] = $ebook_details['discount_flag'];
        $item_details['discounted_price'] = $ebook_details['discounted_price'];
        $item_details['price'] = $ebook_details['price'];

        $item_details['actual_price'] = ($ebook_details['discount_flag'] == 1) ? $ebook_details['discounted_price'] : $ebook_details['price'];
        $item_details['sub_items'] = array();

        $items[] = $item_details;
        $total_payable_amount += $item_details['actual_price'];
        //ended item detail

        //included tax
        //$total_payable_amount = round($total_payable_amount + ($total_payable_amount/100) * get_settings('course_selling_tax'), 2);

        //common structure for all payment gateways and all type of payment
        $data['total_payable_amount'] = $total_payable_amount;
        $data['items'] = $items;
        $data['is_instructor_payout_user_id'] = false;
        $data['payment_title'] = get_phrase('pay_for_purchasing_ebook');
        $data['success_url'] = site_url('addons/ebook/success_ebook_payment');
        $data['cancel_url'] = site_url('payment');
        $data['back_url'] = site_url('ebook/ebook_details/'.slugify($ebook_details['title']).'/'.$ebook_id);

        $this->session->set_userdata('payment_details', $data);

        redirect(site_url('payment'), 'refresh');
    }

    function success_ebook_payment($payment_method = ""){
        //STARTED payment model and functions are dynamic here
        $response = false;
        $user_id = $this->session->userdata('user_id');
        $payment_details = $this->session->userdata('payment_details');
        $payment_gateway = $this->db->get_where('payment_gateways', ['identifier' => $payment_method])->row_array();
        $model_name = strtolower($payment_gateway['model_name']);
        if($payment_gateway['is_addon'] == 1 && $model_name != null){
            $this->load->model('addons/'.strtolower($payment_gateway['model_name']));
        }

        if($model_name != null){
            $payment_check_function = 'check_'.$payment_method.'_payment';
            $response = $this->$model_name->$payment_check_function($payment_method, 'ebook');
        }
        //ENDED payment model and functions are dynamic here
        
        if ($response === true) {
            $ebook_id = $payment_details['items'][0]['id'];
            $session_id = isset($_GET['session_id']) ? $_GET['session_id']:'';
            $this->ebook_model->ebook_purchase($payment_gateway['identifier'],$ebook_id, $payment_details['total_payable_amount'], $session_id);
            $this->session->set_userdata('payment_details', []);
            $this->session->set_flashdata('flash_message', site_phrase('payment_successfully_done'));
            redirect('home/my_ebooks', 'refresh');
        }else{
            $this->session->set_flashdata('error_message', site_phrase('an_error_occurred_during_payment'));
            redirect('ebook', 'refresh');
        }
    }

















    public function stripe_checkout($ebook_id = "")
    {
        if ($this->session->userdata('user_login') != 1)
            redirect('home', 'refresh');

        //checking price
        $ebook = $this->ebook_model->get_ebook_by_id($ebook_id)->row_array();
        if($ebook['discount_flag'] == 1){
            $amount_to_pay = $ebook['discounted_price'];
        }else{
            $amount_to_pay = $ebook['price'];
        }
        
        $page_data['user_details']    = $this->user_model->get_user($this->session->userdata('user_id'))->row_array();
        $page_data['ebook_id'] = $ebook_id;
        $page_data['amount_to_pay']   = $amount_to_pay;
        $this->load->view('ebook_payment/stripe/stripe_checkout', $page_data);
    }

    public function stripe_payment($user_id = "",$ebook_id = "", $session_id = "")
    {
        //THIS IS HOW I CHECKED THE STRIPE PAYMENT STATUS
        $response = $this->ebook_model->stripe_payment($user_id, $session_id);

        if ($response['payment_status'] === 'succeeded') {
            $this->ebook_model->ebook_purchase('stripe',$ebook_id, $ebook_details['price'], $session_id);

            $this->session->set_flashdata('flash_message', site_phrase('payment_successfully_done'));
            redirect('home/my_ebooks', 'refresh');

        } else {
           
            $this->session->set_flashdata('error_message', $response['status_msg']);
            redirect('ebook/my_ebooks', 'refresh');
    
        }
    }

    public function paypal_checkout($ebook_id = "")
    {
        if ($this->session->userdata('user_login') != 1 && $payment_request != 'true')
            redirect('home', 'refresh');
        $page_data['ebook_details'] = $this->ebook_model->get_ebook_by_id($ebook_id)->row_array();
        $page_data['ebook_id'] = $ebook_id;
        if ($page_data['ebook_details']['is_free'] != 1) :
            if ($page_data['ebook_details']['discount_flag'] == 1) :
                $total_price_of_checking_out = $page_data['ebook_details']['discounted_price'];
            else:
                $total_price_of_checking_out = $page_data['ebook_details']['price'];
            endif;
        else:
            $total_price_of_checking_out = 0;      
        endif;
        
        $page_data['user_details']    = $this->user_model->get_user($this->session->userdata('user_id'))->row_array();
        $page_data['amount_to_pay']   = $total_price_of_checking_out;
        $this->load->view('/ebook_payment/paypal/paypal_checkout', $page_data);
    }
    public function paypal_payment($user_id = "", $ebook_id = "", $paymentID = "", $paymentToken = "", $payerID = "") {
        if ($this->session->userdata('user_login') != 1){
            $this->session->set_flashdata('error_message', get_phrase('please_login_first'));
            redirect('home/login', 'refresh');
        }
        $ebook_details = $this->ebook_model->get_ebook_by_id($ebook_id)->row_array();
        $paypal_keys = get_settings('paypal');
        $paypal = json_decode($paypal_keys);

        if ($paypal[0]->mode == 'sandbox') {
            $paypalClientID = $paypal[0]->sandbox_client_id;
            $paypalSecret   = $paypal[0]->sandbox_secret_key;
        }else{
            $paypalClientID = $paypal[0]->production_client_id;
            $paypalSecret   = $paypal[0]->production_secret_key;
        }

        //THIS IS HOW I CHECKED THE PAYPAL PAYMENT STATUS
        $status = $this->payment_model->paypal_payment($paymentID, $paymentToken, $payerID, $paypalClientID, $paypalSecret);
        if (!$status) {
            $this->session->set_flashdata('error_message', site_phrase('an_error_occurred_during_payment'));
            redirect('ebook', 'refresh');
        }

        $this->ebook_model->ebook_purchase('paypal',$ebook_id, $ebook_details['price'], $paymentID, $paymentToken);
        // $this->email_model->bundle_purchase_notification($user_id);

       

       
        $this->session->set_flashdata('flash_message', site_phrase('payment_successfully_done'));
        redirect('home/my_ebooks', 'refresh');
        

    }

    public function razorpay_checkout($ebook_id = "")
    {
        if ($this->session->userdata('user_login') != 1 && $payment_request != 'true')
            redirect('home', 'refresh');
        $page_data['ebook_details'] = $this->ebook_model->get_ebook_by_id($ebook_id)->row_array();
        $page_data['ebook_id'] = $ebook_id;
        if ($page_data['ebook_details']['is_free'] != 1) :
            if ($page_data['ebook_details']['discount_flag'] == 1) :
                $total_price_of_checking_out = $page_data['ebook_details']['discounted_price'];
            else:
                $total_price_of_checking_out = $page_data['ebook_details']['price'];
            endif;
        else:
            $total_price_of_checking_out = 0;      
        endif;
        $page_data['preparedData'] = $this->ebook_model->razorpayPrepareData($total_price_of_checking_out);
        $page_data['user_details']    = $this->user_model->get_user($this->session->userdata('user_id'))->row_array();
        $page_data['amount_to_pay']   = $total_price_of_checking_out;
        $this->load->view('ebook_payment/razorpay/razorpay_checkout', $page_data);
    }

    public function razorpay_payment($ebook_id = "") {
        if ($this->session->userdata('user_login') != 1){
            $this->session->set_flashdata('error_message', get_phrase('please_login_first'));
            redirect('home/login', 'refresh');
        }
        $ebook_details = $this->ebook_model->get_ebook_by_id($ebook_id)->row_array();
        if($ebook_details['discount_flag'] == 1)
        {
            $amount =  $ebook_details['discounted_price'];
        }else{
            $amount = $ebook_details['price'];
        }
        $status = $this->ebook_model->razorpay_payment($_GET['razorpay_order_id'], $_GET['payment_id'], $amount, $_GET['signature']);

        if ($status != true) {
            $this->session->set_flashdata('error_message', site_phrase('an_error_occurred_during_payment'));
            redirect('ebook', 'refresh');
        }

        $this->ebook_model->ebook_purchase('razorpay',$ebook_id, $amount, $_GET['razorpay_order_id'], $_GET['payment_id'], $_GET['signature']);
       
        $this->session->set_flashdata('flash_message', site_phrase('payment_successfully_done'));
        redirect('home/my_ebooks', 'refresh');
        

    }

    function download_ebook_file($ebook_id = ""){
        $ebook = $this->ebook_model->get_ebook_by_id($ebook_id)->row_array();
        if($this->db->get_where('ebook_payment', array('user_id' => $this->session->userdata('user_id'), 'ebook_id' => $ebook_id))->num_rows() > 0 || $ebook['is_free']):

            $this->load->helper('download');
            $file_path = 'uploads/ebook/file/ebook_full/'.$ebook['file'];
            // check file exists    
            if (file_exists ( $file_path )) {
                // get file content
                $data = file_get_contents ( $file_path );
                //force download
                force_download ( rawurlencode(slugify($ebook['title'])).'.'.pathinfo($file_path, PATHINFO_EXTENSION), $data );
                return 'valid_access';
            }else{
                return get_phrase('File_not_found');
            }
        endif;
    }

    function ebook_rating($ebook_id = "", $param1 = ""){
        $page_data['user_ebook_rating'] = $this->ebook_model->get_user_rating($this->session->userdata('user_id'), $ebook_id);
        $page_data['ebook_id'] = $ebook_id;

        if($param1 == 'save_rating' && $page_data['user_ebook_rating']->num_rows() > 0){
            $data['rating'] = htmlspecialchars($_POST['rating']);
            $data['comment'] = htmlspecialchars($_POST['comment']);
            $this->db->where('ebook_id', $ebook_id);
            $this->db->update('ebook_reviews', $data);
            $this->session->set_flashdata('flash_message', site_phrase('rating_updated_successfully'));
            redirect('home/my_ebooks', 'refresh');
        }elseif($param1 == 'save_rating'){
            $data['user_id'] = $this->session->userdata('user_id');
            $data['ebook_id'] = $ebook_id;
            $data['rating'] = htmlspecialchars($_POST['rating']);
            $data['comment'] = htmlspecialchars($_POST['comment']);
            $data['added_date'] = time();
            $insert = $this->db->insert('ebook_reviews', $data);
            if($insert){
                $this->session->set_flashdata('flash_message', site_phrase('rating_added_successfully'));
            }else{
                $this->session->set_flashdata('flash_message', site_phrase('Somthing_wrong'));
            }
            redirect('home/my_ebooks', 'refresh');
        }
        $this->load->view('frontend/'.get_frontend_settings('theme').'/ebook_rating', $page_data);
    }

    function student_purchase_history(){
        $page_data['payment_history'] = $this->ebook_model->payment_history_by_user_id($this->session->userdata('user_id'));
        $this->load->view('frontend/'.get_frontend_settings('theme').'/ebook_purchase_history', $page_data);
    }

    function ebook_invoice($payment_id = ""){
        $page_data['page_name'] = "ebook_invoice";
        $page_data['page_title'] = site_phrase('ebook_invoice');

        $this->db->where('payment_id', $payment_id);
        $page_data['payment'] = $this->db->get('ebook_payment')->row_array();
        $page_data['ebook'] = $this->ebook_model->get_ebook_by_id($page_data['payment']['ebook_id'])->row_array();
        $this->load->view('frontend/'.get_frontend_settings('theme').'/index', $page_data);
    }
    function view($id = "", $is_pdf = ""){
        $user_id = $this->session->userdata('user_id');
        $ebook = $this->ebook_model->get_ebook_by_id($id)->row_array();
        $ebook_payment = $this->db->get_where('ebook_payment', array('user_id' => $user_id, 'ebook_id' => $id));
        $admin_login = $this->session->userdata('admin_login');

        if($ebook_payment->num_rows() > 0 || $ebook['is_free'] || $admin_login == 1 || $ebook['user_id'] == $user_id){
            $file_path = 'uploads/ebook/file/ebook_full/'.$ebook['file'];
            // check file exists    
            if (file_exists ( $file_path ) && $is_pdf == 'pdf') {
                // get file content
                echo file_get_contents ( $file_path );
            }else{
                $this->load->view('frontend/'.get_frontend_settings('theme').'/view', ['ebook' => $ebook]);
            }
        }else{
            $this->session->set_flashdata('error_message', site_phrase('Please buy first then try again'));
            redirect('home/my_ebooks', 'refresh');
        }
    }

    
}