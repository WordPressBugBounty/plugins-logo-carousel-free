<?php
/**
 * Uninstall.php for cleaning plugin database.
 *
 * Trigger the file when plugin is deleted.
 *
 * @see delete_option(), delete_post_meta_key()
 * @since 3.3.0
 * @package logo Carousel free
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Free's own diagnostic-data events (Pro uses different hook names), so
// they go even when Pro's data is kept below.
wp_clear_scheduled_hook( 'logo_carousel_free_weekly_scheduled_events' );
wp_clear_scheduled_hook( 'lcfree_send_diagnostic_data' );

/*
 * Logo Carousel Pro shares this plugin's posts, options and blocks. While Pro
 * is installed, deleting the free plugin must never touch that data.
 */
if ( file_exists( WP_PLUGIN_DIR . '/logo-carousel-pro/logo-carousel-pro.php' ) ) {
	return;
}

/**
 * Delete plugin data function.
 *
 * @return void
 */
function sp_lcp_delete_plugin_data() {
	global $wpdb;

	// Delete plugin option settings.
	$option_name = '_sp_lcpro_options';
	delete_option( $option_name );
	delete_site_option( $option_name ); // For site options in Multisite.

	// Delete carousel and saved-template post types.
	$carousel_posts = get_posts(
		array(
			'numberposts' => 10000,
			'post_type'   => array( 'sp_logo_carousel', 'sp_lc_shortcodes', 'sp_lc_template' ),
			'post_status' => 'any',
		)
	);
	foreach ( $carousel_posts as $post ) {
		wp_delete_post( $post->ID, true );
	}

	// Delete Carousel post meta.
	delete_post_meta_by_key( 'sp_lcp_layout_options' );
	delete_post_meta_by_key( 'sp_lcp_shortcode_options' );
	delete_post_meta_by_key( 'sp_logo_carousel_link_option' );

	// Delete offer banner related option keys.
	delete_option( 'shapedplugin_offer_banner_dismissed_new_year_2026' );
	delete_option( 'shapedplugin_offer_banner_dismissed_black_friday_2025' );
	delete_option( 'sp_lcfree_blocks_promo_notice_dismissed' );

	// Block system (4.0.0+): dashboard, setup wizard, AI and Ready Patterns state.
	$block_options = array(
		'sp_lcpro_modules_visibility',
		'sp_lcpro_blocks_visibility',
		'lcpro_activation_redirect',
		'sp_lcpro_ai_settings',
		'sp_lcpro_rp_payload_generation',
		'sp_lcpro_ready_patterns_favorites',
		'sp_lcpro_placements_report',
		'sp_lcpro_widgets_modified',
	);
	foreach ( $block_options as $block_option ) {
		delete_option( $block_option );
	}

	delete_transient( 'lcpro_changelogs' );
	delete_transient( 'sp_lcpro_ready_patterns_manifest_free' );

	// Ready Patterns manifest file cache. The folder is shared with Pro, so
	// only the free plugin's file goes, and the folder only when left empty.
	$upload_dir  = wp_upload_dir( null, false );
	$uploads_dir = ! empty( $upload_dir['basedir'] ) ? $upload_dir['basedir'] : WP_CONTENT_DIR . '/uploads';
	$cache_dir   = trailingslashit( $uploads_dir ) . 'logo-carousel-pro';
	$cache_file  = $cache_dir . '/ready-patterns-free.json';
	if ( file_exists( $cache_file ) ) {
		wp_delete_file( $cache_file );
	}
	if ( is_dir( $cache_dir ) && 2 === count( (array) scandir( $cache_dir ) ) ) {
		rmdir( $cache_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
	}

	delete_metadata( 'user', 0, 'sp_lcpro_ready_patterns_favorites', '', true );

	// Prefixed transients and upload locks (one row per key, so matched by prefix).
	$prefixes = array(
		'_transient_sp_lcpro_rp_payload_',
		'_transient_timeout_sp_lcpro_rp_payload_',
		'_transient_sp_lcpro_ai_connector_',
		'_transient_timeout_sp_lcpro_ai_connector_',
		'_sp_lcpro_hash_lock_',
	);
	foreach ( $prefixes as $prefix ) {
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $prefix ) . '%'
			)
		);
	}
}

	// Load lc file.
	require plugin_dir_path( __FILE__ ) . '/main.php';
	$sp_lc_plugin_settings = get_option( '_sp_lcpro_options' );
	$delate_plugin_data    = isset( $sp_lc_plugin_settings['lcpro_data_remove'] ) ? $sp_lc_plugin_settings['lcpro_data_remove'] : false;

if ( $delate_plugin_data ) {
	sp_lcp_delete_plugin_data();
}
