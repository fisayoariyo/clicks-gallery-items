<?php
/**
 * Plugin Name: Clicks Gallery Items
 * Description: Gallery media and page copy for the storefront.
 * Version: 1.1.0
 * Author: Clicks Aladire
 */

if (!defined('ABSPATH')) {
	exit;
}

const CLICKS_GALLERY_SECTION_KEY = 'clicks_gallery_section';
const CLICKS_GALLERY_MEDIA_ID_KEY = 'clicks_gallery_media_id';
const CLICKS_GALLERY_LINKED_POST_ID_KEY = 'clicks_gallery_linked_post_id';
const CLICKS_GALLERY_SETTINGS_OPTION = 'clicks_gallery_page_settings';

function clicks_gallery_settings_defaults(): array {
	return [
		'page_eyebrow' => 'Gallery',
		'page_title' => 'Looks, stories, and reels',
		'page_intro' => 'Built with editorial inspiration from premium fashion layouts: large hero moments, horizontal reels, and story cards that feel alive on mobile.',
		'spotlight_label' => 'Editorial spotlight',
		'spotlight_headline' => 'Adire in motion',
		'reels_heading' => 'Reels',
		'reels_hint' => 'Swipe on mobile',
	];
}

function clicks_gallery_sanitize_settings($value): array {
	$value = is_array($value) ? $value : [];
	$defaults = clicks_gallery_settings_defaults();

	return [
		'page_eyebrow' => sanitize_text_field((string) ($value['page_eyebrow'] ?? $defaults['page_eyebrow'])),
		'page_title' => sanitize_text_field((string) ($value['page_title'] ?? $defaults['page_title'])),
		'page_intro' => sanitize_textarea_field((string) ($value['page_intro'] ?? $defaults['page_intro'])),
		'spotlight_label' => sanitize_text_field((string) ($value['spotlight_label'] ?? $defaults['spotlight_label'])),
		'spotlight_headline' => sanitize_text_field((string) ($value['spotlight_headline'] ?? $defaults['spotlight_headline'])),
		'reels_heading' => sanitize_text_field((string) ($value['reels_heading'] ?? $defaults['reels_heading'])),
		'reels_hint' => sanitize_text_field((string) ($value['reels_hint'] ?? $defaults['reels_hint'])),
	];
}

function clicks_gallery_get_settings(): array {
	$stored = get_option(CLICKS_GALLERY_SETTINGS_OPTION, []);
	return array_merge(
		clicks_gallery_settings_defaults(),
		clicks_gallery_sanitize_settings($stored)
	);
}

function clicks_gallery_story_link_options(int $selected_id): array {
	$posts = get_posts([
		'post_type' => ['page', 'post'],
		'post_status' => ['publish', 'draft', 'private', 'pending', 'future'],
		'posts_per_page' => 200,
		'orderby' => 'date',
		'order' => 'DESC',
	]);

	if ($selected_id <= 0) {
		return $posts;
	}

	$current = get_post($selected_id);
	if (!$current instanceof \WP_Post) {
		return $posts;
	}

	foreach ($posts as $post) {
		if ((int) $post->ID === $selected_id) {
			return $posts;
		}
	}

	array_unshift($posts, $current);
	return $posts;
}

add_action('init', function () {
	register_post_type('gallery_item', [
		'labels' => [
			'name' => 'Gallery items',
			'singular_name' => 'Gallery item',
			'add_new_item' => 'Add gallery item',
			'edit_item' => 'Edit gallery item',
		],
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_icon' => 'dashicons-format-gallery',
		'supports' => ['title', 'thumbnail', 'excerpt', 'editor', 'page-attributes'],
		'show_in_rest' => true,
		'rest_base' => 'gallery_item',
		'has_archive' => false,
	]);
});

add_action('admin_init', function () {
	register_setting('clicks_gallery_settings', CLICKS_GALLERY_SETTINGS_OPTION, [
		'type' => 'array',
		'sanitize_callback' => 'clicks_gallery_sanitize_settings',
		'default' => clicks_gallery_settings_defaults(),
	]);
});

add_action('admin_menu', function () {
	add_submenu_page(
		'edit.php?post_type=gallery_item',
		'Gallery page settings',
		'Page settings',
		'manage_options',
		'clicks-gallery-settings',
		'clicks_gallery_render_settings_page'
	);
});

add_action('add_meta_boxes', function () {
	add_meta_box(
		'clicks_gallery_item_fields',
		'Gallery (for storefront)',
		'clicks_gallery_item_metabox',
		'gallery_item',
		'normal',
		'high'
	);
});

function clicks_gallery_render_settings_page(): void {
	if (!current_user_can('manage_options')) {
		return;
	}

	$settings = clicks_gallery_get_settings();
	?>
	<div class="wrap">
		<h1>Gallery page settings</h1>
		<p>Control the editable copy shown on the storefront gallery page.</p>
		<form method="post" action="options.php">
			<?php settings_fields('clicks_gallery_settings'); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="clicks_gallery_page_eyebrow">Page eyebrow</label></th>
						<td><input type="text" class="regular-text" id="clicks_gallery_page_eyebrow" name="<?php echo esc_attr(CLICKS_GALLERY_SETTINGS_OPTION); ?>[page_eyebrow]" value="<?php echo esc_attr($settings['page_eyebrow']); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="clicks_gallery_page_title">Page title</label></th>
						<td><input type="text" class="regular-text" id="clicks_gallery_page_title" name="<?php echo esc_attr(CLICKS_GALLERY_SETTINGS_OPTION); ?>[page_title]" value="<?php echo esc_attr($settings['page_title']); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="clicks_gallery_page_intro">Page intro</label></th>
						<td><textarea class="large-text" rows="4" id="clicks_gallery_page_intro" name="<?php echo esc_attr(CLICKS_GALLERY_SETTINGS_OPTION); ?>[page_intro]"><?php echo esc_textarea($settings['page_intro']); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="clicks_gallery_spotlight_label">Spotlight label</label></th>
						<td><input type="text" class="regular-text" id="clicks_gallery_spotlight_label" name="<?php echo esc_attr(CLICKS_GALLERY_SETTINGS_OPTION); ?>[spotlight_label]" value="<?php echo esc_attr($settings['spotlight_label']); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="clicks_gallery_spotlight_headline">Spotlight headline</label></th>
						<td><input type="text" class="regular-text" id="clicks_gallery_spotlight_headline" name="<?php echo esc_attr(CLICKS_GALLERY_SETTINGS_OPTION); ?>[spotlight_headline]" value="<?php echo esc_attr($settings['spotlight_headline']); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="clicks_gallery_reels_heading">Reels heading</label></th>
						<td><input type="text" class="regular-text" id="clicks_gallery_reels_heading" name="<?php echo esc_attr(CLICKS_GALLERY_SETTINGS_OPTION); ?>[reels_heading]" value="<?php echo esc_attr($settings['reels_heading']); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="clicks_gallery_reels_hint">Reels hint</label></th>
						<td><input type="text" class="regular-text" id="clicks_gallery_reels_hint" name="<?php echo esc_attr(CLICKS_GALLERY_SETTINGS_OPTION); ?>[reels_hint]" value="<?php echo esc_attr($settings['reels_hint']); ?>" /></td>
					</tr>
				</tbody>
			</table>
			<?php submit_button('Save gallery settings'); ?>
		</form>
	</div>
	<?php
}

function clicks_gallery_item_metabox(\WP_Post $post): void {
	wp_nonce_field('clicks_gallery_item_save', 'clicks_gallery_item_nonce');
	$section = get_post_meta($post->ID, CLICKS_GALLERY_SECTION_KEY, true) ?: 'look';
	$media_id = (int) get_post_meta($post->ID, CLICKS_GALLERY_MEDIA_ID_KEY, true);
	$linked_post_id = (int) get_post_meta($post->ID, CLICKS_GALLERY_LINKED_POST_ID_KEY, true);
	$url = $media_id ? wp_get_attachment_url($media_id) : '';
	$link_options = clicks_gallery_story_link_options($linked_post_id);
	?>
	<p>
		Create one gallery item per look, reel, or story card. To add another reel, create a new gallery item instead of replacing an existing one.
	</p>
	<p>
		<label for="clicks_gallery_section"><strong>Section</strong></label><br />
		<select name="clicks_gallery_section" id="clicks_gallery_section" style="max-width:100%;">
			<option value="look" <?php selected($section, 'look'); ?>>Look (image grid / hero)</option>
			<option value="reel" <?php selected($section, 'reel'); ?>>Reel (video)</option>
			<option value="story" <?php selected($section, 'story'); ?>>Story (image + title + text)</option>
		</select>
	</p>
	<p>
		<label><strong>Media file</strong></label><br />
		<input type="hidden" name="clicks_gallery_media_id" id="clicks_gallery_media_id" value="<?php echo esc_attr((string) $media_id); ?>" />
		<button type="button" class="button" id="clicks_gallery_pick_media">Choose from Media Library</button>
		<button type="button" class="button" id="clicks_gallery_clear_media" style="margin-left:6px;">Clear</button>
	</p>
	<p id="clicks_gallery_media_preview" style="word-break:break-all;">
		<?php if ($url) : ?>
			<?php echo esc_html($url); ?>
		<?php else : ?>
			<em>No file selected.</em>
		<?php endif; ?>
	</p>
	<p>
		<label for="clicks_gallery_linked_post_id"><strong>Story destination (optional)</strong></label><br />
		<select name="clicks_gallery_linked_post_id" id="clicks_gallery_linked_post_id" style="max-width:100%; min-width:320px;">
			<option value="">Use the storefront /story page</option>
			<?php
			$current_group = '';
			foreach ($link_options as $linked_post) :
				$group = $linked_post->post_type === 'page' ? 'Pages' : 'Posts';
				if ($group !== $current_group) :
					if ($current_group !== '') :
						?>
						</optgroup>
						<?php
					endif;
					$current_group = $group;
					?>
					<optgroup label="<?php echo esc_attr($group); ?>">
					<?php
				endif;

				$status = $linked_post->post_status !== 'publish' ? ' [' . $linked_post->post_status . ']' : '';
				?>
				<option value="<?php echo esc_attr((string) $linked_post->ID); ?>" <?php selected($linked_post_id, (int) $linked_post->ID); ?>>
					<?php echo esc_html(get_the_title($linked_post) . $status); ?>
				</option>
			<?php endforeach; ?>
			<?php if ($current_group !== '') : ?>
				</optgroup>
			<?php endif; ?>
		</select>
	</p>
	<p class="description">
		For story cards, title becomes the card headline, excerpt becomes the short summary, and this optional field decides where "Read story" opens.
	</p>
	<script>
	(function () {
		var pick = document.getElementById('clicks_gallery_pick_media');
		var clear = document.getElementById('clicks_gallery_clear_media');
		var input = document.getElementById('clicks_gallery_media_id');
		var prev = document.getElementById('clicks_gallery_media_preview');
		if (!pick || !input) return;
		pick.addEventListener('click', function () {
			var frame = wp.media({ title: 'Choose image or video', button: { text: 'Use this file' }, multiple: false });
			frame.on('select', function () {
				var att = frame.state().get('selection').first().toJSON();
				input.value = att.id;
				prev.textContent = att.url || '';
			});
			frame.open();
		});
		if (clear) {
			clear.addEventListener('click', function () {
				input.value = '';
				prev.innerHTML = '<em>No file selected.</em>';
			});
		}
	})();
	</script>
	<?php
}

add_action('admin_enqueue_scripts', function ($hook) {
	if ($hook !== 'post.php' && $hook !== 'post-new.php') {
		return;
	}
	$screen = get_current_screen();
	if (!$screen || $screen->post_type !== 'gallery_item') {
		return;
	}
	wp_enqueue_media();
});

add_action('save_post', function ($post_id) {
	if (get_post_type($post_id) !== 'gallery_item') {
		return;
	}
	if (!isset($_POST['clicks_gallery_item_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['clicks_gallery_item_nonce'])), 'clicks_gallery_item_save')) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	$section = isset($_POST['clicks_gallery_section']) ? sanitize_text_field(wp_unslash($_POST['clicks_gallery_section'])) : 'look';
	if (!in_array($section, ['look', 'reel', 'story'], true)) {
		$section = 'look';
	}
	update_post_meta($post_id, CLICKS_GALLERY_SECTION_KEY, $section);

	$media_id = isset($_POST['clicks_gallery_media_id']) ? absint($_POST['clicks_gallery_media_id']) : 0;
	if ($media_id > 0) {
		update_post_meta($post_id, CLICKS_GALLERY_MEDIA_ID_KEY, $media_id);
	} else {
		delete_post_meta($post_id, CLICKS_GALLERY_MEDIA_ID_KEY);
	}

	$linked_post_id = isset($_POST['clicks_gallery_linked_post_id']) ? absint($_POST['clicks_gallery_linked_post_id']) : 0;
	$linked_post_type = $linked_post_id > 0 ? get_post_type($linked_post_id) : '';
	if ($linked_post_id > 0 && in_array($linked_post_type, ['post', 'page'], true)) {
		update_post_meta($post_id, CLICKS_GALLERY_LINKED_POST_ID_KEY, $linked_post_id);
	} else {
		delete_post_meta($post_id, CLICKS_GALLERY_LINKED_POST_ID_KEY);
	}
}, 10, 1);

add_action('rest_api_init', function () {
	register_rest_field('gallery_item', 'clicks_gallery_section', [
		'schema' => [
			'type' => 'string',
			'context' => ['view', 'edit'],
		],
		'get_callback' => function (array $obj) {
			$id = (int) ($obj['id'] ?? 0);
			$section = get_post_meta($id, CLICKS_GALLERY_SECTION_KEY, true);
			return is_string($section) && $section !== '' ? $section : 'look';
		},
	]);

	register_rest_field('gallery_item', 'clicks_gallery_media_url', [
		'schema' => [
			'type' => ['string', 'null'],
			'format' => 'uri',
			'context' => ['view', 'edit'],
		],
		'get_callback' => function (array $obj) {
			$id = (int) ($obj['id'] ?? 0);
			$attachment_id = (int) get_post_meta($id, CLICKS_GALLERY_MEDIA_ID_KEY, true);
			if ($attachment_id <= 0) {
				return null;
			}
			$url = wp_get_attachment_url($attachment_id);
			return $url ? $url : null;
		},
	]);

	register_rest_field('gallery_item', 'clicks_gallery_linked_post_id', [
		'schema' => [
			'type' => ['integer', 'null'],
			'context' => ['view', 'edit'],
		],
		'get_callback' => function (array $obj) {
			$id = (int) ($obj['id'] ?? 0);
			$linked_post_id = (int) get_post_meta($id, CLICKS_GALLERY_LINKED_POST_ID_KEY, true);
			return $linked_post_id > 0 ? $linked_post_id : null;
		},
	]);

	register_rest_field('gallery_item', 'clicks_gallery_linked_post_url', [
		'schema' => [
			'type' => ['string', 'null'],
			'format' => 'uri',
			'context' => ['view', 'edit'],
		],
		'get_callback' => function (array $obj) {
			$id = (int) ($obj['id'] ?? 0);
			$linked_post_id = (int) get_post_meta($id, CLICKS_GALLERY_LINKED_POST_ID_KEY, true);
			if ($linked_post_id <= 0) {
				return null;
			}
			$url = get_permalink($linked_post_id);
			return $url ? $url : null;
		},
	]);

	register_rest_route('clicks/v1', '/gallery-settings', [
		'methods' => \WP_REST_Server::READABLE,
		'permission_callback' => '__return_true',
		'callback' => function () {
			return rest_ensure_response(clicks_gallery_get_settings());
		},
	]);
});
