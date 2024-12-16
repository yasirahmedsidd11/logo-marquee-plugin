<?php
/**
 * Plugin Name: Logo Marquee
 * Description: A plugin to create and display logo marquees.
 * Version: 1.0
 * Author: Yasir Ahmed Siddiqui
 * Author URI: https://yasirahmed.dev
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Register Custom Post Type for Logo Marquee
function lm_register_logo_marquee()
{
    register_post_type('logo_marquee', [
        'labels' => [
            'name' => 'Logo Marquees',
            'singular_name' => 'Logo Marquee',
            'add_new_item' => 'Add New Logo Marquee',
            'edit_item' => 'Edit Logo Marquee',
            'new_item' => 'New Logo Marquee',
            'view_item' => 'View Logo Marquee',
            'all_items' => 'All Logo Marquees',
        ],
        'public' => false,
        'show_ui' => true,
        'supports' => ['title'],
        'menu_icon' => 'dashicons-images-alt2',
    ]);
}
add_action('init', 'lm_register_logo_marquee');

// Add Meta Box for Images (Gallery)
function lm_add_meta_boxes()
{
    add_meta_box(
        'lm_images_meta_box',
        'Logo Marquee Images',
        'lm_render_meta_box',
        'logo_marquee',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'lm_add_meta_boxes');

// Render Meta Box Content
function lm_render_meta_box($post)
{
    wp_nonce_field('lm_save_meta_box', 'lm_meta_box_nonce');
    $images = get_post_meta($post->ID, 'lm_images', true);
    ?>
    <div id="lm-image-gallery">
        <?php
        if (!empty($images)) {
            foreach ($images as $image) {
                echo '<div class="lm-image-item">';
                echo '<img src="' . esc_url($image) . '" width="100" height="100">';
                echo '<input type="hidden" name="lm_images[]" value="' . esc_url($image) . '">';
                echo '<button type="button" class="lm-remove-image btn btn-danger">Remove</button>';
                echo '</div>';
            }
        }
        ?>
    </div>
    <button type="button" id="lm-add-images" class="button">Add Images</button>
    <?php
}

// Save Meta Box Data
function lm_save_meta_box($post_id)
{
    if (!isset($_POST['lm_meta_box_nonce']) || !wp_verify_nonce($_POST['lm_meta_box_nonce'], 'lm_save_meta_box')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['lm_images'])) {
        update_post_meta($post_id, 'lm_images', array_map('esc_url_raw', $_POST['lm_images']));
    } else {
        delete_post_meta($post_id, 'lm_images');
    }
}
add_action('save_post', 'lm_save_meta_box');

// Generate Shortcode for Displaying Marquee
function lm_generate_shortcode($atts)
{
    $post_id = intval($atts['id']);
    if (!$post_id || get_post_type($post_id) !== 'logo_marquee') {
        return 'Invalid logo marquee ID.';
    }
    // $atts = shortcode_atts(['id' => ''], $atts, 'logo_marquee');
    // $post_id = $atts['id'];

    $images = get_post_meta($post_id, 'lm_images', true);

    if (empty($images)) {
        return 'No images found for this marquee.';
    }
    ob_start();
    ?>
    <div class="marquee marquee--8" style="--marquee-items: <?php echo count($images); ?>">
        <?php foreach ($images as $image): ?>
            <img class="marquee__item" src="<?php echo esc_url($image); ?>" alt="">
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('logo_marquee', 'lm_generate_shortcode');

// Enqueue Scripts and Styles for Admin Pages
function lm_enqueue_assets($hook)
{
    global $post;

    // Only enqueue on the add and edit pages for the 'logo_marquee' custom post type
    if (in_array($hook, ['post-new.php', 'post.php']) && isset($post) && 'logo_marquee' === $post->post_type) {
        // Enqueue jQuery (already included in WordPress by default)
        wp_enqueue_script('jquery');

        // Enqueue WordPress media uploader
        wp_enqueue_media();

        // Enqueue the custom admin.js script
        wp_enqueue_script('logo-marquee-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', ['jquery'], '1.0.0', true);

        // Enqueue the CSS for the Admin Panel
        wp_enqueue_style('logo-marquee-admin-style', plugin_dir_url(__FILE__) . 'admin.css', [], '1.0.0');
    }
}
add_action('admin_enqueue_scripts', 'lm_enqueue_assets');

// Enqueue the CSS for the Frontend
function lm_enqueue_frontend_styles()
{
    wp_enqueue_style('lm-frontend-css', plugin_dir_url(__FILE__) . 'assets/style.css', [], '1.0.0');
}
add_action('wp_enqueue_scripts', 'lm_enqueue_frontend_styles');

// Display Shortcode on Listing Page
function lm_add_shortcode_column($columns)
{
    $columns['lm_shortcode'] = 'Shortcode';
    return $columns;
}
add_filter('manage_logo_marquee_posts_columns', 'lm_add_shortcode_column');

function lm_render_shortcode_column($column, $post_id)
{
    if ('lm_shortcode' === $column) {
        echo '<code>[logo_marquee id="' . esc_attr($post_id) . '"]</code>';
        // echo '<code>[logo_marquee id="' . $post_id . '"]</code>';
    }
}
add_action('manage_logo_marquee_posts_custom_column', 'lm_render_shortcode_column', 10, 2);
