<?php
/**
 * Plugin Name: Register Blocks for ACF
 * Description: Register, edit, and delete custom ACF blocks via the WordPress admin.
 * Version: 1.0.0
 * Author: Dan Freeman 
 */

if (!defined('ABSPATH')) exit;

// Allow SVG and image uploads
add_filter('upload_mimes', function ($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    $mimes['jpg'] = 'image/jpeg';
    $mimes['jpeg'] = 'image/jpeg';
    $mimes['png'] = 'image/png';
    return $mimes;
});

// Enqueue media uploader scripts for admin
add_action('admin_enqueue_scripts', function ($hook) {
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        wp_enqueue_media();
        wp_enqueue_script('acf-dynamic-blocks-admin', plugin_dir_url(__FILE__) . 'main.js', ['jquery'], null, true);
        wp_enqueue_style('acf-dynamic-blocks-admin-style', plugin_dir_url(__FILE__) . 'style.css');
    }
});

//remove the publish box
add_action('admin_menu', function () {
  remove_meta_box('submitdiv', 'acf_block_template', 'side');
});
add_action('admin_head-post.php', function () {
    global $post;
    if ($post->post_type === 'acf_block_template') {
        echo '<style>.misc-pub-section, .edit-post-post-status { display: none !important; }</style>';
    }
});

//remove the default title box
add_action('admin_head', function () {
  $screen = get_current_screen();
  if ($screen->post_type === 'acf_block_template') {
    echo '<style>#titlediv { display: none !important; }</style>';
  }
});

// Register Block Template CPT
add_action('init', function () {
    register_post_type('acf_block_template', [
        'label' => 'Register Blocks',
        'public' => false,
        'show_ui' => true,
        'menu_position' => 80,
        'menu_icon' => 'dashicons-layout',
        'supports' => ['title'],
        'labels' => [
            'name' => 'Blocks',
            'singular_name' => 'Block',
            'add_new' => 'Add New Block',
            'add_new_item' => 'Add New Block',
            'edit_item' => 'Edit Block',
            'new_item' => 'New Block',
            'view_item' => 'View Block',
            'search_items' => 'Search Blocks',
            'not_found' => 'No Blocks found',
            'not_found_in_trash' => 'No Blocks found in Trash',
            'all_items' => 'All Blocks',
            'menu_name' => 'Register Blocks',
            'name_admin_bar' => 'Block'
        ],
    ]);
});

// Removes the meta box for the fields to be rendered in
add_action('edit_form_after_title', function ($post) {
  if ($post->post_type === 'acf_block_template') {
    // Renderthe fields directly
    acf_block_details_meta_box($post); 
  }
});

//removes the screen options box
add_action('admin_head', function () {
  $screen = get_current_screen();
  if ($screen->post_type === 'acf_block_template') {
    echo '<style>#screen-options-link-wrap { display: none !important; }</style>';
  }
});

//changes message save text
add_filter('post_updated_messages', function ($messages) {
    global $post;

    if ($post->post_type !== 'acf_block_template') return $messages;

    $messages['acf_block_template'][1] = 'Block saved.';
    $messages['acf_block_template'][6] = 'Block saved.';
    $messages['acf_block_template'][4] = 'Block updated.';

    return $messages;
});

function acf_block_details_meta_box($post) {
    $meta = fn($key, $default = '') => get_post_meta($post->ID, $key, true) ?: $default;

    $block_slug = $meta('block_slug');
    if (empty($block_slug)) {
        $block_slug = sanitize_title($post->post_title);
    }
    $block_description = $meta('block_description');
    $block_category = $meta('block_category');
    $block_mode = $meta('block_mode', 'preview');
    $block_icon_svg = $meta('block_icon_svg');
    $block_preview_img = $meta('block_preview_img');

    ?>

    <div id="df-header">
      <h1>Edit Block</h1>
      <?php $post_title = ($post->post_status === 'auto-draft') ? '' : get_the_title($post->ID); ?>
      <input type="text" id="df-title" name="post_title" value="<?= esc_attr($post_title)?>" placeholder="Block Title" />

      <div id="header-buttons">
        <?php 
        //add new button
        echo '<a id="add-new" href="' . admin_url('post-new.php?post_type=acf_block_template') . '" class="button button-secondary">Add New Block</a>';
        //save button
        submit_button('Save Block', 'primary', 'publish', false); 
        ?>
      </div>
    </div>


    <div id="df-fields">
      <div id='df-slug' class="section">
        <label for='block_slug'>
          <strong>Block Slug:</strong>
        </label>
        <input type='text' id='block_slug' name='block_slug' value='<?= esc_attr($block_slug) ?>'/>
      </div>

      <div id="df-description" class="section">
        <label for='block_description'>
          <strong>Description:</strong>
        </label>
        <textarea id='block_description' name='block_description' rows='4'><?= esc_textarea($block_description) ?></textarea>
      </div>

      <div id="df-cat" class="section">
        <label for='block_category'>
          <strong>Category (e.g. Images):</strong>
        </label>
        <input type='text' id='block_category' name='block_category' value='<?= esc_attr($block_category)?>'/>
      </div>

      <!-- <div id="df-svg-icon" class="section">
        <label for='block_icon_svg'>
          <strong>SVG Icon:</strong>
        </label>
        <input type='text' id='block_icon_svg' name='block_icon_svg' value='<?= esc_attr($block_icon_svg)?>'/>
        <button type='button' class='button upload-media-button' data-target='block_icon_svg'>Select SVG</button>
      </div> -->


      <div id="df-icon" class="section">

        <?php 
          $selected_icon = get_post_meta($post->ID, 'block_icon_dashicon', true) ?: 'admin-generic';
          $dashicons = [
          'admin-comments', 'admin-generic', 'admin-home', 'admin-links', 'admin-network', 'admin-page', 'admin-plugins', 'admin-settings', 'admin-site', 'admin-site-alt3', 'admin-tools', 'admin-users', 'awards', 'bank', 'beer', 'bell', 'book-alt', 'calendar-alt', 'camera-alt', 'car', 'cart', 'chart-bar', 'chart-pie', 'clock', 'cloud', 'controls-volumeon', 'cover-image', 'dashboard', 'dismiss', 'download', 'edit', 'editor-code', 'editor-help', 'editor-justify', 'editor-kitchensink', 'editor-table', 'editor-ul', 'editor-video', 'ellipsis', 'email', 'excerpt-view', 'feedback', 'flag', 'format-aside', 'format-audio', 'format-gallery', 'format-image', 'format-quote', 'format-video', 'games', 'grid-view', 'hammer', 'heading', 'heart', 'hidden', 'id-alt', 'images-alt', 'images-alt2', 'index-card', 'info', 'layout', 'lightbulb', 'list-view', 'location', 'lock', 'marker', 'megaphone', 'menu-alt', 'microphone', 'no', 'open-folder', 'palmtree', 'paperclip', 'performance', 'pets', 'phone', 'plus', 'post-trash', 'remove', 'rss', 'saved', 'schedule', 'screenoptions', 'search', 'shield-alt', 'star-filled', 'sticky', 'tablet', 'tag', 'tagcloud', 'testimonial', 'text', 'trash', 'update-alt', 'upload', 'video-alt2', 'video-alt3', 'visibility', 'welcome-widgets-menus', 'welcome-write-blog'
          ];

        // Hidden input to store selected icon
        ?>
        <label for='block_icon_dashicon'>
          <strong>Icon:</strong>
        </label>
        <input type="hidden" id="block_icon_dashicon" name="block_icon_dashicon" value="<?= esc_attr($selected_icon); ?>">

        <div id="dashicon-grid">
          <?php
            foreach ($dashicons as $icon) {
            $active = $icon === $selected_icon ? 'selected' : '';
            echo "<div class='dashicon-choice $active' data-icon='$icon'>";
            echo "<span class='dashicons dashicons-$icon'></span>";
            echo '</div>';
          }
          ?>
        </div>
      </div>

      <div id="df-image" class="section">
        <label for='block_preview_img'>
          <strong>Preview Image (JPG/PNG):</strong>
        </label>
        <input type='text' id='block_preview_img' name='block_preview_img' value='<?= esc_attr($block_preview_img) ?>'/><br />
        <button type='button' class='button upload-media-button' data-target='block_preview_img'>Select Image</button>
      </div>

      <div id="df-mode" class="section">
        <label for='block_mode'><strong>Mode:</strong></label>
        <select name='block_mode' id='block_mode'>
        <?php
        foreach (['preview', 'edit', 'auto'] as $opt) {
          printf("<option value='%s'%s>%s</option>", $opt, selected($block_mode, $opt, false), ucfirst($opt));
        }
        ?>
        </select>
      </div>
    </div>
    <?php
}


// Save block settings
add_action('save_post_acf_block_template', function ($post_id) {
    foreach (['block_slug', 'block_description', 'block_category', 'block_icon_dashicon', 'block_preview_img', 'block_mode'] as $key) {
        if (isset($_POST[$key])) {
            $value = sanitize_text_field($_POST[$key]);
            if ($key === 'block_slug') {
                $value = sanitize_title($value);
            }
            update_post_meta($post_id, $key, $value);
        }
    }
});

// Register custom block categories dynamically and override core labels
add_filter('block_categories_all', function ($categories, $editor_context) {
    $blocks = get_posts(['post_type' => 'acf_block_template', 'post_status' => 'publish', 'numberposts' => -1]);

    $custom = [];
    foreach ($blocks as $block) {
        $cat = get_post_meta($block->ID, 'block_category', true);
        if ($cat) {
            $slug = sanitize_title($cat);
            $custom[$slug] = [
                'slug' => $slug,
                'title' => $cat
            ];
        }
    }

    foreach ($categories as &$cat) {
        if (isset($custom[$cat['slug']])) $cat['title'] = $custom[$cat['slug']]['title'];
    }

    foreach ($custom as $slug => $catObj) {
        if (!array_filter($categories, fn($c) => $c['slug'] === $slug)) {
            $categories[] = $catObj;
        }
    }

    usort($categories, fn($a, $b) => strcmp($a['title'], $b['title']));

    return $categories;
}, 10, 2);

// Register blocks dynamically from block templates
add_action('acf/init', function () {
    if (!function_exists('acf_register_block_type')) return;

    $blocks = get_posts(['post_type' => 'acf_block_template', 'post_status' => 'publish', 'numberposts' => -1]);

    foreach ($blocks as $block) {
        $meta = fn($key, $default = '') => get_post_meta($block->ID, $key, true) ?: $default;

        $slug = $meta('block_slug', sanitize_title($block->post_title));
        $desc = $meta('block_description');
        $cat = $meta('block_category', 'custom');
        $svg = $meta('block_icon_svg');
        $preview_img = $meta('block_preview_img');
        $mode = $meta('block_mode', 'preview');

        $icon = 'carrot';
        if ($svg && str_ends_with($svg, '.svg')) {
            $svg_path = str_replace(wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $svg);
            if (file_exists($svg_path)) {
                $icon = file_get_contents($svg_path);
            }
        }
        $dashicon = $meta('block_icon_dashicon', 'admin-generic');

        $args = [
            'name' => $slug,
            'title' => $block->post_title,
            'description' => $desc,
            'category' => sanitize_title($cat ?: 'custom'),
            'keywords' => [$cat],
            'icon' => $dashicon,
            'mode' => $mode,
            'render_callback' => 'acf_dynamic_blocks_render',
            'supports' => ['align' => false],
        ];

        if ($preview_img && filter_var($preview_img, FILTER_VALIDATE_URL)) {
            $args['example'] = [
                'attributes' => [],
                'data' => [
                    'preview_image_help' => $preview_img
                ],
                'mode' => 'preview'
            ];
        }

        acf_register_block_type($args);
    }
});

// Shared render callback
function acf_dynamic_blocks_render($block) {
    if (!empty($block['data']['preview_image_help']) && (isset($block['is_preview']) && $block['is_preview'])) {
        echo '<img src="' . esc_url($block['data']['preview_image_help']) . '" style="max-width:100%;height:auto;" />';
        return;
    }

    $slug = str_replace('acf/', '', $block['name']);
    $fields = get_fields() ?: [];
    printf('<div class="acf-dynamic-block %s" data-attrs="%s"></div>', esc_attr($slug), esc_attr(wp_json_encode($fields)));
}

// Restrict allowed blocks to custom-defined blocks
add_filter('allowed_block_types_all', function ($allowed_blocks, $editor_context) {
    $blocks = get_posts(['post_type' => 'acf_block_template', 'post_status' => 'publish', 'numberposts' => -1]);

    $allowed = [];
    foreach ($blocks as $block) {
        $slug = get_post_meta($block->ID, 'block_slug', true) ?: sanitize_title($block->post_title);
        $allowed[] = 'acf/' . $slug;
    }
    return $allowed;
}, 10, 2);
