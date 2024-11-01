<?php
// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Delete options
function gbvc_delete_options(): void {
	delete_option( 'gbvc_mobile_breakpoint' );
	delete_option( 'gbvc_tablet_breakpoint' );
	delete_option( 'gbvc_disable_styles_on_non_gutenberg_pages' );
}

// Uninstall
function gbvc_uninstall(): void {
	gbvc_delete_options();
}

gbvc_uninstall();