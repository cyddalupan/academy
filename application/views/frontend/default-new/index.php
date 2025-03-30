<?php
$language_dir = 'ltr';
$language_dirs = get_settings('language_dirs');
if($language_dirs){
	$current_language = $this->session->userdata('language');
	$language_dirs_arr = json_decode($language_dirs, true);
	if(array_key_exists($current_language, $language_dirs_arr)){
		$language_dir = $language_dirs_arr[$current_language];
	}
}

?>
<!DOCTYPE html>
<html lang="<?php echo getIsoCode('english'); ?>" dir="<?php echo $language_dir; ?>">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5.0, minimum-scale=0.86">

	<?php include 'seo.php'; ?>

	

	<link rel="icon" href="<?php echo base_url('uploads/system/'.get_frontend_settings('favicon')); ?>" type="image/x-icon">
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo base_url('uploads/system/'.get_frontend_settings('favicon')); ?>">

	<?php include 'includes_top.php';?>

	<style type="text/css">
		<?php echo get_frontend_settings('custom_css'); ?>
	</style>

</head>
<body class="<?php echo $this->session->userdata('theme_mode'); ?>">
	<script>
	localStorage.removeItem('course_id');
	var userId = <?php echo json_encode($this->session->userdata('user_id')); ?>;
	if (userId) {
		localStorage.setItem('user_id', userId);
	} else {
		console.warn('User ID not found in session.');
		localStorage.removeItem('user_id');
	}
	</script>
	<?php
	//user wishlist items
    $my_wishlist_items = array();
    if($user_id = $this->session->userdata('user_id')){
        $wishlist = $this->user_model->get_all_user($user_id)->row('wishlist');
        if($wishlist != ''){
            $my_wishlist_items = json_decode($wishlist, true);
        }
    }
    
	if($this->session->userdata('app_url')):
		include "go_back_to_mobile_app.php";
	endif;
	
	include 'header.php';

	if(get_frontend_settings('cookie_status') == 'active'):
    	include 'eu-cookie.php';
  	endif;
  	
  	if($page_name === null){
  		include $path;
  	}else{
		include $page_name.'.php';
	}
	include 'footer.php';
	include 'includes_bottom.php';
	include 'modal.php';
	include 'common_scripts.php';
	include 'init.php';
	?>

	<?php echo get_frontend_settings('embed_code'); ?>
</body>
</html>
