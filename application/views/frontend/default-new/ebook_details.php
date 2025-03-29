<?php
$ebook_details = $this->ebook_model->get_ebook_by_id($ebook_id)->row_array();
$instructor_details = $this->user_model->get_all_user($ebook_details['user_id'])->row_array();
$category_details = $this->ebook_model->get_categories($ebook_details['category_id'])->row_array();
$path = base_url('uploads/ebook/file/ebook_preview/'.$ebook_details['preview']);
$totoalPages = countPages($path);

if(file_exists('uploads/ebook/thumbnails/'.$ebook_details['thumbnail']) && $ebook_details['thumbnail'] != ''){
    $ebook_image = base_url('uploads/ebook/thumbnails/'.$ebook_details['thumbnail']);
}else{
    //showing placeholder image .png
    $ebook_image = base_url('uploads/ebook/thumbnails/placeholder.png');
}

function countPages($path) {
    $pdftext = file_get_contents($path);
    $num = preg_match_all("/\/Page\W/", $pdftext, $dummy);
    return $num;
}

$user_id = $this->session->userdata('user_id');
                              
?>
<style>
    .ebook-content table tr th{
        width: 30%;
        background: #F1F2F4;
        padding: 10px;
        color: #000;
        border: 0.5px solid #dddddd;
    }
    .ebook-content table tr td{
        width: 70%;
        padding: 10px;
        border: 0.5px solid #dddddd;
    }
</style>
<!---------- Bread Crumb Area Start ---------->
<?php include "breadcrumb.php"; ?>
<!---------- Bread Crumb Area End ---------->

<!-- Start Ebook Details -->
<section class="pt-100 pb-80">
    <div class="container">
    <div class="row align-items-center">
        <div class="col-lg-4">
            <div class="ebook-img"><img loading="lazy" src="<?php echo $ebook_image; ?>" alt="" width="100%"/></div>
        </div>
        <div class="col-lg-8">
            <div class="ebook-content">
                <h4 class="s_Sidebar_title_one s_bar mb-20"><?php echo $ebook_details['title']; ?></h4>
                <!-- <p class="info"><?php echo htmlspecialchars_decode(substr_replace($ebook_details['description'], "...", 300)); ?></p> -->
                <p class="info"><i><?php echo get_phrase('created_by') ?></i>
                    <a class="text-14px fw-600 text-decoration-none"
                        href="<?php echo site_url('home/instructor_page/' . $ebook_details['user_id']); ?>"><?php echo $instructor_details['first_name'] . ' ' . $instructor_details['last_name']; ?></a>

                </p>
                <p class="info"><?php echo get_phrase('publication_name') ?>
                    <span><?php echo $ebook_details['publication_name'] ?></span>
                </p>
                <p class="info"><?php echo get_phrase('published_date') ?> : <span><?php echo  date('D, d-M-Y', $ebook_details['added_date']); ?></span>
                </p>
                <p class="info"><?php echo get_phrase('category_name') ?> : <span><?php echo $category_details['title'] ?></span></p>
            </div>
            <div class="ebook-content mt-4">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ebookModal"><?php echo get_phrase('read_preview') ?></button>
                <?php if($ebook_details['is_free']): ?>
                    <a href="<?php echo base_url('addons/ebook/view/'.$ebook_details['ebook_id']) ?>" class="btn btn-primary" type="button"><?php echo site_phrase('View'); ?></a>
                <?php else: ?>
                    <?php if($this->db->get_where('ebook_payment', array('user_id' => $this->session->userdata('user_id'), 'ebook_id' => $ebook_details['ebook_id']))->num_rows() > 0): ?>
                        <a href="<?php echo base_url('addons/ebook/view/'.$ebook_details['ebook_id']) ?>" class="btn btn-primary"
                        type="button"><?php echo site_phrase('View'); ?></a>
                    <?php else: ?>
                        <a href="<?php echo base_url('ebook/buy/'.$ebook_details['ebook_id']) ?>" class="btn btn-primary" type="button"><?php echo site_phrase('buy_now'); ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <!-- Modal -->
        <div class="modal fade" id="ebookModal" tabindex="-1" aria-labelledby="ebookModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="ebook-modal d-grid justify-content-center">
                        <?php if(!empty($ebook_details['preview'])): ?>
                        <object
                            data="<?php echo base_url('uploads/ebook/file/ebook_preview/'.$ebook_details['preview'].'#toolbar=0') ?>"
                            height="500px" width="800px"></object>
                        <?php else: ?>
                            <div class="w-100 text-center py-5 my-5">
                                <img loading="lazy" width="200px" class="" src="<?php echo site_url('assets/global/image/no-preview-available.png'); ?>">
                            </div>
                        <?php endif ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
    </div>
</section>
<!-- End Ebook Details -->

<!-- Start Ebook Tabs -->
<section class="pb-80">
    <div class="container">
    <ul class="nav nav-tabs sNav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
        <button class="nav-link active" id="forum-tab" data-bs-toggle="tab" data-bs-target="#forum-tab-pane" type="button" role="tab" aria-controls="forum-tab-pane" aria-selected="true"><?php echo get_phrase('Summary')?></button>
        </li>
        <li class="nav-item" role="presentation">
        <button class="nav-link" id="noticeboard-tab" data-bs-toggle="tab" data-bs-target="#noticeboard-tab-pane" type="button" role="tab" aria-controls="noticeboard-tab-pane" aria-selected="false"><?php echo get_phrase('Specification')?></button>
        </li>
        <li class="nav-item" role="presentation">
        <button class="nav-link" id="assignment-tab" data-bs-toggle="tab" data-bs-target="#assignment-tab-pane" type="button" role="tab" aria-controls="assignment-tab-pane" aria-selected="false"><?php echo get_phrase('Review')?></button>
        </li>
    </ul>
    <div class="tab-content sTab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="forum-tab-pane" role="tabpanel" aria-labelledby="forum-tab" tabindex="0">
            <div class="ebook-content">
                <h4 class="s_Sidebar_title_one mb-20"><?php echo $ebook_details['title'] ?></h4>
                <p class="info"><?php echo htmlspecialchars_decode($ebook_details['description']) ?></p>
            </div>
        </div>
        <div class="tab-pane fade" id="noticeboard-tab-pane" role="tabpanel" aria-labelledby="noticeboard-tab" tabindex="0">
            <div class="ebook-content">
                <table style="width:100%">
                    <tr>
                        <th style="width: 30%"><?php echo get_phrase('Title');?></th>
                        <td style="width:70%"><?php echo $ebook_details['title'] ?></td>
                    </tr>
                    <tr>
                        <th><?php echo get_phrase('Author');?></td>
                        <td colspan="2">
                            <?php echo $instructor_details['first_name']." ".$instructor_details['last_name'] ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php echo get_phrase('Publisher');?></td>
                        <td><?php echo $ebook_details['publication_name'] ?></td>
                    </tr>
                    <tr>
                        <th><?php echo get_phrase('Edition');?></td>
                        <td><?php echo $ebook_details['edition'] ?></td>
                    </tr>
                    <tr>
                        <th><?php echo get_phrase('No. of page');?></td>
                        <td><?php echo $ebook_details['no_of_pages']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="tab-pane fade" id="assignment-tab-pane" role="tabpanel" aria-labelledby="assignment-tab" tabindex="0">
        <div class="ebook-content">
            <?php if($user_id > 0):?>
        <h4 class="s_Sidebar_title_one mb-20"><?php echo get_phrase('Write a Review') ?></h4>
            <div class="row">
                <div class="col-sm-6">
                    <form action="<?php echo site_url('addons/ebook/ebook_rating/'.$ebook_details['ebook_id'].'/save_rating'); ?>" method="post">
                        <input type="hidden" name="course_id" value="">
                        <select class="form-control mb-3" name="rating">
                            <option value="1"><?php echo get_phrase('1 Start Rating') ?></option>
                            <option value="2"><?php echo get_phrase('2 Start Rating') ?></option>
                            <option value="3"><?php echo get_phrase('3 Start Rating') ?></option>
                            <option value="4"><?php echo get_phrase('4 Start Rating') ?></option>
                            <option value="5"><?php echo get_phrase('5 Start Rating') ?></option>
                        </select>
                        <textarea class="form-control text-section" name="comment" placeholder="<?php echo get_phrase('Write your comment') ?>" ></textarea>
                        <div class="msg mt-3">
                            <button type="submit" class="btn btn-primary"><?php echo get_phrase('Submit'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif;?>
        </div>
        </div>
    </div>
    </div>
</section>
<!-- End Ebook Tabs -->

<!-- Start Related Ebook -->
<section class="pb-80">
    <div class="container">
    <h4 class="s_title_one pb-50"><?php echo get_phrase('Other Related Ebooks')?></h4>
    <div class="row rg-24">
        <?php $this->db->limit(4);
        $other_related_ebooks = $this->ebook_model->get_ebooks($ebook_details['category_id'])->result_array();
        foreach ($other_related_ebooks as $other_related_ebook) : 

            if(file_exists('uploads/ebook/thumbnails/'.$other_related_ebook['thumbnail']) && $other_related_ebook['thumbnail'] != ''){
                $thumbnail = base_url('uploads/ebook/thumbnails/'.$other_related_ebook['thumbnail']);
            }else{
                //showing placeholder image .png
                $thumbnail = base_url('uploads/ebook/thumbnails/placeholder.png');
            }
            
        ?>
        
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="ebook-item-one">
                <div class="img"><img loading="lazy" src="<?php echo $thumbnail; ?>" alt="" width="100%"/></div>
                <div class="content">
                <h4 class="title"><?php echo $other_related_ebook['title'] ?></h4>
                <a href="<?php echo site_url('ebook/ebook_details/'.rawurlencode(slugify($other_related_ebook['title'])).'/'.$other_related_ebook['ebook_id']) ?>" class="link"><?php echo get_phrase('View Details')?></a>
                </div>
                <div class="status free">
                    <p>
                        <?php if($ebook_details['is_free'] == 1){
                            echo get_phrase('Free');
                        }else{
                            echo currency($ebook_details['price']);
                        }?>
                    </p>
                </div>
            </div>
        </div>
        <?php endforeach?>
    </div>
    </div>
</section>
<!-- End Related Ebook -->

<!-- Start Instructor -->
<section class="pb-120">
    <div class="container">
    <h4 class="s_title_one pb-50"><?php echo get_phrase('About Instructor')?></h4>
    <div class="row justify-content-between">
        <div class="col-lg-9">
        <div class="s_ebook_instructor">
            <div class="img"><img loading="lazy" src="<?php echo $this->user_model->get_user_image_url($ebook_details['user_id']) ?>" alt="" /></div>
            <div class="content">
            <h5 class="name"><?php echo $instructor_details['first_name']." ".$instructor_details['last_name'] ?></h5>
            <!-- <p class="subtitle">Agile Project Expert</p> -->
            <p class="info"><?php echo $instructor_details['biography'] ?></p>
            <ul class="instructor_social">
                <?php $social_link = json_decode($instructor_details['social_links']);?>
                <li>
                <a href="<?php echo $social_link->facebook;?>"><i class="fa-brands fa-facebook-f"></i></a>
                </li>
                <li>
                <a href="<?php echo $social_link->twitter;?>"><i class="fa-brands fa-twitter"></i></a>
                </li>
                <li>
                <a href="<?php echo $social_link->linkedin?>"><i class="fa-brands fa-linkedin"></i></a>
                </li>
                <li>
                <!-- <a href="#"><i class="fa-brands fa-behance"></i></a> -->
                </li>
            </ul>
            </div>
        </div>
        </div>
        <div class="col-lg-2">
            <?php 
                $number_of_ratings = $this->ebook_model->get_ratings($other_related_ebook['ebook_id'])->num_rows();
                $total_rating =  $this->ebook_model->get_ratings($other_related_ebook['ebook_id'], true)->row()->rating;
            ?>
            <div class="s_review text-lg-end">
                <h4 class="title"><?php echo get_phrase('Ebook review')?></h4>
                <p class="date"></p>
                <p class="rating-no"><?php 
                    if ($number_of_ratings > 0) {
                        $average_ceil_rating = ceil($total_rating / $number_of_ratings);
                        echo $average_ceil_rating;
                    } else {
                        $average_ceil_rating = 0;
                        echo $average_ceil_rating;
                    }
                ?></p>
                <div class="rating-icon justify-content-lg-end">
                    <?php for ($i = 1; $i < 6; $i++) : ?>
                        <?php if ($i <= $average_ceil_rating) : ?>
                            <img loading="lazy" src="<?php echo base_url('assets/frontend/default-new/image/icon/star-solid.svg')?>" alt="" />
                        <?php else : ?>
                            <img loading="lazy" src="<?php echo base_url('assets/frontend/default-new/image/icon/star-solid-2.svg')?>" alt="" />
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
    </div>
</section>
<!-- End Instructor -->

<script>
    function handleCartItems(elem) {
        url1 = '<?php echo site_url('home/handleCartItems'); ?>';
        url2 = '<?php echo site_url('home/refreshWishList'); ?>';
        $.ajax({
            url: url1,
            type: 'POST',
            data: {
                course_id: elem.id
            },
            success: function(response) {
                $('#cart_items').html(response);
                if ($(elem).hasClass('active')) {
                    $(elem).removeClass('active')
                    $(elem).text("<?php echo site_phrase('add_to_cart'); ?>");
                } else {
                    $(elem).addClass('active');
                    $(elem).addClass('active');
                    $(elem).text("<?php echo site_phrase('added_to_cart'); ?>");
                }
                $.ajax({
                    url: url2,
                    type: 'POST',
                    success: function(response) {
                        $('#wishlist_items').html(response);
                    }
                });
            }
        });
    }

    function handleBuyNow(elem) {

        url1 = '<?php echo site_url('home/handleCartItemForBuyNowButton'); ?>';
        url2 = '<?php echo site_url('home/refreshWishList'); ?>';
        urlToRedirect = '<?php echo site_url('home/shopping_cart'); ?>';
        var explodedArray = elem.id.split("_");
        var course_id = explodedArray[1];

        $.ajax({
            url: url1,
            type: 'POST',
            data: {
                course_id: course_id
            },
            success: function(response) {
                $('#cart_items').html(response);
                $.ajax({
                    url: url2,
                    type: 'POST',
                    success: function(response) {
                        $('#wishlist_items').html(response);
                        toastr.success('<?php echo site_phrase('please_wait') . '....'; ?>');
                        setTimeout(
                            function() {
                                window.location.replace(urlToRedirect);
                            }, 1000);
                    }
                });
            }
        });
    }

    function handleEnrolledButton() {
        $.ajax({
            url: '<?php echo site_url('home/isLoggedIn?url_history='.base64_encode(current_url())); ?>',
            success: function(response) {
                if (!response) {
                    window.location.replace("<?php echo site_url('login'); ?>");
                }
            }
        });
    }

    function handleAddToWishlist(elem) {
        $.ajax({
            url: '<?php echo site_url('home/isLoggedIn?url_history='.base64_encode(current_url())); ?>',
            success: function(response) {
                if (!response) {
                    window.location.replace("<?php echo site_url('login'); ?>");
                } else {
                    $.ajax({
                        url: '<?php echo site_url('home/handleWishList'); ?>',
                        type: 'POST',
                        data: {
                            course_id: elem.id
                        },
                        success: function(response) {
                            if ($(elem).hasClass('active')) {
                                $(elem).removeClass('active');
                                $(elem).text("<?php echo site_phrase('add_to_wishlist'); ?>");
                            } else {
                                $(elem).addClass('active');
                                $(elem).text("<?php echo site_phrase('added_to_wishlist'); ?>");
                            }
                            $('#wishlist_items').html(response);
                        }
                    });
                }
            }
        });
    }

    function pausePreview() {
        player.pause();
    }

    $('.course-compare').click(function(e) {
        e.preventDefault()
        var redirect_to = $(this).attr('redirect_to');
        window.location.replace(redirect_to);
    });

    function go_course_playing_page(course_id, lesson_id) {
        var course_playing_url = "<?php echo site_url('home/lesson/'.slugify($ebook_details['title'])); ?>/" + course_id +
            '/' + lesson_id;

        $.ajax({
            url: '<?php echo site_url('home/go_course_playing_page/'); ?>' + course_id,
            type: 'POST',
            success: function(response) {
                if (response == 1) {
                    window.location.replace(course_playing_url);
                }
            }
        });
    }
</script>