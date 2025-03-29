<form action="<?php echo $payment_details['success_url'].'/offline_payment'; ?>" class="gateway <?php echo $payment_gateway['identifier']; ?>-gateway" method="post" enctype="multipart/form-data">

	<div class="offline_payment_instruction mb-4">
		<?php echo htmlspecialchars_decode(get_settings('offline_bank_information')); ?>
	</div>

	<label for="amount"><?php echo get_phrase('payable_amount'); ?></label>
	<input type="number" id="amount" class="form-control" name="amount" value="<?php echo $payment_details['total_payable_amount']; ?>" readonly>
	<label class="mt-4" for="payment_document"><?php echo get_phrase('document_of_your_payment'); ?> (jpg, pdf, txt, png, docx)</label>
	<input type="file" class="form-control" id="payment_document" name="payment_document" required>
	<button type="submit" class="payment-button float-right mt-4"><?php echo get_phrase('submit_payment_document'); ?></button>
</form>
