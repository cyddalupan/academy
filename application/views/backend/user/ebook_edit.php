<?php 
$ebook_banner = $this->ebook_model->get_ebook_banner_url($ebook['ebook_id']);
$ebook_thumbnail = $this->ebook_model->get_ebook_thumbnail_url($ebook['ebook_id']); 
?>

<div class="row ">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <h4 class="page-title"> <i class="mdi mdi-apple-keyboard-command title_icon"></i>
                    <?php echo get_phrase('edit_ebook'); ?></h4>
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">

                <div class="row">
                    <div class="col-md-6">
                        <h4 class="header-title my-1"><?php echo get_phrase('ebook_editing_form'); ?></h4>
                    </div>
                    <div class="col-md-6">
                        <a href="<?php echo site_url('addons/ebook_manager/ebook'); ?>"
                            class="alignToTitle btn btn-outline-secondary btn-rounded btn-sm my-1"> <i
                                class=" mdi mdi-keyboard-backspace"></i>
                            <?php echo get_phrase('back_to_ebook_list'); ?></a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <form class="required-form"
                            action="<?php echo site_url('addons/ebook_manager/ebook/update/'. $ebook['ebook_id']); ?>"
                            method="post" enctype="multipart/form-data">
                            <div id="basicwizard">

                                <ul class="nav nav-pills nav-justified form-wizard-header">
                                    <li class="nav-item">
                                        <a href="#basic" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                            <i class="mdi mdi-fountain-pen-tip"></i>
                                            <span class="d-none d-sm-inline"><?php echo get_phrase('basic'); ?></span>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="#pricing" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                            <i class="mdi mdi-currency-cny"></i>
                                            <span class="d-none d-sm-inline"><?php echo get_phrase('pricing'); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#media" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                            <i class="mdi mdi-library-video"></i>
                                            <span
                                                class="d-none d-sm-inline"><?php echo get_phrase('ebook_files'); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#seo" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                            <i class="mdi mdi-tag-multiple"></i>
                                            <span class=""><?php echo get_phrase('seo'); ?></span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#finish" data-toggle="tab" class="nav-link rounded-0 pt-2 pb-2">
                                            <i class="mdi mdi-checkbox-marked-circle-outline"></i>
                                            <span class="d-none d-sm-inline"><?php echo get_phrase('finish'); ?></span>
                                        </a>
                                    </li>

                                </ul>

                                <div class="tab-content b-0 mb-0">
                                    <div class="tab-pane mt-4" id="basic">
                                        <div class="row justify-content-center">
                                            <div class="col-md-10">

                                                </h4>

                                                <div class="form-group">
                                                    <label for="title"><?php echo get_phrase('title'); ?></label>
                                                    <input type="text" class="form-control" name="title" id="title"
                                                        value="<?php echo $ebook['title'] ?>"
                                                        placeholder="<?php echo get_phrase('enter_ebook_title'); ?>"
                                                        required>
                                                </div>

                                                <div class="form-group">
                                                    <label
                                                        for="category_id"><?php echo get_phrase('category'); ?></label>
                                                    <select class="form-control select2" data-toggle="select2"
                                                        name="category_id" id="category_id" required>
                                                        <option value="">
                                                            <?php echo get_phrase('select_a_category'); ?>
                                                        </option>
                                                        <?php foreach($this->ebook_model->get_ebook_categories()->result_array() as $category): ?>
                                                        <option value="<?php echo $category['category_id']; ?>"
                                                            <?php if($category['category_id'] == $ebook['category_id']){ echo 'selected';} ?>>
                                                            <?php echo $category['title'] ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="form-group row mb-3">
                                                    <label class="col-md-3 col-form-label"
                                                        for="description"><?php echo get_phrase('description'); ?></label>
                                                    <div class="col-md-12">
                                                        <textarea name="description" id="description"
                                                            class="form-control"><?php echo $ebook['description']; ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label
                                                        for="publication_name"><?php echo get_phrase('publication_name'); ?></label>
                                                    <input type="text" class="form-control" name="publication_name"
                                                        id="publication_name"
                                                        value="<?php echo $ebook['publication_name'] ?>"
                                                        placeholder="<?php echo get_phrase('enter_publication_name'); ?>"
                                                        required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="edition"><?php echo get_phrase('edition'); ?></label>
                                                    <input type="text" class="form-control" name="edition" id="edition"
                                                        value="<?php echo $ebook['edition'] ?>"
                                                        placeholder="<?php echo get_phrase('enter_edition'); ?>"
                                                        required>
                                                </div>

                                                <!-- <div class="form-group mb-3">
                                                    <label
                                                        for="banner"><?php echo get_phrase('ebook_banner'); ?></label>
                                                    <div class="wrapper-image-preview" style="margin-left: -6px;">
                                                        <div class="box" style="width: 300px;">
                                                            <div class="js--image-preview"
                                                                style="background-image: url('<?php echo $ebook_banner ?>'); background-color: #F5F5F5; background-size: cover; background-position: center;">
                                                            </div>
                                                            <div class="upload-options">
                                                                <label for="banner" class="btn"> <i
                                                                        class="mdi mdi-camera"></i>
                                                                    <?php echo get_phrase('choose_a_banner'); ?>
                                                                    <br> <small>(2000 x 500)</small>
                                                                </label>
                                                                <input id="banner" style="visibility:hidden;"
                                                                    type="file" class="image-upload" name="banner"
                                                                    accept="image/*">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div> -->

                                                <div class="form-group mb-3">
                                                    <label
                                                        for="thumbnail"><?php echo get_phrase('ebook_thumbnail'); ?></label>
                                                    <div class="wrapper-image-preview" style="margin-left: -6px;">
                                                        <div class="box" style="width: 300px;">
                                                            <div class="js--image-preview"
                                                                style="background-image: url('<?php echo $ebook_thumbnail ?>'); background-color: #F5F5F5; background-size: cover; background-position: center;">
                                                            </div>
                                                            <div class="upload-options">
                                                                <label for="thumbnail" class="btn"> <i
                                                                        class="mdi mdi-camera"></i>
                                                                    <?php echo get_phrase('choose_a_thumbnail'); ?>
                                                                    <br> <small>(500 x 700)</small>
                                                                </label>
                                                                <input id="thumbnail" style="visibility:hidden;"
                                                                    type="file" class="image-upload" name="thumbnail"
                                                                    accept="image/*">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>





                                            </div>
                                        </div>
                                    </div> <!-- end tab pane -->
                                    <div class="tab-pane mt-4" id="pricing">
                                        <div class="row justify-content-center">
                                            <div class="col-xl-8">
                                                <div class="form-group row mb-3">
                                                    <div class="offset-md-2 col-md-10">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input"
                                                                name="is_free" id="is_free" value="1"
                                                                <?php if($ebook['is_free']){echo 'checked';} ?>
                                                                onclick="togglePriceFields(this.id)">
                                                            <label class="custom-control-label"
                                                                for="is_free"><?php echo get_phrase('check_if_this_is_a_free_ebook'); ?></label>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="paid-course-stuffs">
                                                    <div class="form-group row mb-3">
                                                        <label class="col-md-2 col-form-label"
                                                            for="price"><?php echo get_phrase('ebook_price').' ('.currency_code_and_symbol().')'; ?></label>
                                                        <div class="col-md-10">
                                                            <input type="number" class="form-control" id="price"
                                                                name="price" value="<?php echo $ebook['price'] ?>"
                                                                placeholder="<?php echo get_phrase('enter_ebook_price'); ?>"
                                                                min="1">
                                                            <span
                                                                class="text-danger error-message d-hidden"><?php echo get_phrase('minimum_required_value_1'); ?></span>
                                                        </div>
                                                    </div>

                                                    <div class="form-group row mb-3">
                                                        <div class="offset-md-2 col-md-10">
                                                            <div class="custom-control custom-checkbox">
                                                                <input type="checkbox" class="custom-control-input"
                                                                    name="discount_flag" id="discount_flag" value="1"
                                                                    <?php if($ebook['discount_flag']){ echo "checked";} ?>>
                                                                <label class="custom-control-label"
                                                                    for="discount_flag"><?php echo get_phrase('check_if_this_ebook_has_discount'); ?></label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group row mb-3">
                                                        <label class="col-md-2 col-form-label"
                                                            for="discounted_price"><?php echo get_phrase('discounted_price').' ('.currency_code_and_symbol().')'; ?></label>
                                                        <div class="col-md-10">
                                                            <input type="number" class="form-control"
                                                                name="discounted_price" id="discounted_price"
                                                                onkeyup="calculateDiscountPercentage(this.value)"
                                                                value="<?php echo $ebook['discounted_price'] ?>"
                                                                min="0">
                                                            <small
                                                                class="text-muted"><?php echo get_phrase('this_ebook_has'); ?>
                                                                <span id="discounted_percentage"
                                                                    class="text-danger">0%</span>
                                                                <?php echo get_phrase('discount'); ?></small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div> <!-- end col -->
                                        </div> <!-- end row -->
                                    </div> <!-- end tab-pane -->
                                    <div class="tab-pane mt-4" id="media">
                                        <div class="row justify-content-center">



                                            <div class="col-xl-8">
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label"
                                                        for="course_overview_url"><?php echo get_phrase('ebook_preview_file'); ?></label>
                                                    <div class="col-md-10">
                                                        <input type="file" class="form-control" value="hi"
                                                            name="ebook_preview_file" id="ebook_preview_file">

                                                    </div>

                                                    <label class="col-md-2 col-form-label"
                                                        for="ebook_complete_file"><?php echo get_phrase('ebook_complete_file'); ?></label>
                                                    <div class="col-md-10">
                                                        <input type="file" class="form-control"
                                                            value="<?php echo $ebook['file']; ?>"
                                                            name="ebook_complete_file" id="ebook_complete_file">

                                                    </div>

                                                    <label class="col-md-2 col-form-label"
                                                        for="no_of_pages"><?php echo get_phrase('no_of_pages'); ?></label>
                                                    <div class="col-md-10">
                                                        <input type="number" class="form-control" id="no_of_pages"
                                                            name="no_of_pages" value="<?php echo $ebook['no_of_pages'] ?>"
                                                            placeholder="<?php echo get_phrase('enter_total_ebook_page_number'); ?>"
                                                            min="1">
                                                    </div>
                                                </div>
                                            </div> <!-- end col -->
                                            <!-- this portion will be generated theme wise from the theme-config.json file Starts-->
                                            <?php //include 'course_media_add.php'; ?>
                                            <!-- this portion will be generated theme wise from the theme-config.json file Ends-->

                                        </div> <!-- end row -->
                                    </div>
                                    <div class="tab-pane mt-4" id="seo">
                                        <div class="row justify-content-center">
                                            <div class="col-xl-8">
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="meta_title"><?php echo get_phrase('meta_title'); ?></label>
                                                    <div class="col-md-10">
                                                        <input type="text" class="form-control" name="meta_title" id="meta_title"
                                                        value="<?php echo $ebook['meta_title'] ?>"
                                                        placeholder="<?php echo get_phrase('enter_ebook_meta_title'); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="meta_description"><?php echo get_phrase('meta_description'); ?></label>
                                                    <div class="col-md-10">
                                                        <textarea name="meta_description" class="form-control"><?php echo $ebook['meta_description']; ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="website_keywords"><?php echo get_phrase('meta_keywords'); ?></label>
                                                    <div class="col-md-10">
                                                        <input type="text" class="form-control bootstrap-tag-input" id="meta_keywords" name="meta_keywords" data-role="tagsinput" style="width: 100%;" value="<?php echo $ebook['meta_keywords']; ?>" placeholder="<?php echo get_phrase('write_a_keyword_and_then_press_enter_button'); ?>" . />
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="og_title"><?php echo get_phrase('og_title'); ?></label>
                                                    <div class="col-md-10">
                                                        <input type="text" class="form-control" name="og_title" id="og_title"
                                                        value="<?php echo $ebook['og_title'] ?>"
                                                        placeholder="<?php echo get_phrase('enter_ebook_og_title'); ?>">
                                                    </div>
                                                </div>
                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="og_description"><?php echo get_phrase('og_description'); ?></label>
                                                    <div class="col-md-10">
                                                        <textarea name="og_description" class="form-control"><?php echo $ebook['og_description']; ?></textarea>
                                                    </div>
                                                </div>

                                                <div class="form-group row mb-3">
                                                    <label class="col-md-2 col-form-label" for="json_ld"><?php echo get_phrase('Json Id'); ?></label>
                                                    <div class="col-md-10">
                                                        <textarea class="form-control" id="json_ld" name="json_ld" rows="5"><?php echo $ebook['json_ld']; ?></textarea>
                                                    </div>
                                                </div>

                                            </div> <!-- end col -->
                                        </div> <!-- end row -->
                                    </div>
                                    <div class="tab-pane mt-4" id="finish">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="text-center">
                                                    <h2 class="mt-0"><i class="mdi mdi-check-all"></i></h2>
                                                    <h3 class="mt-0"><?php echo get_phrase("thank_you"); ?> !</h3>

                                                    <p class="w-75 mb-2 mx-auto">
                                                        <?php echo get_phrase('you_are_just_one_click_away'); ?></p>

                                                    <div class="mb-3 mt-3">
                                                        <button type="button" class="btn btn-primary text-center"
                                                            onclick="checkRequiredFields()"><?php echo get_phrase('submit'); ?></button>
                                                    </div>
                                                </div>
                                            </div> <!-- end col -->
                                        </div> <!-- end row -->
                                    </div>

                                    <ul class="list-inline mb-0 wizard text-center">
                                        <li class="previous list-inline-item">
                                            <a href="javascript::" class="btn btn-info"> <i
                                                    class="mdi mdi-arrow-left-bold"></i> </a>
                                        </li>
                                        <li class="next list-inline-item">
                                            <a href="javascript::" class="btn btn-info"> <i
                                                    class="mdi mdi-arrow-right-bold"></i> </a>
                                        </li>
                                    </ul>



                                </div> <!-- tab-content -->
                            </div> <!-- end #progressbarwizard-->
                        </form>
                    </div>
                </div><!-- end row-->
            </div> <!-- end card-body-->
        </div> <!-- end card-->
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    initSummerNote(['#description']);
});
</script>

<script type="text/javascript">
var blank_outcome = jQuery('#blank_outcome_field').html();
var blank_requirement = jQuery('#blank_requirement_field').html();
jQuery(document).ready(function() {
    jQuery('#blank_outcome_field').hide();
    jQuery('#blank_requirement_field').hide();
});

function appendOutcome() {
    jQuery('#outcomes_area').append(blank_outcome);
}

function removeOutcome(outcomeElem) {
    jQuery(outcomeElem).parent().parent().remove();
}

function appendRequirement() {
    jQuery('#requirement_area').append(blank_requirement);
}

function removeRequirement(requirementElem) {
    jQuery(requirementElem).parent().parent().remove();
}

function priceChecked(elem) {
    if (jQuery('#discountCheckbox').is(':checked')) {

        jQuery('#discountCheckbox').prop("checked", false);
    } else {

        jQuery('#discountCheckbox').prop("checked", true);
    }
}

function topCourseChecked(elem) {
    if (jQuery('#isTopCourseCheckbox').is(':checked')) {

        jQuery('#isTopCourseCheckbox').prop("checked", false);
    } else {

        jQuery('#isTopCourseCheckbox').prop("checked", true);
    }
}

function isFreeCourseChecked(elem) {

    if (jQuery('#' + elem.id).is(':checked')) {
        $('#price').prop('required', false);
    } else {
        $('#price').prop('required', true);
    }
}
$('document').ready(function() {
    $('input[min]').keyup(function() {
        var val = $(this).val();
        var requiredVal = $(this).attr('min');
        console.log(val, requiredVal);
        if (Number(val) >= Number(requiredVal)) {
            $(this).parent().children('.error-message').hide();
        } else {
            $(this).parent().children('.error-message').show();
        }
    });
});

function calculateDiscountPercentage(discounted_price) {
    if (discounted_price > 0) {
        var actualPrice = jQuery('#price').val();
        if (actualPrice > 0) {
            var reducedPrice = actualPrice - discounted_price;
            var discountedPercentage = (reducedPrice / actualPrice) * 100;
            if (discountedPercentage > 0) {
                jQuery('#discounted_percentage').text(discountedPercentage.toFixed(2) + '%');

            } else {
                jQuery('#discounted_percentage').text('<?php echo '0%'; ?>');
            }
        }
    }
}
</script>

<style media="screen">
body {
    overflow-x: hidden;
}
</style>