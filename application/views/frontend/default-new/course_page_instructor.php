

<div class="instructor">
    <?php $multi_instructor_id_arr = explode(',',$course_details['user_id']); ?>
    <?php foreach($multi_instructor_id_arr as $instructor_id): ?>
        <?php if($instructor_id > 0): ?>
            <?php $instructor = $this->user_model->get_all_user($instructor_id)->row_array(); ?>
            <div class="row g-3">
                <div class="col-lg-3 col-md-4 col-sm-4 col-4">
                    <div class="instructor-img">
                        <img loading="lazy" src="<?php echo $this->user_model->get_user_image_url($instructor['id']); ?>">
                    </div>
                </div>
                <div class="col-lg-9 col-md-8 col-sm-8 col-8">
                    <div class="instructor-text">
                        <h2 class="text-black ms-0 fw-600"><?php echo $instructor['first_name'].' '.$instructor['last_name']; ?></h2>
                        <p class="ms-0 text-15px font-inter-light ellipsis-line-2"><?php echo $instructor['title']; ?></p>
                        <div class="ellipsis-line-2 font-inter-light"><?php echo ($instructor['biography']) ? strip_tags($instructor['biography']):''; ?></div>
                    </div>
                    <div class="instructor-icon mt-3">
                        <?php foreach(json_decode($instructor['social_links'], true) as $key => $social_link): ?>
                            <?php if(!$social_link) continue; ?>
                            <a href="<?php echo $social_link; ?>">
                                <?php if($key == 'facebook'): ?>
                                    <i class="fa-brands fa-facebook-f" data-bs-toggle="tooltip" title="<?php echo get_phrase('Facebook'); ?>"></i>
                                <?php elseif($key == 'twitter'): ?>
                                    <i class="fa-brands fa-twitter" data-bs-toggle="tooltip" title="<?php echo get_phrase('Twitter'); ?>"></i>
                                <?php elseif($key == 'linkedin'): ?>
                                        <i class="fa-brands fa-linkedin" data-bs-toggle="tooltip" title="<?php echo get_phrase('Linkedin'); ?>"></i></a>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                        <a class="btn btn-primary py-2 btn-sm" href="<?php echo site_url('home/instructor_page/'.$instructor_id) ?>" target="_blank"><?php echo get_phrase('View Profile'); ?></a>
                        <?php 
                        $is_following = $this->user_model->is_following($instructor_id, $this->session->userdata('user_id')); 
                        $user_id = $this->session->userdata('user_id');
                        $user_role = $this->session->userdata('role');
                        ?>

                        <?php if ($user_role != 1 && $user_id != $instructor_id): ?>
                           <!-- Updated HTML with class instead of ID -->
                                <a id="follow-btn-<?php echo $instructor['id']; ?>" href="javascript:;" onclick="toggleFollow(<?php echo $instructor['id']; ?>, this)">
                                    <span class="follow-btn btn <?php echo ($is_following) ? 'btn-fill' : 'btn-primary'; ?>"><?php echo ($is_following) ? get_phrase('Unfollow') : get_phrase('Follow'); ?></span>
                                </a>
                        <?php endif; ?>

                                                
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>


<script>
   $(document).on('click', '.follow-btn', function() {
    let isFollowing = $(this).hasClass('btn-fill');
    // Toggle background color class
    $(this).toggleClass('btn-primary btn-fill');

    // Toggle the text between "Follow" and "Unfollow"
    if (isFollowing) {
        $(this).text("<?php echo get_phrase('Follow'); ?>");
    } else {
        $(this).text("<?php echo get_phrase('Unfollow'); ?>");
    }
});

function toggleFollow(instructor_id, element) {
    var url = "<?php echo site_url('home/toggle_following'); ?>";
    $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json', 
        data: {
            instructor_id: instructor_id,
            user_id: <?php echo $this->session->userdata('user_id'); ?>
        },
        success: function(response) {
            var btn = $(element).find('span');
            if (response.status === 'followed') {
                btn.text('<?php echo get_phrase('Unfollow'); ?>');
                btn.removeClass('btn-primary');
                btn.addClass('btn-fill');
            } else if (response.status === 'unfollowed') {
                btn.text('<?php echo get_phrase('Follow'); ?>');
                btn.removeClass('btn-fill');
                btn.addClass('btn-primary');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error: ' + error);
        }
    });
}
</script>