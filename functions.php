<?php
// Add Shortcode
function ajax_category_pills_shortcode() {
    ob_start();
    ?>
    <div id="category-pills">
        <div class="category-pills-container">
            <button class="pill active" data-category="all">Alle</button>
            <?php
            $categories = get_categories();
            foreach ($categories as $category) {
                echo '<button class="pill" data-category="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</button>';
            }
            ?>
        </div>
        <div id="category-dropdown-container" style="display: none;">
            <select id="category-dropdown">
                <option value="all">Alle</option>
                <?php
                foreach ($categories as $category) {
                    echo '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
                }
                ?>
            </select>
        </div>
        <div id="posts-container"></div>
        <div id="pagination-container"></div>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            function loadPosts(category = 'all', page = 1) {
                const container = $('#posts-container');
                const pagination = $('#pagination-container');
                container.html('Loading...');
                pagination.html('');

                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    method: 'POST',
                    data: {
                        action: 'load_posts_by_category',
                        category: category,
                        page: page
                    },
                    success: function (response) {
                        const parsed = JSON.parse(response);
                        container.html(parsed.posts);
                        pagination.html(parsed.pagination);
                    }
                });
            }

            // Toggle between pills and dropdown based on screen size
            function toggleCategorySelector() {
                if ($(window).width() < 768) {
                    $('.category-pills-container').hide();
                    $('#category-dropdown-container').show();
                } else {
                    $('.category-pills-container').show();
                    $('#category-dropdown-container').hide();
                }
            }

            $(window).on('resize', toggleCategorySelector);
            toggleCategorySelector();

            // Event listener for category pills
            $('.category-pills-container').on('click', '.pill', function () {
                $('.pill').removeClass('active');
                $(this).addClass('active');
                loadPosts($(this).data('category'));
            });

            // Event listener for dropdown menu
            $('#category-dropdown').on('change', function () {
                loadPosts($(this).val());
            });

            // Event delegation for dynamically generated pagination links
            $('#pagination-container').on('click', '.page-number', function (e) {
                e.preventDefault();
                const category = $(window).width() < 768 ? $('#category-dropdown').val() : $('.pill.active').data('category');
                const page = $(this).data('page');
                loadPosts(category, page);
            });

            // Initial load
            loadPosts();
        });
    </script>

    <style>
        #category-pills {
            text-align: center;
        }
        .category-pills-container {
            margin-bottom: 20px;
        }
        .pill {
            display: inline-block;
            margin: 5px;
            padding: 10px 20px;
            background-color: #4a7728;
            border: 1px solid #4a7728;
            cursor: pointer;
			color:#fff
        }
        .pill.active {
            background-color: #3D5567;
            color: #fff;
        }
        #category-dropdown-container {
            margin-bottom: 20px;
        }
        #category-dropdown {
            padding: 10px;
            font-size: 16px;
            width: 100%;
            max-width: 300px;
        }
        .post {
            margin: 15px;           
            text-align: left;
        }
        .post img {
            width: 100%;
            height: auto;
        }
        .post-meta {
            font-size: 14px;
            color: #888;
            margin-bottom: 10px;
        }
        .post-title {
            color: #000;
			font-family: Raleway, sans-serif;
			font-size: 18px;
			line-height: 1.55556em;
			font-weight: 500;
			letter-spacing: .15em;
			text-transform: uppercase;
			margin: 25px 0;
			-ms-word-wrap: break-word;
			word-wrap: break-word;
        }
        .post-excerpt {
			font-size: 16px;
            color: #333;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
		.page-number{font-size:16px;margin:0px 10px}
        @media (min-width: 768px) {
            #posts-container {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
            }
            .post {
                flex: 1 1 calc(33.333% - 30px);
                box-sizing: border-box;
            }
			
        }
        @media (max-width: 767px) {
			.page-number{font-size:22px;margin:0px 10px}
            .post {
                flex: 1 1 100%;
            }
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('category_pills', 'ajax_category_pills_shortcode');

// AJAX Handler
function load_posts_by_category() {
    $category = $_POST['category'] ?? 'all';
    $paged = $_POST['page'] ?? 1;
    $args = [
        'post_type' => 'post',
        'posts_per_page' => 12,
        'paged' => $paged,
		'status' => 'publish',
		'category__not_in' => [1], // Exclude "Uncategorized" (ID = 1 by default)
    ];

    if ($category !== 'all') {
        $args['category_name'] = sanitize_text_field($category);
    }

    $query = new WP_Query($args);

    $posts_html = '';
    while ($query->have_posts()) {
        $query->the_post();
        $categories = get_the_category();
        $category_links = [];
        foreach ($categories as $cat) {
            $category_links[] = esc_html($cat->name);
        }

        $posts_html .= '<div class="post">';
		$posts_html .= '<a href="'.get_the_permalink().'">';
        $posts_html .= get_the_post_thumbnail(get_the_ID(), 'qi_addons_for_elementor_image_size_square');
        $posts_html .= '<div class="post-meta">' . get_the_date('d. F Y') . ' / ' . implode(', ', $category_links) . '</div>';
        $posts_html .= '<div class="post-title">' . get_the_title() . '</div>';
        $posts_html .= '<div class="post-excerpt">' . get_the_excerpt() . '</div>';
		$posts_html .= '</a>';
        $posts_html .= '</div>';
    }
    wp_reset_postdata();

    $pagination_html = paginate_links([
        'total' => $query->max_num_pages,
        'current' => $paged,
        'type' => 'array',
    ]);

    $pagination_links = '';
    if ($pagination_html) {
        foreach ($pagination_html as $page_link) {
            // Ensure there is no nested anchor tag issue
            $clean_page_link = preg_replace('/<a[^>]*>(.*?)<\/a>/', '$1', $page_link);

            // Extract page number from link
            preg_match('/page\/([0-9]+)/', $page_link, $matches);
            $page_number = $matches[1] ?? 1;

            $pagination_links .= '<a href="#" class="page-number" data-page="' . esc_attr($page_number) . '">' . $clean_page_link . '</a>';
        }
    }

    echo json_encode(["posts" => $posts_html, "pagination" => $pagination_links]);
    wp_die();
}
add_action('wp_ajax_load_posts_by_category', 'load_posts_by_category');
add_action('wp_ajax_nopriv_load_posts_by_category', 'load_posts_by_category');
