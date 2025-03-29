<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i> <?php echo $page_title; ?>
            </h4>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
              <h4 class="mb-3 header-title"><?php echo get_phrase('offline_payments'); ?></h4>
              <div class="table-responsive-sm mt-4">
                <table id="basic-datatable" class="table table-striped table-centered mb-0">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th><?php echo get_phrase('user'); ?></th>
                      <th><?php echo get_phrase('price'); ?></th>
                      <th><?php echo get_phrase('purchased item'); ?></th>
                      <th><?php echo get_phrase('payment_document'); ?></th>
                      <th><?php echo get_phrase('status'); ?></th>
                      <th><?php echo get_phrase('actions'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                      <?php
                       foreach ($offline_payments as $key => $offline_payment): ?>
                        <tr>
                            <td><?php echo $key+1; ?></td>
                            <td>
                              <?php $user_data = $this->user_model->get_user($offline_payment['user_id'])->row_array();?>
                              <b><?php echo $user_data['first_name']." ".$user_data['last_name']; ?></b>
                              <p><small><?php echo $user_data['email']; ?></small></p>
                            </td>
                            <td>
                              <span class="badge badge-dark-lighten badge-pill"><?php echo currency($offline_payment['amount']); ?></span>
                            </td>
                            <td>
                              <h5><?php echo get_phrase($offline_payment['item_type']); ?>: </h5>
                              
                              <?php if($offline_payment['item_info'] != ""): ?>
                                <?php foreach(json_decode($offline_payment['item_info']) as $item): ?>
                                    <p><i class="mdi mdi-arrow-right"></i> <?php echo $item->title; ?></p>
                                <?php endforeach; ?>
                              <?php endif; ?>
                              <p><small><?php echo date('d M Y', $offline_payment['timestamp']) ?></small></p>
                            </td>
                            <td>
                              <a href="<?php echo base_url('uploads/payment_document/'.$offline_payment['document_image']); ?>" class="btn btn-outline-info" download><i class="mdi mdi-download"></i><?php echo get_phrase('payment_document_file'); ?></a>
                              <a>
                            </td>
                            <td>
                              <?php if($offline_payment['status'] == 0): ?>
                                <span class="badge badge-danger-lighten"><?php echo get_phrase('pending') ?></span>
                              <?php elseif($offline_payment['status'] == 1): ?>
                                <span class="badge badge-success-lighten"><?php echo get_phrase('approved') ?></span>
                              <?php elseif($offline_payment['status'] == 2): ?>
                                <span class="badge badge-dark-lighten"><?php echo get_phrase('suspended') ?></span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <div class="dropright dropright">
                                  <button type="button" class="btn btn-sm btn-outline-primary btn-rounded btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                      <i class="mdi mdi-dots-vertical"></i>
                                  </button>
                                  <ul class="dropdown-menu">
                                      <li><a class="dropdown-item" href="#" onclick="confirm_modal('<?php echo site_url('addons/offline_payment/approve/delete/'.$offline_payment['id']); ?>');"><?php echo get_phrase('delete'); ?></a></li>
                                  </ul>
                              </div>
                            </td>
                        </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
              </div>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>




<script type="text/javascript">
  function view_payment_document(image_url){
    $('#payment_document_modal').modal('show');
    $('#payment_document_image_view').html("<img src='"+image_url+"' alt='contact-img' title='<?php echo get_phrase('payment_document'); ?>'' class='rounded w-100 text-center'>");
  }
  function show_loader_modal(){
    $("#loader_modal").show();
  }
</script>

<div id="loader_modal" class="loader-modal">
  <p class="p-0 m-0"><?php echo get_phrase('please_wait');?>....</p>
  <p class="p-0 m-0"><?php echo get_phrase('enrolling_the_student_and_sending_mail'); ?>.....</p>
</div>

<!--  Modal content for the above example -->
<div class="modal fade" id="payment_document_modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div id="payment_document_image_view" class="w-100 text-center justify-content-center"></div>
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
