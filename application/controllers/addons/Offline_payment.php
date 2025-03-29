<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Offline_payment extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set(get_settings('timezone'));
		
		// Your own constructor code
		$this->load->database();
		$this->load->library('session');
		$this->load->model('addons/offline_payment_model');
		// $this->load->library('stripe');
		/*cache control*/

		if (!$this->session->userdata('cart_items')) {
			$this->session->set_userdata('cart_items', array());
		}
	}

	public function pending($param1 = "", $id = "", $user_id = "", $amount_paid = "")
	{
		if ($this->session->userdata('admin_login') != true) {
			redirect(site_url('login'), 'refresh');
		}

		if ($param1 == 'approve'){
			$offline_payment = $this->offline_payment_model->offline_payment_all_data($id)->row_array();
			$item_ids = json_decode($offline_payment['item_id']);

			if($offline_payment['course_referee'] != ''){
				$this->session->set_userdata('course_referee', $offline_payment['course_referee']);
				$this->session->set_userdata('course_reffer_id', $offline_payment['referred_item_id']);
			}

			if($offline_payment['item_type'] == 'course_bundle'){
				foreach ($item_ids as $key => $item_id) {
					$bundle_details = $this->db->where('id', $item_id)->get('course_bundle')->row_array();
					$data['user_id'] = $offline_payment['user_id'];
		            $data['bundle_creator_id'] = $bundle_details['user_id'];
		            $data['bundle_id'] = $item_id;
		            $data['payment_method'] = 'offline';
		            $data['amount'] = $offline_payment['amount'];
		            $data['date_added'] = strtotime(date('d M Y H:i:s'));
		            $this->db->insert('bundle_payment', $data);
				}
			}elseif($offline_payment['item_type'] == 'ebook'){
				$this->load->model('addons/ebook_model');
				foreach ($item_ids as $key => $item_id) {
		            $this->ebook_model->ebook_purchase('offline_payment', $item_id, $offline_payment['amount'], '', '', $offline_payment['user_id']);
		        }
			}elseif($offline_payment['item_type'] == 'tutor_booking'){
				$this->load->model('addons/tutor_booking_model');
				foreach ($item_ids as $key => $item_id) {
					$get_schedule_info = $this->tutor_booking_model->get_schedule_info($item_id);
		            $this->tutor_booking_model->complete_schedule_booking($offline_payment['user_id'], $item_id, $get_schedule_info['booking_id'], 'offline', '', $offline_payment['amount']);
		        }
			}elseif($offline_payment['item_type'] == 'course'){
				//add purchase course in cart
				$this->session->set_userdata('cart_items', $item_ids);
				//insert value
				$this->crud_model->enrol_student($offline_payment['user_id']);
				$this->crud_model->course_purchase($offline_payment['user_id'], 'offline', $offline_payment['amount']);
				$this->email_model->course_purchase_notification($offline_payment['user_id'], 'offline', $offline_payment['amount']);
			}elseif($offline_payment['item_type'] == 'team_training'){
				$this->load->model('addons/team_package_model');
				foreach ($item_ids as $key => $item_id) {
		            $this->team_package_model->package_purchase('offline',$item_id, $offline_payment['amount'], '');
	            	$this->team_package_model->enrol_in_package($item_id, $offline_payment['user_id']);
		        }
		    }elseif($offline_payment['item_type'] == 'bootcamp'){
		    	foreach ($item_ids as $key => $item_id) {
			    	$bootcamp_data['user_id'] = $offline_payment['user_id'];
			        $bootcamp_data['bootcamp_id'] = $item_id;
			        $bootcamp_data['price'] = $offline_payment['amount'];
			        $bootcamp_data['payment_method'] = 'offline';
			        $bootcamp_data['request_date'] = time();
			        $bootcamp_data['added_date'] = time();
			        $bootcamp_data['updated_date'] = time();
			        $this->db->insert('bootcamp_purchase', $bootcamp_data);
			    }
			}else{
				$this->session->set_flashdata('error_message', get_phrase('This module is not yet available in the offline payment section'));
				redirect(site_url('addons/offline_payment/pending'), 'refresh');
			}

			$this->offline_payment_model->approve_offline_payment($id);
			$this->session->set_flashdata('flash_message', get_phrase('Pending payments has been approved'));
			redirect(site_url('addons/offline_payment/pending'), 'refresh');
		}elseif ($param1 == 'suspended'){
			$this->offline_payment_model->suspended_offline_payment($id);
			$this->session->set_flashdata('flash_message', get_phrase('data_updated_successfully'));
			redirect(site_url('addons/offline_payment/pending'), 'refresh');
		}elseif ($param1 == 'delete'){
			$this->offline_payment_model->delete_offline_payment($id);
			$this->session->set_flashdata('flash_message', get_phrase('data_deleted_successfully'));
			redirect(site_url('addons/offline_payment/pending'), 'refresh');
		}


		$page_data['page_name'] = 'offline_payment_pending';
		$page_data['offline_payments'] = $this->offline_payment_model->offline_payment_pending()->result_array();
		$page_data['page_title'] = get_phrase('pending_payment_request');
		$this->load->view('backend/index', $page_data);
	}

	public function approve($param1 = "", $id = "", $user_id = "", $amount_paid = "")
	{
		if ($this->session->userdata('admin_login') != true) {
			redirect(site_url('login'), 'refresh');
		}

		if ($param1 == 'delete') :
			$this->offline_payment_model->delete_offline_payment($id);
			$this->session->set_flashdata('flash_message', get_phrase('data_deleted_successfully'));
			redirect(site_url('addons/offline_payment/approve'), 'refresh');
		endif;


		$page_data['page_name'] = 'offline_payment_approve';
		$page_data['offline_payments'] = $this->offline_payment_model->offline_payment_approve()->result_array();
		$page_data['page_title'] = get_phrase('accepted_payment_request');
		$this->load->view('backend/index', $page_data);
	}

	public function suspended($param1 = "", $id = "", $user_id = "", $amount_paid = "")
	{
		if ($this->session->userdata('admin_login') != true) {
			redirect(site_url('login'), 'refresh');
		}

		if ($param1 == 'approve'){
			
			$offline_payment = $this->offline_payment_model->offline_payment_all_data($id)->row_array();
			$item_ids = json_decode($offline_payment['item_id']);

			if($offline_payment['item_type'] == 'course_bundle'){
				foreach ($item_ids as $key => $item_id) {
					$bundle_details = $this->db->where('id', $item_id)->get('course_bundle')->row_array();
					$data['user_id'] = $offline_payment['user_id'];
		            $data['bundle_creator_id'] = $bundle_details['user_id'];
		            $data['bundle_id'] = $item_id;
		            $data['payment_method'] = 'offline';
		            $data['amount'] = $offline_payment['amount'];
		            $data['date_added'] = strtotime(date('d M Y H:i:s'));
		            $this->db->insert('bundle_payment', $data);
				}
			}elseif($offline_payment['item_type'] == 'ebook'){
				$this->load->model('addons/ebook_model');
				foreach ($item_ids as $key => $item_id) {
		            $this->ebook_model->ebook_purchase('offline_payment', $item_id, $offline_payment['amount'], '', '', $offline_payment['user_id']);
		        }
			}elseif($offline_payment['item_type'] == 'tutor_booking'){
				$this->load->model('addons/tutor_booking_model');
				foreach ($item_ids as $key => $item_id) {
					$get_schedule_info = $this->tutor_booking_model->get_schedule_info($item_id);
		            $this->tutor_booking_model->complete_schedule_booking($offline_payment['user_id'], $item_id, $get_schedule_info['booking_id'], 'offline', '', $offline_payment['amount']);
		        }
			}elseif($offline_payment['item_type'] == 'course'){
				//add purchase course in cart
				$this->session->set_userdata('cart_items', $item_ids);
				//insert value
				$this->crud_model->enrol_student($offline_payment['user_id']);
				$this->crud_model->course_purchase($offline_payment['user_id'], 'offline', $offline_payment['amount']);
				$this->email_model->course_purchase_notification($offline_payment['user_id'], 'offline', $offline_payment['amount']);
			}elseif($offline_payment['item_type'] == 'team_training'){
				$this->load->model('addons/team_package_model');
				foreach ($item_ids as $key => $item_id) {
		            $this->team_package_model->package_purchase('offline',$item_id, $offline_payment['amount'], '');
	            	$this->team_package_model->enrol_in_package($item_id, $offline_payment['user_id']);
		        }
		    }elseif($offline_payment['item_type'] == 'bootcamp'){
		    	foreach ($item_ids as $key => $item_id) {
			    	$bootcamp_data['user_id'] = $offline_payment['user_id'];
			        $bootcamp_data['bootcamp_id'] = $item_id;
			        $bootcamp_data['price'] = $offline_payment['amount'];
			        $bootcamp_data['payment_method'] = 'offline';
			        $bootcamp_data['request_date'] = time();
			        $bootcamp_data['added_date'] = time();
			        $bootcamp_data['updated_date'] = time();
			        $this->db->insert('bootcamp_purchase', $bootcamp_data);
			    }
			}else{
				$this->session->set_flashdata('error_message', get_phrase('This module is not yet available in the offline payment section'));
				redirect(site_url('addons/offline_payment/pending'), 'refresh');
			}

			$this->offline_payment_model->approve_offline_payment($id);
			$this->session->set_flashdata('flash_message', get_phrase('Suspended payments has been approved'));
			redirect(site_url('addons/offline_payment/suspended'), 'refresh');
		}elseif ($param1 == 'suspended'){
			$this->offline_payment_model->delete_offline_payment($id);
			$this->session->set_flashdata('flash_message', get_phrase('data_deleted_successfully'));
			redirect(site_url('addons/offline_payment/suspended'), 'refresh');
		}


		$page_data['page_name'] = 'offline_payment_suspended';
		$page_data['offline_payments'] = $this->offline_payment_model->offline_payment_suspended()->result_array();
		$page_data['page_title'] = get_phrase('suspended_payment_request');
		$this->load->view('backend/index', $page_data);
	}

	//Offline checkout
	public function attach_payment_document($payment_request_mobile = "")
	{
		if ($this->session->userdata('user_login') != true) {
			redirect(site_url('login'), 'refresh');
		}

		$status = "error";
		$course_id = $this->session->userdata('cart_items');
		$file_extension = pathinfo($_FILES['payment_document']['name'], PATHINFO_EXTENSION);
		if ($file_extension == 'jpg' || $file_extension == 'pdf' || $file_extension == 'txt' || $file_extension == 'png' || $file_extension == 'docx') :
			if ($this->session->userdata('total_price_of_checking_out') > 0) :
				$this->offline_payment_model->attach_payment_document($file_extension);
				$this->session->set_flashdata('flash_message', get_phrase('your_document_will_be_reviewd'));
				$status = "pending";
			else :
				$this->session->set_flashdata('error_message', get_phrase('session_timed_out') . ' ! ' . get_phrase('please_try_again'));
			endif;
		else :
			$this->session->set_flashdata('error_message', get_phrase('this_type_of_file_does_not_have_permissions') . '. ' . get_phrase('there_are_permissions_for') . ' jpg, pdf, txt, png, docx ' . get_phrase('extension'));
			redirect(site_url('home/shopping_cart'), 'refresh');
		endif;

		if ($payment_request_mobile) {
			$user_id = $this->session->userdata('user_id');
			redirect('home/payment_success_mobile/' . $course_id[0] . '/' . $user_id . '/' . $status, 'refresh');
		} else {
			redirect(site_url('home/purchase_history'), 'refresh');
		}
	}


	public function settings($param1 = ""){
		if($param1 != ""){
			$this->offline_payment_model->settings();
			redirect(site_url('addons/offline_payment/settings'), 'refresh');
		}
		$page_data['page_name'] = 'offline_payment_settings';
		$page_data['page_title'] = get_phrase('offline_payment_settings');
		$this->load->view('backend/index', $page_data);
	}
}
