<?php
/**
 * Admin notices.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin
 * @author     ShapedPlugin<support@shapedplugin.com>
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\AdminNotices;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Admin\AdminNotices\AdminNotices' ) ) {
	/**
	 * Backend notices owned by this plugin, plus the block-editor promo that
	 * rides alongside them on the Manage Logo Views screen. Both share the same
	 * screen gate and deep-link flag, so they live together.
	 */
	class AdminNotices {
		/**
		 * Screen the blocks promo appears on (Manage Logo Views).
		 *
		 * @var string
		 */
		const PROMO_SCREEN = 'edit-sp_lc_shortcodes';

		/**
		 * Option recording that the promo was dismissed.
		 *
		 * @var string
		 */
		const PROMO_DISMISSED_OPTION = 'sp_lcfree_blocks_promo_notice_dismissed';

		/**
		 * Nonce action for the dismissal request.
		 *
		 * @var string
		 */
		const PROMO_NONCE = 'sp_lcfree_blocks_promo_notice';

		/**
		 * Wire hooks.
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_notices', array( $this, 'render_blocks_promo_notice' ) );
			add_action( 'admin_footer-edit.php', array( $this, 'print_block_editor_shortcut' ) );
			add_action( 'wp_ajax_sp_lcfree_dismiss_blocks_promo_notice', array( $this, 'dismiss_blocks_promo_notice' ) );
		}

		/**
		 * Load the promo and shortcut styles on the Manage Logo Views screen.
		 *
		 * @return void
		 */
		public function enqueue_styles() {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			$base   = isset( $screen->base ) ? (string) $screen->base : '';
			$type   = isset( $screen->post_type ) ? (string) $screen->post_type : '';

			if ( ! self::is_manage_views_screen( $base, $type ) ) {
				return;
			}

			wp_enqueue_style(
				'sp-lcfree-admin-notices',
				SP_LOGO_CAROUSEL_FREE_URL . 'Admin/assets/css/admin-notices.css',
				array(),
				SP_LOGO_CAROUSEL_FREE_VERSION
			);
		}

		/**
		 * URL that opens a blank new page with the inserter open on our block
		 * category.
		 *
		 * @return string
		 */
		public static function inserter_url() {
			return admin_url( 'post-new.php?post_type=page&lcpro_block_inserter=1' );
		}

		/**
		 * Whether the request is rendering the Manage Logo Views list table.
		 *
		 * Pure, so the gate can be unit-tested without WordPress.
		 *
		 * @param string $base      Screen base.
		 * @param string $post_type Screen post type.
		 * @return bool
		 */
		public static function is_manage_views_screen( $base, $post_type ) {
			return 'edit' === $base && 'sp_lc_shortcodes' === $post_type;
		}

		/**
		 * Offer the block editor beside "Add New" on the Manage Logo Views table.
		 *
		 * The list table exposes no hook between its title and that button, so the
		 * anchor is printed in the footer and moved into place on the client.
		 *
		 * @return void
		 */
		public function print_block_editor_shortcut() {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			$base   = isset( $screen->base ) ? (string) $screen->base : '';
			$type   = isset( $screen->post_type ) ? (string) $screen->post_type : '';

			if ( ! self::is_manage_views_screen( $base, $type ) || ! current_user_can( 'edit_pages' ) ) {
				return;
			}

			$shortcut = sprintf(
				'<a href="%1$s" class="sp-lcpro-block-editor-btn">%2$s</a>',
				esc_url( self::inserter_url() ),
				esc_html__( 'Start With Logo Carousel Blocks', 'logo-carousel-free' )
			);

			wp_print_inline_script_tag(
				sprintf(
					'( function() {
						var addNew = document.querySelector( ".wrap .page-title-action" );
						if ( ! addNew || document.querySelector( ".sp-lcpro-block-editor-btn" ) ) {
							return;
						}
						addNew.insertAdjacentHTML( "afterend", %s );
					}() );',
					wp_json_encode( $shortcut )
				)
			);
		}

		/**
		 * Whether the promo belongs on this screen.
		 *
		 * Pure, so the gate can be unit-tested without WordPress.
		 *
		 * @param string $screen_id Current screen id.
		 * @param bool   $dismissed Whether the notice was dismissed.
		 * @param bool   $can_edit  Whether the user may edit content.
		 * @return bool
		 */
		public static function should_render_promo( $screen_id, $dismissed, $can_edit ) {
			return $can_edit && ! $dismissed && self::PROMO_SCREEN === $screen_id;
		}

		/**
		 * Point authors on the classic Manage Views screen at the block editor.
		 *
		 * @return void
		 */
		public function render_blocks_promo_notice() {
			$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			$screen_id = isset( $screen->id ) ? (string) $screen->id : '';

			if ( ! self::should_render_promo( $screen_id, (bool) get_option( self::PROMO_DISMISSED_OPTION ), current_user_can( 'edit_posts' ) ) ) {
				return;
			}

			$inserter_url = self::inserter_url();
			$patterns_url = admin_url( 'post-new.php?post_type=page&lcpro_pattern_library=1' );
			?>
			<div class="notice sp-lcpro-blocks-promo" data-sp-lcpro-nonce="<?php echo esc_attr( wp_create_nonce( self::PROMO_NONCE ) ); ?>">
				<img
					class="sp-lcpro-blocks-promo__icon"
					src="<?php echo esc_url( SP_LOGO_CAROUSEL_FREE_URL . 'Admin/assets/images/admin-notices.svg' ); ?>"
					alt=""
					width="50"
					height="50"
				/>
				<div class="sp-lcpro-blocks-promo__text">
					<?php
					echo wp_kses(
						sprintf(
							/* translators: %1$s and %2$s are the opening and closing HTML anchor tags for the patterns link. */
							__( 'Good news! We\'ve introduced %1$sLogo Carousel blocks%2$s — build carousel and grid logo showcases directly in the block editor.', 'logo-carousel-free' ),
							'<a class="sp-lcpro-blocks-promo__highlight" href="' . esc_url( $patterns_url ) . '" target="_blank" rel="noopener">',
							'</a>'
						),
						array(
							'strong' => array(),
							'a'      => array(
								'class'  => array(),
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					);
					?>
				</div>
				<div class="sp-lcpro-blocks-promo__actions">
					<a class="sp-lcpro-blocks-promo__cta" href="<?php echo esc_url( $inserter_url ); ?>">
						<?php esc_html_e( 'Try Block Editor', 'logo-carousel-free' ); ?>
						<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M2.25 6h7.5M6.75 2.25 10.5 6 6.75 9.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</a>
					<button type="button" class="sp-lcpro-blocks-promo__dismiss" aria-label="<?php esc_attr_e( 'Dismiss this notice', 'logo-carousel-free' ); ?>">
						<span class="dashicons dashicons-no-alt"></span>
					</button>
				</div>
			</div>
			<script>
				( function() {
					document.addEventListener( 'click', function( event ) {
						var dismiss = event.target.closest( '.sp-lcpro-blocks-promo__dismiss' );
						if ( ! dismiss ) {
							return;
						}
						var notice = dismiss.closest( '.sp-lcpro-blocks-promo' );
						if ( ! notice ) {
							return;
						}
						notice.style.display = 'none';
						var data = new FormData();
						data.append( 'action', 'sp_lcfree_dismiss_blocks_promo_notice' );
						data.append( 'nonce', notice.getAttribute( 'data-sp-lcpro-nonce' ) || '' );
						if ( window.fetch && window.ajaxurl ) {
							fetch( window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: data } );
						}
					} );
				} )();
			</script>
			<?php
		}

		/**
		 * Persist dismissal of the blocks promo, site-wide.
		 *
		 * @return void
		 */
		public function dismiss_blocks_promo_notice() {
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error();
			}

			$nonce = isset( $_POST['nonce'] ) ? sanitize_key( wp_unslash( $_POST['nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, self::PROMO_NONCE ) ) {
				wp_send_json_error();
			}

			update_option( self::PROMO_DISMISSED_OPTION, 1, false );
			wp_send_json_success();
		}
	}
}
