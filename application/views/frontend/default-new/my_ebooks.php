<?php include "breadcrumb.php"; ?>


<section class="user-dashboard-area pt-3">
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-lg-3">
                <?php include "profile_menus.php"; ?>
            </div>
            <div class="col-md-8 col-lg-9 mt-4 mt-md-0">
                <div class="my-course-1-full-body">
                    <h1><?php echo get_phrase('Ebooks'); ?></h1>
                    <div class="row">
                    <?php foreach ($my_ebooks->result_array() as $key => $ebook) :
                        $ebook_payment_history = $this->db->get_where('ebook_payment', array('user_id' => $this->session->userdata('user_id'), 'ebook_id' => $ebook['ebook_id']))->row_array();
                        $instructor_details = $this->user_model->get_all_user($ebook['user_id'])->row_array(); ?>
                        <div class="col-lg-4 col-sm-6">
                            <div class="ebook-item-one">
                                <div class="img"><img loading="lazy" src="<?php echo $this->ebook_model->get_ebook_thumbnail_url($ebook['ebook_id']); ?>" alt="" width="100%"/></div>
                                <div class="content">
                                    <h4 class="title"><?php echo $ebook['title'] ?></a></h4>
                                    <div class="content d-flex">
                                        <a href="<?php echo site_url('ebook/ebook_details/'.rawurlencode(slugify($ebook['title'])).'/'.$ebook['ebook_id']) ?>" class="link w-100"><?php echo get_phrase('View Details')?></a>
                                        <a href="<?php echo site_url('addons/ebook/ebook_invoice/'.$ebook_payment_history['payment_id']); ?>" class="ms-1 link btn-outline" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo get_phrase('Invoice');?>"><i class="fas fa-file-invoice"></i></a>
                                    </div>
                                </div>
                                <div class="status free">
                                    <p>
                                        <?php if($ebook['is_free'] == 1){
                                            echo get_phrase('Free');
                                        }else{
                                            echo currency($ebook['price']);
                                        }?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;?>
                    </div>
                </div>                
            </div>
        </div>
    </div>
</section>