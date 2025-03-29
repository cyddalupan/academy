<?php
isset($layout) ? "" : $layout = "list";
isset($selected_category_id) ? "" : $selected_category_id = "all";
isset($selected_rating) ? "" : $selected_rating = "all";
isset($selected_price) ? "" : $selected_price = "all";
$number_of_visible_categories = 10;

?>

<!---------- Bread Crumb Area Start ---------->
<?php include "breadcrumb.php"; ?>
<!---------- Bread Crumb Area End ---------->

<!-- Start Tutor list -->
<section class="pt-50 pb-120">
    <div class="container">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 col-md-4">
            <div class="s_Sidebar_one">
                <div class="content">
                <div class="pb-30">
                    <h4 class="mb-20 s_Sidebar_title_one s_bar"><?php echo get_phrase('Categories')?></h4>
                    <div class="d-flex justify-content-between align-items-center pb-12">
                    <div class="s_Sidebar_checkbox_one">
                        <input class="form-check-input" type="radio" value="all" name="sub_category" id="allcategory" onclick="filter(this)" <?php if ($selected_category_id == 'all') echo 'checked'; ?> />
                        <label class="form-check-label" for="allcategory"><?php echo get_phrase('All category')?></label>
                    </div>
                    <span class="no">(<?php echo $total_active_ebooks; ?>)</span>
                    </div>
                    <?php
                    $counter = 1;
                    $total_number_of_categories = $this->db->get('ebook_category')->num_rows();
                    $categories = $this->ebook_model->get_categories()->result_array();
                    foreach ($categories as $category) : ?>
                    <div class="pb-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="s_Sidebar_checkbox_one">
                                <input class="form-check-input categories" name="sub_category" type="radio" value="<?php echo $category['slug'];?>" id="category-<?php echo $category['category_id']; ?>" onclick="filter(this)" <?php if ($selected_category_id == $category['category_id']) echo 'checked'; ?>/>
                                <label class="form-check-label" for="category-<?php echo $category['category_id']; ?>"><?php echo $category['title']; ?></label>
                            </div>
                            <span class="no">(<?php echo $this->ebook_model->get_active_addon_by_category_id($category['category_id'], 'category_id')->num_rows(); ?>)</span>
                        </div>
                    </div>
                    <?php endforeach;?>
                    
                    <a href="javascript:;" class="text-13px fw-500" id="city-toggle-btn" onclick="showToggle(this, 'hidden-categories')"><?php echo $total_number_of_categories > $number_of_visible_categories ? site_phrase('show_more') : ""; ?></a>
                </div>
                <div class="pb-30">
                    <h4 class="mb-20 s_Sidebar_title_one s_bar"><?php echo site_phrase('Price'); ?></h4>
                    <div class="s_Sidebar_checkbox_one pb-12">
                        <input class="form-check-input prices" type="radio" id="price_all" name="price" value="all" onclick="filter(this)" <?php if ($selected_price == 'all') echo 'checked'; ?>/>
                        <label class="form-check-label" for="price_all"><?php echo site_phrase('all'); ?></label>
                    </div>
                    <div class="s_Sidebar_checkbox_one pb-12">
                        <input class="form-check-input prices" type="radio" id="price_free" name="price" value="free" onclick="filter(this)" <?php if ($selected_price == 'free') echo 'checked'; ?> />
                        <label class="form-check-label" for="price_free"><?php echo site_phrase('free'); ?></label>
                    </div>
                    <div class="s_Sidebar_checkbox_one">
                        <input class="form-check-input prices" id="price_paid" name="price" type="radio" value="paid" onclick="filter(this)" <?php if ($selected_price == 'paid') echo 'checked'; ?>/>
                        <label class="form-check-label" for="price_paid"><?php echo site_phrase('paid'); ?></label>
                    </div>
                </div>
                <div>
                    <h4 class="mb-20 s_Sidebar_title_one s_bar"><?php echo site_phrase('Ratings'); ?></h4>
                    <div class="s_Sidebar_checkbox_one pb-12">
                        <input class="form-check-input ratings" type="radio" id="all_rating" name="rating" value="<?php echo 'all'; ?>" onclick="filter(this)" <?php if ($selected_rating == "all") echo 'checked'; ?>/>
                        <label class="form-check-label" for="rAll"><?php echo site_phrase('All'); ?></label>
                    </div>
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                    <div class="s_Sidebar_checkbox_one pb-12">
                        <input class="form-check-input ratings" id="rating_<?php echo $i; ?>" name="rating" type="radio" value="<?php echo $i; ?>" onclick="filter(this)" <?php if ($selected_rating == $i) echo 'checked'; ?>/>
                        <label class="form-check-label" for="rating_<?php echo $i; ?>">
                            <div class="rating-icon">
                                <?php for ($j = 1; $j <= $i; $j++) : ?>
                                    <img loading="lazy" src="<?php echo base_url('assets/frontend/default-new/image/icon/star-solid.svg')?>" alt="" />
                                <?php endfor; ?>
                                <?php for ($j = $i; $j < 5; $j++) : ?>
                                    <img loading="lazy" src="<?php echo base_url('assets/frontend/default-new/image/icon/star-solid-2.svg')?>" alt="" />
                                <?php endfor; ?>
                            </div>
                        </label>
                    </div>
                    <?php endfor; ?>
                    
                </div>
                </div>
            </div>
        </div>
        <!-- Course list -->
        <div class="col-lg-9 col-md-8">
        <div class="d-flex justify-content-between pb-10">
            <p class="searchResult"><?php echo site_phrase('showing').' '.count($ebooks).' '.site_phrase('of').' '.$total_result.' '.site_phrase('results'); ?></p>
            <div class="s_search">
            <form action="<?php echo site_url('ebook') ?>" method='get'>
                <input type="text" class="form-control" name="search" placeholder="<?php echo get_phrase('Search')?>" value="<?php if(isset($_GET['search'])) echo $_GET['search']; ?>" />
                <span><img loading="lazy" src="<?php echo base_url('assets/frontend/default-new/image/icon/s_search.svg')?>" alt="" /></span>
            </form>
            </div>
        </div>
        <!-- Items -->
        <div class="ebook-items">
            <div class="row">
                <?php foreach($ebooks as $ebook):?>
                    <div class="col-lg-4 col-sm-6">
                        <div class="ebook-item-one">
                            <div class="img"><img loading="lazy" src="<?php echo $this->ebook_model->get_ebook_thumbnail_url($ebook['ebook_id']); ?>" alt="" width="100%"/></div>
                            <div class="content">
                                <h4 class="title"><?php echo $ebook['title'];?></h4>
                                <a href="<?php echo site_url('ebook/ebook_details/'.rawurlencode(slugify($ebook['title'])).'/'.$ebook['ebook_id']) ?>" class="link"><?php echo get_phrase('View Details')?></a>
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
<!-- End Tutor list -->

<script>
    function get_url() {
        var urlPrefix = '<?php echo site_url('ebook?'); ?>'
        var urlSuffix = "";
        var slectedCategory = "";
        var selectedPrice = "";
        var selectedRating = "";
        var search_text = "";

        // Get selected category
        $('.categories:checked').each(function() {
            slectedCategory = $(this).attr('value');
        });

        // Get selected price
        $('.prices:checked').each(function() {
            selectedPrice = $(this).attr('value');
        });
        searchText = $('.search').val();
        // Get selected rating
        $('.ratings:checked').each(function() {
            selectedRating = $(this).attr('value');
        });

        if (searchText != null) {
            urlSuffix = "category=" + slectedCategory + "&&price=" + selectedPrice + "&&rating=" + selectedRating +
                "&&search=" + searchText;
        } else {
            urlSuffix = "category=" + slectedCategory + "&&price=" + selectedPrice + "&&rating=" + selectedRating;
        }
        var url = urlPrefix + urlSuffix;
        return url;
    }
    function filter() {
        var url = get_url();
        window.location.replace(url);
        //console.log(url);
    }
    function showToggle(elem, selector) {
        $('.' + selector).slideToggle(20);
        if ($(elem).text() === "<?php echo site_phrase('show_more'); ?>") {
            $(elem).text('<?php echo site_phrase('show_less'); ?>');
        } else {
            $(elem).text('<?php echo site_phrase('show_more'); ?>');
        }
    }
    $('.course-compare').click(function(e) {
        e.preventDefault()
        var redirect_to = $(this).attr('redirect_to');
        window.location.replace(redirect_to);
    });
    function toggleLayout(layout) {
        $.ajax({
            type: 'POST',
            url: '<?php echo site_url('home/set_layout_to_session'); ?>',
            data: {
                layout: layout
            },
            success: function(response) {
                location.reload();
            }
        });
    }
</script>
