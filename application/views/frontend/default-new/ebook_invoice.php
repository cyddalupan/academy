<!---------- Bread Crumb Area Start ---------->
<?php include "breadcrumb.php"; ?>
<!---------- Bread Crumb Area End ---------->
<?php $student_details = $this->user_model->get_all_user($payment['user_id'])->row_array(); ?>
<?php $instructor_details = $this->user_model->get_all_user($ebook['user_id'])->row_array(); ?>

<style>
    .invioce-logo img{
        height: 100% !important;
    }
</style>

<!------------ Invoice section start ----->
<section class="invoice">
    <div class="container">
        <div class="print-content">
            <div class="invoice-heading">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                        <h3><?php echo strtoupper(site_phrase('invoice')); ?></h3>
                        <div class="invoice-no">
                            <h6 class="invoice-color"><?php echo get_phrase('Invoice No :')?></h6>
                            <h6> #<?php echo $payment['added_date']; ?></h6>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                        <div class="invioce-logo">
                            <a href="javascript:void(0)"><img loading="lazy" src="<?php echo base_url('uploads/system/'.get_frontend_settings('dark_logo'));?>" alt=""></a> 
                        </div>
                    </div>
                </div>
            </div>
            <div class="invoice-bill">
                <div class="row">
                    <div class="col-lg-6 col-md-6 col-sm-7 col-8">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <p><?php echo get_phrase('Billed To') ?>:</p>
                                <h6><?php echo $student_details['first_name'].' '.$student_details['last_name']; ?></h6>
                                <h6><?php echo $student_details['email']; ?></h6>
                                <h6><?php echo $student_details['address']; ?></h6>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <p><?php echo get_phrase('Date Of Issue') ?>:</p>
                                <h6><?php echo date('d-M-Y', $payment['added_date']) ?></h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-5 col-4">
                        <div class="invoice-total text-end">
                            <p><?php echo get_phrase('Invoice Total') ?></p>
                            <h2><?php echo currency($payment['paid_amount']); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="invoice-dec">
                <div class="invoice-bill--scroll-bar">
                    <table class="table">
                        <thead class="invoice-2-table-head">
                        <tr>
                            <th class="pe-5" scope="col"><h6><?php echo get_phrase('Ebook'); ?></h6></th>
                            <th scope="col"><h6><?php echo get_phrase('Instructor'); ?></h6></th>
                            <th scope="col"><h6 class="text-end"><?php echo get_phrase('QTY') ?></h6></th>
                            <th scope="col"><h6 class="text-end"><?php echo get_phrase('Price') ?></h6></th>
                        </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row"><p><?php echo $ebook['title']; ?></p></th>
                                <td><p ><?php echo $instructor_details['first_name'].' '.$instructor_details['last_name']; ?></p></td>
                                <td><p class="text-end">1</p></td>
                                <td><p class="text-end"><?php echo currency($payment['admin_revenue'] + $payment['instructor_revenue']) ?></p></td>
                            </tr>
                        </tbody>
                    </table> 
                </div>
                <div class="print-btn print-d-none">
                    <a href="javascript:window.print()"><i class="fa-solid fa-print"></i><?php echo get_phrase('Print'); ?></a>
                </div>
            </div>
        </div>
    </div>
</section>
<!------------ Invoice secton end -------->

