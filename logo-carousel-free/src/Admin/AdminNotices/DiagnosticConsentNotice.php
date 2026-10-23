<?php
/**
 * Diagnostic-data consent notice.
 *
 * A one-time admin notice asking sites that use the plugin and have not opted
 * in to share non-sensitive diagnostic data. Appears seven days after it first
 * qualifies, so a fresh install is not asked straight away; accepting or
 * dismissing hides it for good. Same notice and timing as Logo Carousel Pro,
 * but Free has no licence to gate on, so it waits for a published showcase.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin
 * @author     ShapedPlugin<support@shapedplugin.com>
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\AdminNotices;

use ShapedPlugin\LogoCarouselFree\Admin\Dashboard\BlockUsage;
use ShapedPlugin\LogoCarouselFree\Admin\Dashboard\DiagnosticData;
use ShapedPlugin\LogoCarouselFree\Includes\SavedTemplates;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Admin\AdminNotices\DiagnosticConsentNotice' ) ) {

	/**
	 * Renders the consent notice and records the answer.
	 */
	class DiagnosticConsentNotice {

		/**
		 * Plugin settings option the consent answer and notice state live in.
		 *
		 * @var string
		 */
		const OPTION = '_sp_lcpro_options';

		/**
		 * Settings key: when the notice first qualified (Unix time).
		 *
		 * @var string
		 */
		const START_KEY = 'consent_notice_start_time';

		/**
		 * Settings key: the notice was dismissed without accepting.
		 *
		 * @var string
		 */
		const DISMISSED_KEY = 'consent_notice_dismissed';

		/**
		 * Days between the notice first qualifying and it appearing.
		 *
		 * @var int
		 */
		const DELAY_DAYS = 7;

		/**
		 * Form field and nonce action for the two buttons.
		 *
		 * @var string
		 */
		const ACTION = 'lcfree_diagnostic_consent_action';

		/**
		 * Nonce field name.
		 *
		 * @var string
		 */
		const NONCE = 'lcfree_diagnostic_consent_nonce';

		/**
		 * Wire hooks.
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'handle_submit' ) );
			add_action( 'admin_notices', array( $this, 'maybe_render' ) );
		}

		/**
		 * Whether the notice applies to this site at all, delay aside.
		 *
		 * Pure. Hidden once the admin has opted in or dismissed it, and shown
		 * only to sites that actually use the plugin.
		 *
		 * @param array $settings The `_sp_lcpro_options` value.
		 * @param bool  $in_use   Whether the site has a published showcase.
		 * @return bool
		 */
		public static function is_eligible( array $settings, $in_use ) {
			if ( DiagnosticData::has_consent( $settings ) || ! empty( $settings[ self::DISMISSED_KEY ] ) ) {
				return false;
			}

			return (bool) $in_use;
		}

		/**
		 * Whether the delay since the notice first qualified has passed.
		 *
		 * Pure.
		 *
		 * @param int|null $start Unix time it first qualified, or null.
		 * @param int      $now   Current Unix time.
		 * @param int      $days  Delay in days.
		 * @return bool
		 */
		public static function delay_passed( $start, $now, $days = self::DELAY_DAYS ) {
			if ( empty( $start ) ) {
				return false;
			}

			return ( (int) $now - (int) $start ) >= DAY_IN_SECONDS * (int) $days;
		}

		/**
		 * Show the notice when it applies and its delay has passed.
		 *
		 * @return void
		 */
		public function maybe_render() {
			if ( ! current_user_can( apply_filters( 'sp_lc_ui_permission', 'manage_options' ) ) ) {
				return;
			}

			$settings = $this->settings();

			if ( ! self::is_eligible( $settings, $this->site_uses_plugin() ) ) {
				return;
			}

			// The clock starts the first time the notice qualifies.
			if ( empty( $settings[ self::START_KEY ] ) ) {
				$this->update_setting( self::START_KEY, time() );
				return;
			}

			if ( ! self::delay_passed( $settings[ self::START_KEY ], time() ) ) {
				return;
			}

			$this->render();
		}

		/**
		 * Record the notice's answer, then reload the page so a refresh
		 * cannot resubmit it.
		 *
		 * @return void
		 */
		public function handle_submit() {
			if ( ! isset( $_POST[ self::ACTION ] ) ) {
				return;
			}

			check_admin_referer( self::ACTION, self::NONCE );

			if ( ! current_user_can( apply_filters( 'sp_lc_ui_permission', 'manage_options' ) ) ) {
				wp_die( esc_html__( 'Unauthorized.', 'logo-carousel-free' ), 403 );
			}

			$choice = sanitize_key( wp_unslash( $_POST[ self::ACTION ] ) );

			if ( 'allow' === $choice ) {
				DiagnosticData::record_consent( true );
			} elseif ( 'deny' === $choice ) {
				$this->update_setting( self::DISMISSED_KEY, true );
			}

			$referer = wp_get_referer();
			wp_safe_redirect( $referer ? $referer : admin_url() );
			exit;
		}

		/**
		 * Whether the site has a published shortcode view, saved template or
		 * Logo Carousel block. Reads counts and the cached placements report
		 * only, so it never scans content on an admin page load.
		 *
		 * @return bool
		 */
		private function site_uses_plugin() {
			foreach ( array( 'sp_lc_shortcodes', SavedTemplates::POST_TYPE ) as $post_type ) {
				$counts = wp_count_posts( $post_type );
				if ( isset( $counts->publish ) && (int) $counts->publish > 0 ) {
					return true;
				}
			}

			return true === BlockUsage::stored_has_showcases();
		}

		/**
		 * The stored plugin settings.
		 *
		 * @return array
		 */
		private function settings() {
			$settings = get_option( self::OPTION, array() );
			return is_array( $settings ) ? $settings : array();
		}

		/**
		 * Set one key in the plugin settings.
		 *
		 * @param string $key   Settings key.
		 * @param mixed  $value Value.
		 * @return void
		 */
		private function update_setting( $key, $value ) {
			$settings         = $this->settings();
			$settings[ $key ] = $value;
			update_option( self::OPTION, $settings );
		}

		/**
		 * Print the notice.
		 *
		 * @return void
		 */
		private function render() {
			?>
			<style>
				.lcfree-consent-notice {
					position: relative;
					display: flex;
					align-items: flex-start;
					gap: 16px;
					margin-bottom: 20px;
					padding: 14px;
					border: 1px solid #ccc;
					border-left: 4px solid #2271b1;
					border-radius: 4px;
					background-color: #fff;
					box-shadow: 0 16px 32px -4px rgba(12, 12, 13, 0.05), 0 4px 4px -4px rgba(12, 12, 13, 0.02);
				}
				.lcfree-consent-notice img {
					width: 52px;
					border-radius: 4px;
				}
				.lcfree-consent-notice-wrapper {
					display: flex;
					gap: 26px;
				}
				.lcfree-consent-notice h3 {
					margin: 0 0 8px;
					color: #2c2d2f;
					font-size: 18px;
					font-weight: 600;
				}
				.lcfree-consent-notice p,
				.lcfree-consent-notice a {
					margin: 0 0 2px;
					color: #6e6f72;
					font-size: 14px;
				}
				.lcfree-consent-notice a {
					text-decoration: underline;
				}
				.lcfree-consent-notice .lcfree-consent-notice-accept {
					margin-top: 8px;
					border: 1px solid #ecedf0;
					border-radius: 4px;
					background-color: #1e1e1e;
					color: #fff;
					font-size: 13px;
					font-weight: 500;
					line-height: 14px;
					transition: all 0.2s ease;
				}
				.lcfree-consent-notice .lcfree-consent-notice-accept:hover,
				.lcfree-consent-notice .lcfree-consent-notice-accept:focus {
					border-color: #b7b8bb;
					background-color: #2e2e2e;
					box-shadow: none;
					color: #fff;
				}
				.lcfree-consent-notice .lcfree-consent-notice-dismiss {
					position: absolute;
					top: 0;
					right: 7px;
					border: none;
					background: transparent;
					color: #b6b6b6;
					font-size: 16px;
					cursor: pointer;
				}
			</style>
			<div class="notice notice-info lcfree-consent-notice">
				<div class="lcfree-consent-notice-wrapper">
					<div>
						<h3><?php esc_html_e( 'Help us make Logo Carousel even more awesome?', 'logo-carousel-free' ); ?></h3>
						<p>
							<?php esc_html_e( 'Allow us to collect non-sensitive diagnostic data to resolve problems faster and improve performance.', 'logo-carousel-free' ); ?>
							<a href="https://shapedplugin.com/information-we-collect/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn More', 'logo-carousel-free' ); ?></a>
						</p>
					</div>
					<div style="display:flex; gap:10px;">
						<form method="post" style="display:inline;">
							<?php wp_nonce_field( self::ACTION, self::NONCE ); ?>
							<input type="hidden" name="<?php echo esc_attr( self::ACTION ); ?>" value="allow" />
							<button type="submit" class="button lcfree-consent-notice-accept">
								<?php esc_html_e( 'Accept', 'logo-carousel-free' ); ?>
							</button>
						</form>
						<form method="post" style="display:inline;">
							<?php wp_nonce_field( self::ACTION, self::NONCE ); ?>
							<input type="hidden" name="<?php echo esc_attr( self::ACTION ); ?>" value="deny" />
							<button type="submit" class="lcfree-consent-notice-dismiss dashicons dashicons-dismiss" aria-label="<?php esc_attr_e( 'Dismiss', 'logo-carousel-free' ); ?>"></button>
						</form>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
