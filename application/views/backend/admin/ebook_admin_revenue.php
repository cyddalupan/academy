<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i> <?php echo get_phrase('admin_revenue'); ?></h4>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3 header-title"><?php echo get_phrase('admin_revenue'); ?></h4>
                <div class="row justify-content-md-center">
                    <div class="col-xl-6">
                        <form class="form-inline" action="<?php echo site_url('addons/ebook_manager/payment_history/admin_revenue/filter_by_date_range') ?>" method="get">
                            <div class="col-xl-10">
                                <div class="form-group">
                                    <div id="reportrange" class="form-control" data-toggle="date-picker-range" data-target-display="#selectedValue" data-cancel-class="btn-light" style="width: 100%;">
                                        <i class="mdi mdi-calendar"></i>&nbsp;
                                        <span id="selectedValue"><?php echo date("F d, Y", $timestamp_start) . " - " . date("F d, Y", $timestamp_end); ?></span> <i class="mdi mdi-menu-down"></i>
                                    </div>
                                    <input id="date_range" type="hidden" name="date_range" value="<?php echo date("d F, Y", $timestamp_start) . " - " . date("d F, Y", $timestamp_end); ?>">
                                </div>
                            </div>
                            <div class="col-xl-2">
                                <button type="submit" class="btn btn-info" id="submit-button" onclick="update_date_range();"> <?php echo get_phrase('filter'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="table-responsive-sm mt-4">
                    <table id="basic-datatable" class="table table-striped table-centered mb-0">
                        <thead>
                            <tr>
                                <th><?php echo get_phrase('ebook'); ?></th>
                                <th><?php echo get_phrase('total_amount'); ?></th>
                                <th><?php echo get_phrase('admin_revenue'); ?></th>
                                <th><?php echo get_phrase('purchase_date'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_history as $payment) :
                                $user_data = $this->db->get_where('users', array('id' => $payment['user_id']))->row_array();
                                $ebook_details = $this->db->get_where('ebook', array('ebook_id' => $payment['ebook_id']))->row_array(); ?>
                                <tr class="gradeU">
                                    <td>
                                        <a href="<?php echo site_url('ebook/ebook_details/'.rawurlencode(slugify($ebook_details['title'])).'/'.$ebook_details['ebook_id']) ?>" target="_blank"><?php echo $ebook_details['title']; ?></a><br>
                                        <small class="text-muted"><?php echo date('d M Y', $ebook_details['added_date']); ?></small>
                                    </td>
                                    <td><?php echo currency($payment['paid_amount']); ?></td>
                                    <td><?php echo currency($payment['admin_revenue']); ?></td>
                                    <td><?php echo date('D, d-M-Y', $payment['added_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function update_date_range() {
        var x = $("#selectedValue").html();
        $("#date_range").val(x);
    }
</script>