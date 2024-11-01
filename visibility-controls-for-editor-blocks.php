<?php
/**
 * Plugin Name: Visibility Controls for Editor Blocks
 * Description: Adds controls to hide blocks on mobile, tablet, and desktop devices in Gutenberg.
 * Tags: block, visibility, gutenberg, responsive, breakpoints
 * Version: 1.0.6
 * Author: Denis Doroshchuk
 * Author URI: https://doroshchuk.me/
 * Text Domain: visibility-controls-for-editor-blocks
 * Domain Path: /languages
 * License: GPLv3.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load text domain for translations
function gbvc_load_text_domain(): void {
	load_plugin_textdomain( 'visibility-controls-for-editor-blocks', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'plugins_loaded', 'gbvc_load_text_domain' );

// Enqueue block editor assets (JavaScript and CSS) for Gutenberg.
function gbvc_enqueue_block_editor_assets(): void {
	wp_enqueue_script( 'gbvc-editor', plugins_url( 'build/gbvc-editor.js', __FILE__ ), array(
		'wp-blocks',
		'wp-element',
		'wp-i18n',
		'wp-edit-post',
		'wp-dom-ready'
	), '1.0.3', true );

	wp_set_script_translations( 'gbvc-editor', 'visibility-controls-for-editor-blocks', plugin_dir_path( __FILE__ ) . 'languages' );
}

add_action( 'enqueue_block_editor_assets', 'gbvc_enqueue_block_editor_assets' );

// Enqueue frontend styles for the block visibility controls.
function gbvc_enqueue_frontend_styles(): void {
	$disable_styles = get_option( 'gbvc_disable_styles_on_non_gutenberg_pages', false );

	if ( ! $disable_styles || gbvc_is_gutenberg_page() || is_admin() ) {
		$mobile_breakpoint     = get_option( 'gbvc_mobile_breakpoint', '600' );
		$tablet_min_breakpoint = $mobile_breakpoint + 1;
		$tablet_breakpoint     = get_option( 'gbvc_tablet_breakpoint', '1024' );
		$desktop_breakpoint    = $tablet_breakpoint + 1;
		$inline_style          = "@media screen and (max-width: " . esc_attr( $mobile_breakpoint ) . "px) {.gbvc-hide-on-mobile {display: none !important}}@media screen and (min-width: " . esc_attr( $tablet_min_breakpoint ) . "px) and (max-width: " . esc_attr( $tablet_breakpoint ) . "px) {.gbvc-hide-on-tablet {display: none !important}}@media screen and (min-width: " . esc_attr( $desktop_breakpoint ) . "px) {.gbvc-hide-on-desktop {display: none !important}}";

		if ( ! is_admin() ) {
			$inline_style .= "body.logged-in .gbvc-hide-for-logged-in {display: none !important;}body:not(.logged-in) .gbvc-hide-for-non-logged-in {display: none !important;}";
		} else {
			$inline_style .= ".gbvc-hide-for-logged-in, .gbvc-hide-for-non-logged-in { position: relative; opacity: 0.6; pointer-events: none; } .gbvc-hide-for-logged-in:after, .gbvc-hide-for-non-logged-in::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: repeating-linear-gradient(45deg, rgba(255, 0, 0, 0.1), rgba(255, 0, 0, 0.1) 5px, transparent 5px, transparent 10px); z-index: 5; pointer-events: none; }";
		}

		wp_register_style( 'gbvc-styles', false, array(), '1.0.0' );
		wp_add_inline_style( 'gbvc-styles', $inline_style );
		wp_enqueue_style( 'gbvc-styles' );
	}
}

add_action( 'enqueue_block_assets', 'gbvc_enqueue_frontend_styles' );

// Function to check if the page contains Gutenberg blocks or not
function gbvc_is_gutenberg_page(): bool {
	if ( is_singular() && has_blocks( get_the_ID() ) ) {
		return true;
	}

	return false;
}

// Function to add the settings page
function gbvc_add_settings_page(): void {
	add_options_page( __( 'Visibility Controls for Gutenberg Blocks', 'visibility-controls-for-editor-blocks' ), __( 'Gutenberg Blocks Visibility', 'visibility-controls-for-editor-blocks' ), 'manage_options', 'gbvc-settings', 'gbvc_render_settings_page' );
}

add_action( 'admin_menu', 'gbvc_add_settings_page' );

// Render the settings page
function gbvc_render_settings_page(): void {
	?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

        <form method="post" action="options.php">
			<?php
			settings_fields( 'gbvc_settings_group' );
			do_settings_sections( 'gbvc-breakpoints-settings' );
			do_settings_sections( 'gbvc-advanced-settings' );
			submit_button();
			?>
        </form>
    </div>
	<?php
}

// Register the settings
function gbvc_register_settings(): void {
	// Register settings group
	register_setting( 'gbvc_settings_group', 'gbvc_mobile_breakpoint', array( 'sanitize_callback' => 'gbvc_sanitize_input_number' ) );
	register_setting( 'gbvc_settings_group', 'gbvc_tablet_breakpoint', array( 'sanitize_callback' => 'gbvc_sanitize_input_number' ) );
	register_setting( 'gbvc_settings_group', 'gbvc_disable_styles_on_non_gutenberg_pages', array( 'sanitize_callback' => 'gbvc_sanitize_checkbox' ) );

	// Breakpoints settings section
	add_settings_section( 'gbvc_breakpoints_settings_section', __( 'Breakpoints Settings', 'visibility-controls-for-editor-blocks' ), 'gbvc_breakpoints_settings_section_callback', 'gbvc-breakpoints-settings' );

	// Field for mobile breakpoint
	add_settings_field( 'gbvc_mobile_breakpoint', __( 'Mobile Breakpoint (px)', 'visibility-controls-for-editor-blocks' ), 'gbvc_mobile_breakpoint_callback', 'gbvc-breakpoints-settings', 'gbvc_breakpoints_settings_section' );

	// Field for tablet breakpoint
	add_settings_field( 'gbvc_tablet_breakpoint', __( 'Tablet Breakpoint (px)', 'visibility-controls-for-editor-blocks' ), 'gbvc_tablet_breakpoint_callback', 'gbvc-breakpoints-settings', 'gbvc_breakpoints_settings_section' );

	// Field for desktop breakpoint
	add_settings_field( 'gbvc_desktop_breakpoint', __( 'Desktop Breakpoint (px)', 'visibility-controls-for-editor-blocks' ), 'gbvc_desktop_breakpoint_callback', 'gbvc-breakpoints-settings', 'gbvc_breakpoints_settings_section' );

	// Advanced settings section
	add_settings_section( 'gbvc_advanced_settings_section', __( 'Advanced Settings', 'visibility-controls-for-editor-blocks' ), 'gbvc_advanced_settings_section_callback', 'gbvc-advanced-settings' );

	// Field for disable styles on non gutenberg pages
	add_settings_field( 'gbvc_desktop_breakpoint', __( 'Styles loading', 'visibility-controls-for-editor-blocks' ), 'gbvc_disable_styles_on_non_gutenberg_pages_callback', 'gbvc-advanced-settings', 'gbvc_advanced_settings_section' );
}

add_action( 'admin_init', 'gbvc_register_settings' );

// Callback for sanitize input number
function gbvc_sanitize_input_number( $input ): int {
	return absint( $input );
}

// Callback for sanitize checkbox
function gbvc_sanitize_checkbox( $input ): bool {
	return isset( $input ) && $input;
}

// Callback for the Breakpoints settings section
function gbvc_breakpoints_settings_section_callback(): void {
	echo esc_html__( 'Configure the breakpoints for mobile, tablet and desktop devices.', 'visibility-controls-for-editor-blocks' );
}

// Callback for the Advanced settings section
function gbvc_advanced_settings_section_callback(): void {
	echo '';
}

// Callback for disable styles on non gutenberg pages checkbox field
function gbvc_disable_styles_on_non_gutenberg_pages_callback(): void {
	$value = get_option( 'gbvc_disable_styles_on_non_gutenberg_pages', false );
	echo '<input type="checkbox" name="gbvc_disable_styles_on_non_gutenberg_pages" value="1"' . checked( 1, $value, false ) . '/>';
	echo '<label for="gbvc_disable_styles_on_non_gutenberg_pages">' . esc_html__( 'Disable CSS loading on pages without Gutenberg', 'visibility-controls-for-editor-blocks' ) . '</label>';
	echo '<p class="description"><small>' . esc_html__( 'When this option is enabled, the plugin will prevent loading its CSS code on pages that do not use the Gutenberg editor.', 'visibility-controls-for-editor-blocks' ) . '<br>' . esc_html__( 'This includes archive pages such as categories and tags, as well as other pages that do not contain Gutenberg blocks.', 'visibility-controls-for-editor-blocks' ) . '</small></p>';
}

// Callback for mobile breakpoint input field
function gbvc_mobile_breakpoint_callback(): void {
	$value = get_option( 'gbvc_mobile_breakpoint', '600' );
	echo '<input type="number" name="gbvc_mobile_breakpoint" value="' . esc_attr( $value ) . '" /> px';
	echo '<p class="description"><small>' . esc_html__( 'This option defines the screen width (in pixels) that determines a mobile device.', 'visibility-controls-for-editor-blocks' ) . '<br>' . esc_html__( 'Blocks hidden on mobile will be hidden on screens this size or smaller.', 'visibility-controls-for-editor-blocks' ) . '<br><strong>' . esc_html__( 'Recommended: between 320px and 600px for mobiles.', 'visibility-controls-for-editor-blocks' ) . '</strong></small></p>';
}

// Callback for tablet breakpoint input field
function gbvc_tablet_breakpoint_callback(): void {
	$value = get_option( 'gbvc_tablet_breakpoint', '1024' );
	echo '<input type="number" name="gbvc_tablet_breakpoint" value="' . esc_attr( $value ) . '" /> px';
	echo '<p class="description"><small>' . esc_html__( 'This option defines the screen width (in pixels) that determines a tablet device.', 'visibility-controls-for-editor-blocks' ) . '<br>' . esc_html__( 'Blocks hidden on tablets will be hidden on screens this size or smaller.', 'visibility-controls-for-editor-blocks' ) . '<br><strong>' . esc_html__( 'Recommended: between 601px and 1024px for tablets.', 'visibility-controls-for-editor-blocks' ) . '</strong></small></p>';
}

// Callback for desktop breakpoint input field
function gbvc_desktop_breakpoint_callback(): void {
	echo '<p class="description"><small><strong>' . esc_html__( 'Note: ', 'visibility-controls-for-editor-blocks' ) . '</strong>' . esc_html__( 'There\'s no input for the desktop breakpoint because it is automatically defined', 'visibility-controls-for-editor-blocks' ) . '<br>' . esc_html__( 'Any screen wider than the tablet breakpoint will be considered a desktop.', 'visibility-controls-for-editor-blocks' ) . '</small></p>';
}

// Add settings link on the plugins page
function gbvc_add_settings_link( $links ) {
	$settings_link = '<a href="options-general.php?page=gbvc-settings">' . esc_html__( 'Settings', 'visibility-controls-for-editor-blocks' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'gbvc_add_settings_link' );

// Add visibility classes to dynamic blocks.
function gbvc_add_visibility_classes( $block_content, $block ) {
	// Define a list of dynamic blocks that should receive visibility classes.
	$dynamic_blocks = array(
		'core/legacy-widget',
		'core/widget-group',
		'core/archives',
		'core/avatar',
		'core/block',
		'core/button',
		'core/calendar',
		'core/categories',
		'core/comment-author-name',
		'core/comment-content',
		'core/comment-date',
		'core/comment-edit-link',
		'core/comment-reply-link',
		'core/comment-template',
		'core/comments',
		'core/comments-pagination',
		'core/comments-pagination-next',
		'core/comments-pagination-numbers',
		'core/comments-pagination-previous',
		'core/comments-title',
		'core/cover',
		'core/file',
		'core/footnotes',
		'core/gallery',
		'core/heading',
		'core/home-link',
		'core/image',
		'core/latest-comments',
		'core/latest-posts',
		'core/list',
		'core/loginout',
		'core/media-text',
		'core/navigation',
		'core/navigation-link',
		'core/navigation-submenu',
		'core/page-list',
		'core/pattern',
		'core/post-author',
		'core/post-author-biography',
		'core/post-author-name',
		'core/post-comments-form',
		'core/post-content',
		'core/post-date',
		'core/post-excerpt',
		'core/post-featured-image',
		'core/post-navigation-link',
		'core/post-template',
		'core/post-terms',
		'core/post-title',
		'core/query',
		'core/query-no-results',
		'core/query-pagination',
		'core/query-pagination-next',
		'core/query-pagination-numbers',
		'core/query-pagination-previous',
		'core/query-title',
		'core/read-more',
		'core/rss',
		'core/search',
		'core/shortcode',
		'core/site-logo',
		'core/site-tagline',
		'core/site-title',
		'core/social-link',
		'core/tag-cloud',
		'core/template-part',
		'core/term-description',
	);

	// Check if the block is a dynamic block from the list.
	if ( in_array( $block['blockName'], $dynamic_blocks, true ) ) {
		$additional_classes = '';

		// Add the 'hide on mobile' class if set in the block attributes.
		if ( isset( $block['attrs']['hideOnMobile'] ) && $block['attrs']['hideOnMobile'] ) {
			$additional_classes .= ' gbvc-hide-on-mobile';
		}

		// Add the 'hide on tablet' class if set in the block attributes.
		if ( isset( $block['attrs']['hideOnTablet'] ) && $block['attrs']['hideOnTablet'] ) {
			$additional_classes .= ' gbvc-hide-on-tablet';
		}

		// Add the 'hide on desktop' class if set in the block attributes.
		if ( isset( $block['attrs']['hideOnDesktop'] ) && $block['attrs']['hideOnDesktop'] ) {
			$additional_classes .= ' gbvc-hide-on-desktop';
		}

		// Add the 'hide for logged-in users' class if set in the block attributes.
		if ( isset( $block['attrs']['hideForLoggedInUsers'] ) && $block['attrs']['hideForLoggedInUsers'] ) {
			$additional_classes .= ' gbvc-hide-for-logged-in';
		}


		// Add the 'hide for non-logged-in users' class if set in the block attributes.
		if ( isset( $block['attrs']['hideForNonLoggedInUsers'] ) && $block['attrs']['hideForNonLoggedInUsers'] ) {
			$additional_classes .= ' gbvc-hide-for-non-logged-in';
		}

		// If there are any visibility classes to add
		if ( ! empty( $additional_classes ) ) {
			// Check if block already has a className attribute, and append the new classes.
			if ( ! empty( $block['attrs']['className'] ) ) {
				$block['attrs']['className'] .= $additional_classes;
			} else {
				// If no className attribute exists, create it with the new classes.
				$block['attrs']['className'] = trim( $additional_classes );
			}

			// Inject the new classes into the block's HTML content by modifying the class attribute.
			$block_content = preg_replace( '/<([a-z0-9]+)([^>]*?)class="([^"]*?)"/i', '<$1$2class="$3 ' . esc_attr( trim( $additional_classes ) ) . '"', $block_content, 1 // Limit to the first match to only modify the parent element's class.
			);
		}
	}

	return $block_content;
}

add_filter( 'render_block', 'gbvc_add_visibility_classes', 10, 2 );