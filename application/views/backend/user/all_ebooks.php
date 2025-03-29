<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body py-2">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i> <?php echo get_phrase('ebooks'); ?>
                    <a href="<?php echo site_url('ebook_manager/add_ebook'); ?>" class="btn btn-outline-primary btn-rounded alignToTitle"><i class="mdi mdi-plus"></i><?php echo get_phrase('add_new_ebook'); ?></a>
                </h4>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('ebooks'); ?></h4>
                <div class="table-responsive-sm mt-4">
                    <table id="basic-datatable" class="table table-striped dt-responsive nowrap dataTable no-footer dtr-inline collapsed">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo get_phrase('creator'); ?></th>
                                <th><?php echo get_phrase('title'); ?></th>
                                <th><?php echo get_phrase('category'); ?></th>
                                <th><?php echo get_phrase('status'); ?></th>
                                <th><?php echo get_phrase('actions'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($ebooks as $key => $ebook) : ?>
                            	<?php $user_details = $this->user_model->get_all_user($ebook['user_id'])->row_array(); ?>
                                <tr>
                                    <td><?php echo $key + 1; ?></td>
                                    <td>
                                    	<a href="<?php echo site_url('home/instructor_page/'.$ebook['user_id']); ?>" target="_blank">
	                                    	<div class="d-flex">
	                                    		<div>
	                                        		<img src="<?php echo $this->user_model->get_user_image_url($user_details['id']); ?>" alt="" height="50" width="50" class="img-fluid rounded-circle img-thumbnail">
	                                        	</div>
		                                        <div class="pl-1 pt-1">
			                                    	<?php echo $user_details['first_name'] . ' ' . $user_details['last_name']; ?>
			                                    	<p><?php echo $user_details['email']; ?></p>
			                                    </div>
			                                </div>
			                            </a>
                                    </td>
                                    <td>
                                        <a href="<?php echo site_url('ebook/ebook_details/'.rawurlencode(slugify($ebook['title'])).'/'.$ebook['ebook_id']) ?>" target="_blank"><?php echo $ebook['title']; ?></a><br>
                                        <small class="text-muted"><?php echo date('d M Y', $ebook['added_date']); ?></small>
                                    </td>
                                    <td><?php echo $this->ebook_model->get_ebook_categories($ebook['category_id'])->row('title'); ?></td>
                                    <td>
                                        <?php if($ebook['is_active'] == 1): ?>
                                        	<span class="badge badge-success"><?php echo get_phrase('active'); ?></span>
                                        <?php else: ?>
                                        	<span class="badge badge-danger"><?php echo get_phrase('pending'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="dropright dropright">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-rounded btn-icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="mdi mdi-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="<?php echo site_url('addons/ebook_manager/edit_ebook/' . $ebook['ebook_id']); ?>"><?php echo get_phrase('edit'); ?></a></li>
                                                <li><a class="dropdown-item" href="#" onclick="confirm_modal('<?php echo site_url('addons/ebook_manager/ebook/delete/' . $ebook['ebook_id']); ?>');"><?php echo get_phrase('delete'); ?></a></li>
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