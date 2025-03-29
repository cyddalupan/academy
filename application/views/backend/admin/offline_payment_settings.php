<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i> <?php echo $page_title; ?></h4>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<div class="row">
	<div class="col-lg-7">
		<div class="card">
			<div class="card-body">
				<form action="<?php echo site_url('addons/offline_payment/settings/save'); ?>" method="post">
					<div class="form-group">
	                    <label for="bank_information"><?php echo get_phrase('enter_your_bank_information'); ?></label>
	                    <textarea name="bank_information" id = "bank_information" class="form-control" rows="5"><?php echo htmlspecialchars_decode(get_settings('offline_bank_information')); ?></textarea>
	                </div>

	                <div class="form-group">
	                	<button class="btn btn-primary"><?php echo get_phrase('Submit'); ?></button>
	                </div>
				</form>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
  $(document).ready(function () {
    initSummerNote(['#bank_information']);
  });
</script>