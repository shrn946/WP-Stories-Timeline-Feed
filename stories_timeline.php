<?php
/*
Plugin Name: WP Stories Timeline Feed
Description: A WordPress plugin for displaying 3D stories timeline feed.[stories_timeline_feed] [stories_timeline_feed number_of_stories="5" order="DESC" orderby="date"]
Version: 1.0
Author: Hassn Naqvi
*/
// Enqueue styles
function stories_timeline_feed_styles() {
    // Font Awesome
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.2.0/css/font-awesome.min.css');

    // Custom stylesheet
    wp_enqueue_style('stories-timeline-feed-style', plugin_dir_url(__FILE__) . 'style.css');
}

// Hook styles to the wp_enqueue_scripts action
add_action('wp_enqueue_scripts', 'stories_timeline_feed_styles');

// Register custom post type 'Stories'
function register_stories_post_type() {
    $labels = array(
        'name'               => _x('Stories', 'post type general name'),
        'singular_name'      => _x('Story', 'post type singular name'),
        'add_new'            => _x('Add New', 'story'),
        'add_new_item'       => __('Add New Story'),
        'edit_item'          => __('Edit Story'),
        'new_item'           => __('New Story'),
        'all_items'          => __('All Stories'),
        'view_item'          => __('View Story'),
        'search_items'       => __('Search Stories'),
        'not_found'          => __('No stories found'),
        'not_found_in_trash' => __('No stories found in the Trash'),
        'parent_item_colon'  => '',
        'menu_name'          => __('Stories'),
    );
    $args = array(
        'labels'        => $labels,
        'public'        => true,
        'menu_position' => 7,
        'supports'      => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'has_archive'   => true,
        'rewrite'       => array('slug' => 'stories'),
        'publicly_queryable' => false, // Disable the "View" option
    );
    register_post_type('stories', $args);
}

add_action('init', 'register_stories_post_type');

// Add custom field for 'Story Date'
function add_story_date_meta_box() {
    add_meta_box(
        'story_date_meta_box',
        __('Story Date'),
        'story_date_callback',
        'stories',
        'normal',
        'default'
    );
}

add_action('add_meta_boxes', 'add_story_date_meta_box');

// Callback function to display custom field
function story_date_callback($post) {
    // Get existing value of the 'story_date' custom field
    $date = get_post_meta($post->ID, 'story_date', true);
    ?>
    <label for="story_date"><?php _e('Story Date:'); ?></label>
    <input type="text" id="story_date" name="story_date" value="<?php echo esc_attr($date); ?>">
    <?php
}

// Save custom field data
function save_story_date_meta($post_id) {
    if (array_key_exists('story_date', $_POST)) {
        update_post_meta(
            $post_id,
            'story_date',
            sanitize_text_field($_POST['story_date'])
        );
    }
}

add_action('save_post', 'save_story_date_meta');

// Move custom field under 'Description' tab in editor
function move_story_date_meta_box() {
    remove_meta_box('story_date_meta_box', 'stories', 'normal');
    add_meta_box('story_date_meta_box', __('Story Date'), 'story_date_callback', 'stories', 'description', 'default');
}

add_action('admin_menu', 'move_story_date_meta_box');

// Shortcode to display Stories
function stories_shortcode($atts) {
    // Define default attributes
    $atts = shortcode_atts(
        array(
            'number_of_stories' => -1, // -1 to display all stories
            'order'             => 'DESC', // Order: 'ASC' (ascending) or 'DESC' (descending)
            'orderby'           => 'date', // Order by: 'date', 'title', 'rand' (random), etc.
        ),
        $atts,
        'stories_timeline_feed'
    );

    // Query Stories
    $stories_query = new WP_Query(
        array(
            'post_type'      => 'stories',
            'posts_per_page' => $atts['number_of_stories'],
            'order'          => $atts['order'],
            'orderby'        => $atts['orderby'],
        )
    );

    ob_start();

    if ($stories_query->have_posts()) {
        echo '<div class="container-st">';
        echo '<section class="stories_main"><ul class="timeline">';

        $first_post = true;

        while ($stories_query->have_posts()) {
            $stories_query->the_post();

            // Get post data
            $title = get_the_title();
            $description = get_the_content();
            $date = get_post_meta(get_the_ID(), 'story_date', true);
            $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');

            // Output HTML for each story
            echo '<li class="event">';
            echo '<input type="radio" name="tl-group" ' . ($first_post ? 'checked' : '') . '/>';
            echo '<label></label>';
            echo '<div class="thumb user-1" style="background-image: url(' . esc_url($thumbnail_url) . ');"><span>' . esc_html($date) . '</span></div>';
            echo '<div class="content-perspective"><div class="content"><div class="content-inner">';
            echo '<h3>' . esc_html($title) . '</h3>';
            echo '<p>' . strip_tags($description) . '</p>';

            echo '</div></div></div></li>';

            $first_post = false;
        }

        echo '</ul></section></div>';
    }

    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode('stories_timeline_feed', 'stories_shortcode');


// Function to display the settings page content for WP 3D Stories Timeline Feed
function wp_3d_stories_timeline_settings_page() {
    ?>
    <div class="wrap">
        <h1>WP 3D Stories Timeline Feed Shortcodes</h1>
        <p>Welcome to the WP 3D Stories Timeline Feed plugin settings page.</p>
        <h2>How to Use Shortcode</h2>
        <p>Here are a few shortcode examples for the WP 3D Stories Timeline Feed:</p>

        <p>Display the default 3D stories timeline feed with all available stories:</p>
        <pre>[stories_timeline_feed]</pre>

        <p>Display a specific number of Stories</p>
        <pre>[stories_timeline_feed number_of_stories="3"]</pre>

        <p>Display a custom number of latest stories with no Post Order</p>
        <pre>[stories_timeline_feed number_of_stories="5" order="DESC" orderby="date"]</pre>

    </div>
    <?php
}

// Function to add the settings page to the admin menu for WP 3D Stories Timeline Feed
function wp_3d_stories_timeline_add_menu() {
    add_options_page('WP 3D Stories Timeline Feed Settings', 'WP 3D Stories Timeline Feed', 'manage_options', 'wp-3d-stories-timeline-settings', 'wp_3d_stories_timeline_settings_page');
}

// Hook to add the settings page for WP 3D Stories Timeline Feed
add_action('admin_menu', 'wp_3d_stories_timeline_add_menu');

