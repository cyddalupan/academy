<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once(APPPATH."libraries/razorpay-php/Razorpay.php");
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
class Ebook_model extends CI_Model
{


    public function get_category_id($slug = "")
    {
        $category_details = $this->db->get_where("ebook_category", array("slug" => $slug))->row_array();
        return $category_details['category_id'];
    }
    public function get_categories($param1 = "")
    {
        if($param1 != ""){
            $this->db->where('category_id',$param1);
        }
        return $this->db->get('ebook_category');
    }

    public function get_active_ebook(){
        $this->db->where('is_active', 1);
        return $this->db->get('ebook');
    }
    public function get_active_addon_by_category_id($category_id = "", $category_id_type = "category_id"){
        $this->db->where($category_id_type, $category_id);
        $this->db->where('is_active', 1);
        return $this->db->get('ebook');
    }
    public function get_category_details_by_id($id)
    {
        return $this->db->get_where('ebook_category', array('category_id' => $id));
    }
    function filter_ebook($selected_category_id = "", $selected_price = "", $selected_rating = "", $search_text ="")
    {

        $ebook_ids = array();
        if ($selected_category_id != "all") {
            $category_id = $this->get_category_details_by_id($selected_category_id)->row('category_id');
        }

        if ($selected_rating != "all") {
            $this->db->where('is_active', 1);
            $ebooks = $this->db->get('ebook')->result_array();
            foreach ($ebooks as $key => $ebook) {
                $total_rating =  $this->get_ratings( $ebook['ebook_id'], true)->row()->rating;
                $number_of_ratings = $this->get_ratings($ebook['ebook_id'])->num_rows();
                if ($number_of_ratings > 0) {
                    $average_ceil_rating = ceil($total_rating / $number_of_ratings);
                    if ($average_ceil_rating == $selected_rating) {
                        array_push($ebook_ids, $ebook['ebook_id']);
                    }
                    
                }
                
            }
        }

        if($search_text != ""){
            $this->db->group_start();
            
            $this->db->like('title', $search_text);
            $this->db->or_like('description', $search_text)->group_end();
        }
        
        if ($selected_category_id != "all") {
            
            $this->db->where('category_id', $category_id);
        }
        
        if ($selected_price != "all") {
            if ($selected_price == "paid") {
               
                $this->db->where('is_free', 0);
            } elseif ($selected_price == "free") {
               
                $this->db->where('is_free', 1);
            }
        }

        if ($selected_rating != "all") {
            if(!empty($ebook_ids)){
                $this->db->where_in('ebook_id', $ebook_ids);

            }else{
                $this->db->where_in('ebook_id', "");
            }
        }
        $this->db->where('is_active', 1);
            
        return $this->db->get('ebook')->result_array();
        
           
        
    }

    public function get_ratings ($ratable_id = "", $is_sum = false)
    {
        if ($is_sum) {
            $this->db->select_sum('rating');
            return $this->db->get_where('ebook_reviews', array('ebook_id' => $ratable_id));
        } else {
            return $this->db->get_where('ebook_reviews', array('ebook_id' => $ratable_id));
        }
    }

    function get_user_rating($user_id = "", $ebook_id = ""){
        $this->db->where('user_id', $user_id);
        $this->db->where('ebook_id', $ebook_id);
        return $this->db->get('ebook_reviews');
    }

    public function get_ebook_thumbnail_url($ebook_id = '')
    {
        $thumb = $this->db->get_where('ebook', array('ebook_id' => $ebook_id))->row('thumbnail');
       
        if (file_exists('uploads/ebook/thumbnails/' . $thumb))
            return base_url() . 'uploads/ebook/thumbnails/' . $thumb;
        else
            return base_url() . 'uploads/ebook/thumbnails/placeholder.png';
    }

    public function get_ebook_banner_url($ebook_id = ''){
        $banner = $this->db->get_where('ebook', array('ebook_id' => $ebook_id))->row('banner');
        if(file_exists('uploads/ebook/banner/'. $banner))
            return base_url() . 'uploads/ebook/banner/' . $banner;
        else
            return base_url() . 'uploads/ebook/banner/placeholder.png';
    }

    public function get_ebook_by_id($ebook_id = "")
    {
       return $this->db->get_where("ebook", array("ebook_id" => $ebook_id));

    }
    public function get_ebooks_by_user_id($user_id = "", $ebook_id = "")
    {
        if($user_id == ""){
            $this->db->where('user_id', $this->session->userdata('user_id'));
        }
        if($ebook_id > 0){
            $this->db->where('ebook_id', $ebook_id);
        }
       return $this->db->get("ebook");

    }
    public function get_ebooks($category_id = "")
    {
        if ($category_id > 0) {
            $this->db->where('category_id', $category_id);
            $this->db->where('is_active', 1);

            return $this->db->get("ebook");
        } else {
            $this->db->where('is_active', 1);

            return $this->db->get('ebook');
        }
    }

    public function get_percentage_of_specific_rating($rating = "", $ratable_type = "", $ratable_id = "")
    {
        $number_of_user_rated = $this->db->get_where('ebook_reviews', array(
            // 'ratable_type' => $ratable_type,
            'ebook_id'   => $ratable_id
        ))->num_rows();

        $number_of_user_rated_the_specific_rating = $this->db->get_where('ebook_reviews', array(
            // 'ratable_type' => $ratable_type,
            'ebook_id'   => $ratable_id,
            'rating'       => $rating
        ))->num_rows();

        //return $number_of_user_rated.' '.$number_of_user_rated_the_specific_rating;
        if ($number_of_user_rated_the_specific_rating > 0) {
            $percentage = ($number_of_user_rated_the_specific_rating / $number_of_user_rated) * 100;
        } else {
            $percentage = 0;
        }
        return floor($percentage);
    }
    public function get_user($user_id = 0)
    {
        if ($user_id > 0) {
            $this->db->where('id', $user_id);
        }
        // $this->db->where('role_id', 2);
        return $this->db->get('users');
    }

    //backend

    public function get_all_ebooks($ebook_id = " ")
    {
        if($ebook_id > 0){
            $this->db->where('ebook_id',$ebook_id);
        }

        $this->db->order_by('ebook_id', 'desc');
        return $this->db->get('ebook');
    }
    function get_ebook_categories($ebook_category_id = ""){
        if($ebook_category_id > 0){
            $this->db->where('category_id', $ebook_category_id);
        }
        return $this->db->get('ebook_category');
    }

    public function get_ebooks_list($ebook_id = " ")
    {
        if($ebook_id > 0){
            $this->db->where('ebook_id',$ebook_id);
        }
       
        $this->db->where("is_active", 1);
        $this->db->order_by('ebook_id', 'desc');
        return $this->db->get('ebook');
    }
    public function get_ebooks_by_category_id($category_id = ""){
        $this->db->where('category_id', $category_id);
        $this->db->where('is_active', 1);
        return $this->db->get('ebook');
    }

    public function add_ebook(){
        $data['title'] = htmlspecialchars($this->input->post('title'));
        $data['description'] = htmlspecialchars(remove_js($this->input->post('description', false)));
        $data['category_id'] = htmlspecialchars($this->input->post('category_id'));
        // echo $_FILES['banner']['name'];
        // echo $_FILES['thumbnail']['name'];
        // $ext  = (new SplFileInfo($path))->getExtension();
        if ($_FILES['thumbnail']['name'] != "") {
            $ext  = (new SplFileInfo($_FILES['thumbnail']['name']))->getExtension();
            $data['thumbnail'] = md5(rand(10000000, 20000000)) .'.'. $ext;
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], 'uploads/ebook/thumbnails/' . $data['thumbnail']);
        }else{
            $data['thumbnail'] = 'placeholder.png';
        }

        // if ($_FILES['banner']['name'] != "") {
        //     $ext  = (new SplFileInfo($_FILES['banner']['name']))->getExtension();
        //     $data['banner'] = md5(rand(10000000, 20000000)) .'.'. $ext;
        //     move_uploaded_file($_FILES['banner']['tmp_name'], 'uploads/ebook/banner/' . $data['banner']);
        // }else{
            $data['banner'] = 'placeholder.png';
        // }
        if ($_FILES['ebook_preview_file']['name'] != "") {
            $ext  = (new SplFileInfo($_FILES['ebook_preview_file']['name']))->getExtension();
            $data['preview'] = md5(rand(10000000, 20000000)) .'.'. $ext;
            move_uploaded_file($_FILES['ebook_preview_file']['tmp_name'], 'uploads/ebook/file/ebook_preview/' . $data['preview']);
        }
        if ($_FILES['ebook_complete_file']['name'] != "") {
            $ext  = (new SplFileInfo($_FILES['ebook_complete_file']['name']))->getExtension();
            $data['file'] = md5(rand(10000000, 20000000)) .'.'. $ext;
            move_uploaded_file($_FILES['ebook_complete_file']['tmp_name'], 'uploads/ebook/file/ebook_full/' .$data['file']);
        }
        $data['user_id'] = $this->session->userdata('user_id');
      

        $data['price'] = $this->input->post('price');
        $flag = $this->input->post('discount_flag');
        $free = $this->input->post('is_free');
        $data['publication_name'] = $this->input->post('publication_name');
        $data['edition'] = $this->input->post('edition');
        if($flag != 1){
            $flag = 0;

        }
        if($free != 1){
            $free = 0;
        }
        if($this->session->userdata('admin_login')){
            $data['is_active'] = 1;
        }
        else{
            $data['is_active'] = 0;
        }
        $data['discounted_price'] = $this->input->post('discounted_price');
        $data['no_of_pages'] = $this->input->post('no_of_pages');
        $data['is_free'] = $free;
        $data['discount_flag'] = $flag;
        $data['added_date'] = strtotime(date('D, d-M-Y'));

        //seo-fileds
        $data['meta_title'] = html_escape($this->input->post('meta_title'));
        $data['meta_description'] = html_escape($this->input->post('meta_description'));
        $data['meta_keywords'] = html_escape($this->input->post('meta_keywords'));
        $data['og_title'] = html_escape($this->input->post('og_title'));
        $data['og_description'] = html_escape($this->input->post('og_description'));
        $data['json_ld'] = $this->input->post('json_ld');

        $this->db->insert('ebook', $data);
        return true;
    }

    public function update_ebook($ebook_id = ""){
        $data['title'] = htmlspecialchars($this->input->post('title'));
        $data['description'] = htmlspecialchars(remove_js($this->input->post('description', false)));
        $data['category_id'] = htmlspecialchars($this->input->post('category_id'));
        $ebook = $this->get_ebook_by_id($ebook_id)->row_array();
        if ($_FILES['thumbnail']['name'] != "") {
            $ext  = (new SplFileInfo($_FILES['thumbnail']['name']))->getExtension();
            unlink('uploads/ebook/thumbnails/'.$ebook['thumbnail']);
            $data['thumbnail'] = md5(rand(10000000, 20000000)) .'.'. $ext;
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], 'uploads/ebook/thumbnails/' . $data['thumbnail']);
        }
        // if ($_FILES['banner']['name'] != "") {
        //     $ext  = (new SplFileInfo($_FILES['banner']['name']))->getExtension();
        //     unlink('uploads/ebook/banner/'.$ebook['banner']);
        //     $data['banner'] = md5(rand(10000000, 20000000)) .'.'. $ext;
        //     move_uploaded_file($_FILES['banner']['tmp_name'], 'uploads/ebook/banner/' . $data['banner']);
        // }
        if ($_FILES['ebook_preview_file']['name'] != "") {
            $ext  = (new SplFileInfo($_FILES['ebook_preview_file']['name']))->getExtension();
            unlink('uploads/ebook/file/ebook_preview/'.$ebook['preview']);
            $data['preview'] = md5(rand(10000000, 20000000)) .'.'. $ext;
            move_uploaded_file($_FILES['ebook_preview_file']['tmp_name'], 'uploads/ebook/file/ebook_preview/' . $data['preview']);
        }
        if ($_FILES['ebook_complete_file']['name'] != "") {
            unlink('uploads/ebook/file/ebook_full/'.$ebook['file']);
            $ext  = (new SplFileInfo($_FILES['ebook_complete_file']['name']))->getExtension();
            $data['file'] = md5(rand(10000000, 20000000)) .'.'. $ext;
            move_uploaded_file($_FILES['ebook_complete_file']['tmp_name'], 'uploads/ebook/file/ebook_full/' .$data['file']);
        }
      

        $data['price'] = $this->input->post('price');
        $flag = $this->input->post('discount_flag');
        $free = $this->input->post('is_free');
        $data['publication_name'] = $this->input->post('publication_name');
        $data['edition'] = $this->input->post('edition');
        if($flag != 1){
            $flag = 0;

        }
        if($free != 1){
            $free = 0;
        }

        $data['discounted_price'] = $this->input->post('discounted_price');
        $data['no_of_pages'] = $this->input->post('no_of_pages');
        $data['is_free'] = $free;
        $data['discount_flag'] = $flag;
        $data['added_date'] = strtotime(date('D, d-M-Y'));

        //seo-fileds
        $data['meta_title'] = html_escape($this->input->post('meta_title'));
        $data['meta_description'] = html_escape($this->input->post('meta_description'));
        $data['meta_keywords'] = html_escape($this->input->post('meta_keywords'));
        $data['og_title'] = html_escape($this->input->post('og_title'));
        $data['og_description'] = html_escape($this->input->post('og_description'));
        $data['json_ld'] = $this->input->post('json_ld');

        if($this->session->userdata('user_login')){
            $this->db->where('user_id', $this->session->userdata('user_id'));
        }
        
        $this->db->where('ebook_id',$ebook_id);
        $this->db->update('ebook', $data);
        return true;
    }

    public function update_ebook_status($ebook_id = ""){
        $ebook = $this->get_ebook_by_id($ebook_id)->row_array();
        if($ebook['is_active'] == 0){
            $this->db->where('ebook_id', $ebook_id);
            $this->db->update('ebook', array('is_active' => 1));
            return true;
        }
        if($ebook['is_active'] == 1){
            $this->db->where('ebook_id', $ebook_id);
            $this->db->update('ebook', array('is_active' => 0));
            return false;
        }
        
    }

    public function delete_ebook($ebook_id = "")
    {
        if($this->session->userdata('user_login')){
            $this->db->where('user_id', $this->session->userdata('user_id'));
        }
        $this->db->where('ebook_id', $ebook_id);
        $this->db->delete('ebook');

        return true;

    }

    public function add_ebook_category()
    {
        $data['title'] = htmlspecialchars($this->input->post('title'));
        $data['slug'] = slugify($data['title']);
        // $data['thumbnail'] = htmlspecialchars($this->input->post('thumbnail'));
        $data['added_date'] = time();
        if (!file_exists('uploads/ebook/thumbnails/category_thumbnails')) {
            mkdir('uploads/ebook/thumbnails/category_thumbnails', 0777, true);
        }
        elseif ($_FILES['category_thumbnail']['name'] == "") {
            $data['thumbnail'] = 'category-thumbnail.png';
        } 
        else {
            $data['thumbnail'] = md5(rand(10000000, 20000000)) . '.jpg';
            move_uploaded_file($_FILES['category_thumbnail']['tmp_name'], 'uploads/ebook/thumbnails/category_thumbnails/' . $data['thumbnail']);
        }
        
        $this->db->where('slug', $data['slug']);
        $row = $this->db->get('ebook_category');

        if($row->num_rows() > 0)
        {
            return false;
        }else{
            $this->db->insert('ebook_category', $data);
            return true;
        }

    }
    function delete_ebook_category($category_id = ""){
        $this->db->where('category_id', $category_id);
        $this->db->delete('ebook_category');
    }
    function update_ebook_category($category_id = ""){
        $data['title'] = htmlspecialchars($this->input->post('title'));
        $data['slug'] = slugify($data['title']);
        // $data['thumbnail'] = htmlspecialchars($this->input->post('thumbnail'));
        $data['added_date'] = time();
        if (!file_exists('uploads/ebook/thumbnails/category_thumbnails')) {
            mkdir('uploads/ebook/thumbnails/category_thumbnails', 0777, true);
        }
        elseif ($_FILES['category_thumbnail']['name'] == "") {
            $data['thumbnail'] = 'category-thumbnail.png';
        } 
        else {
            $data['thumbnail'] = md5(rand(10000000, 20000000)) . '.jpg';
            move_uploaded_file($_FILES['category_thumbnail']['tmp_name'], 'uploads/ebook/thumbnails/category_thumbnails/' . $data['thumbnail']);
        }
        
        $this->db->where('slug', $data['slug']);
        $row = $this->db->get('ebook_category');
        if($row->num_rows() > 0 && $row->row('category_id') != $category_id){
            return false;
        }else{
            $this->db->where('category_id', $category_id);
            $this->db->update('ebook_category', $data);
            return true;
        }
    }

    public function get_instructor_wise_ebooks($instructor_id = "", $return_as = "")
    {
        
        $this->db->where('user_id', $instructor_id);

        

        $ebooks = $this->db->get('ebook');
       
        
        if ($return_as == 'simple_array') {
            $array = array();
            foreach ($ebooks->result_array() as $ebook) {
                if (!in_array($ebook['ebook_id'], $array)) {
                    array_push($array, $ebook['ebook_id']);
                }
            }
            return $array;
        } else {
            
            return $ebooks;
        }
    }
    public function get_instructor_wise_ebook_ratings($instructor_id = "", $ratable_type = "", $is_sum = false)
    {
       
        $ebook_ids = $this->get_instructor_wise_ebooks($instructor_id, 'simple_array');
        if ($is_sum) {
            $this->db->where('ratable_type', $ratable_type);
            $this->db->where_in('ratable_id', $course_ids);
            $this->db->select_sum('rating');
            return $this->db->get('rating');
        } else {
            // $this->db->where('ratable_type', $ratable_type);
            $this->db->where_in('ebook_id', $ebook_ids);
            return $this->db->get('ebook_reviews');
        }
    }

    function my_ebooks(){
        $data['user_id'] = $this->session->userdata('user_id');
        $ebook_payments = $this->db->get_where('ebook_payment', array('user_id' =>$data['user_id']))->result_array();

        $arr = array(0);
        foreach($ebook_payments as $ebook_payment):
            array_push($arr, $ebook_payment['ebook_id']);
        endforeach;
        $this->db->where('is_active', 1);
        $this->db->where_in('ebook_id', $arr);
        return $this->db->get('ebook');

    }

    // VALIDATE STRIPE PAYMENT
    public function stripe_payment($user_id = "", $session_id = "") {
        $stripe_keys = get_settings('stripe_keys');
        $values = json_decode($stripe_keys);
        if ($values[0]->testmode == 'on') {
            $public_key = $values[0]->public_key;
            $secret_key = $values[0]->secret_key;
        } else {
            $public_key = $values[0]->public_live_key;
            $secret_key = $values[0]->secret_live_key;
        }


        // Stripe API configuration
        define('STRIPE_API_KEY', $secret_key);
        define('STRIPE_PUBLISHABLE_KEY', $public_key);

        $status_msg = '';
        $transaction_id = '';
        $paid_amount = '';
        $paid_currency = '';
        $payment_status = '';

        // Check whether stripe checkout session is not empty
        if($session_id != ""){
            //$session_id = $_GET['session_id'];

            // Include Stripe PHP library
            require_once APPPATH.'libraries/Stripe/init.php';

            // Set API key
            \Stripe\Stripe::setApiKey(STRIPE_API_KEY);

            // Fetch the Checkout Session to display the JSON result on the success page
            try {
                $checkout_session = \Stripe\Checkout\Session::retrieve($session_id);
            }catch(Exception $e) {
                $api_error = $e->getMessage();
            }

            if(empty($api_error) && $checkout_session){
                // Retrieve the details of a PaymentIntent
                try {
                    $intent = \Stripe\PaymentIntent::retrieve($checkout_session->payment_intent);
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    $api_error = $e->getMessage();
                }

                // Retrieves the details of customer
                try {
                    // Create the PaymentIntent
                    $customer = \Stripe\Customer::retrieve($checkout_session->customer);
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    $api_error = $e->getMessage();
                }

                if(empty($api_error) && $intent){
                    // Check whether the charge is successful
                    if($intent->status == 'succeeded'){
                        // Customer details
                        $name = $customer->name;
                        $email = $customer->email;

                        // Transaction details
                        $transaction_id = $intent->id;
                        $paid_amount = ($intent->amount/100);
                        $paid_currency = $intent->currency;
                        $payment_status = $intent->status;

                        // If the order is successful
                        if($payment_status == 'succeeded'){
                            $status_msg = get_phrase("Your_Payment_has_been_Successful");
                        }else{
                            $status_msg = get_phrase("Your_Payment_has_failed");
                        }
                    }else{
                        $status_msg = get_phrase("Transaction_has_been_failed");;
                    }
                }else{
                    $status_msg = get_phrase("Unable_to_fetch_the_transaction_details"). ' ' .$api_error;
                }

                $status_msg = 'success';
            }else{
                $status_msg = get_phrase("Transaction_has_been_failed").' '.$api_error;
            }
        }else{
            $status_msg = get_phrase("Invalid_Request");
        }

        $response['status_msg'] = $status_msg;
        $response['transaction_id'] = $transaction_id;
        $response['paid_amount'] = $paid_amount;
        $response['paid_currency'] = $paid_currency;
        $response['payment_status'] = $payment_status;
        $response['stripe_session_id'] = $session_id;
        $response['payment_method'] = 'stripe';

        return $response;
    }

    function ebook_purchase($method = "",$ebook_id= "", $amount = "", $transaction_id = "", $session_id = "", $user_id = ""){
     

        $ebook_details = $this->ebook_model->get_ebook_by_id($ebook_id)->row_array();
        if($ebook_details['discount_flag'] == 1)
        {
            $data['paid_amount'] =  $ebook_details['discounted_price'];
        }else{
            $data['paid_amount'] = $ebook_details['price'];
        }

        if($user_id == ""){
            $user_id = $this->session->userdata('user_id');
        }
        $data['ebook_id'] = $ebook_id;
        $data['user_id'] = $user_id;
        $data['payment_method'] = 'paypal';
        $data['payment_keys'] = json_encode(array('transaction_id' => $transaction_id,'session_id' => $session_id));

        if (get_user_role('role_id', $ebook_details['user_id']) == 1) {
            $data['admin_revenue'] = $data['paid_amount'];
            $data['instructor_revenue'] = 0;
            $data['instructor_payment_status'] = 1;
        } else {
            if (get_settings('allow_instructor') == 1) {
                $instructor_revenue_percentage = get_settings('instructor_revenue');
                $data['instructor_revenue'] = ceil(($data['paid_amount'] * $instructor_revenue_percentage) / 100);
                $data['admin_revenue'] = $data['paid_amount'] - $data['instructor_revenue'];
            } else {
                $data['instructor_revenue'] = 0;
                $data['admin_revenue'] = $data['paid_amount'];
            }
            $data['instructor_payment_status'] = 0;
        }

        $data['added_date'] =  time();
        $payment = $this->db->get_where('ebook_payment', array('ebook_id' => $ebook_id, 'user_id' => $user_id));
        if($payment->num_rows() <= 0){
            $this->db->insert('ebook_payment', $data);
        }
      
    }




    public function get_revenue_by_user_type($timestamp_start = "", $timestamp_end = "", $revenue_type = "")
    {
        $ebook_ids = array();
        $ebooks    = array();
        
        $admin_details = $this->user_model->get_admin_details()->row_array();
        if ($revenue_type == 'instructor_revenue') {
            if($this->session->userdata('admin_login')){
                $this->db->where('user_id !=', $admin_details['id']);
            }else{
                $this->db->where('user_id', $this->session->userdata('user_id'));
            }
            $this->db->select('ebook_id');
            $ebooks = $this->db->get('ebook')->result_array();
            foreach ($ebooks as $ebook) {
                if (!in_array($ebook['ebook_id'], $ebook_ids)) {
                    array_push($ebook_ids, $ebook['ebook_id']);
                }
            }
            if (sizeof($ebook_ids)) {
                $this->db->where_in('ebook_id', $ebook_ids);
            } else {
                return array();
            }
        }
        $this->db->where('added_date >=', $timestamp_start);
        $this->db->where('added_date <=', $timestamp_end);
        $this->db->order_by('added_date', 'desc');
        return $this->db->get('ebook_payment')->result_array();
    }


    public function razorpayPrepareData($payable_amount = "")
    {
        $user_details = $this->user_model->get_user($this->session->userdata('user_id'))->row_array();
        $razorpay_settings = json_decode(get_settings('razorpay_keys'));

        $key_id = $razorpay_settings[0]->key;
        $secret_key = $razorpay_settings[0]->secret_key;



    
      $api = new Api($key_id, $secret_key);

      $razorpayOrder = $api->order->create(array(
        'receipt'         => rand(),
        'amount'          => $payable_amount * 100, // 2000 rupees in paise
        'currency'        => get_settings('razorpay_currency'),
        'payment_capture' => 1 // auto capture
      ));
      $amount = $razorpayOrder['amount'];
      $razorpayOrderId = $razorpayOrder['id'];
      $_SESSION['razorpay_order_id'] = $razorpayOrderId;

    $data = array(
      "key" => $key_id,
      "amount" => $amount,
      "name" => get_settings('system_title'),
      "description" => get_settings('about_us'),
      "image" => base_url('uploads/system/'.get_settings('favicon')),
      "prefill" => array(
      "name"  => $user_details['first_name'].' '.$user_details['last_name'],
      "email"  => $user_details['email'],
    ),
      "notes"  => array(
      "merchant_order_id" => rand(),
    ),
      "theme"  => array(
      "color"  => $razorpay_settings[0]->theme_color
    ),
      "order_id" => $razorpayOrderId,
    );
    return $data;
  }

  public function razorpay_payment($razorpayOrderId = "", $payment_id = "", $amount = "", $signature = "")
  {
    $razorpay_keys = json_decode(get_settings('razorpay_keys'));
    $success = true;
    $error = "payment_failed";

    if (empty($payment_id) === false) {
      $api = new Api($razorpay_keys[0]->key, $razorpay_keys[0]->secret_key);
      try {
        $attributes = array(
          'razorpay_order_id' => $razorpayOrderId,
          'razorpay_payment_id' => $payment_id,
          'razorpay_signature' => $signature
        );
        $api->utility->verifyPaymentSignature($attributes);
      } catch(SignatureVerificationError $e) {
        $success = false;
        $error = 'Razorpay_Error : ' . $e->getMessage();
      }
    }
    if ($success === true) {
      return true;
    }else {
      return false;
    }
  }

    function payment_history_by_user_id($user_id = ""){
        $this->db->where('user_id', $user_id);
        return $this->db->get('ebook_payment');
    }
    
}