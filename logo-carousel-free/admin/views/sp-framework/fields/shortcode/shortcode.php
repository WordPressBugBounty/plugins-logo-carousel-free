<?php
/**
 * Framework shortcode field file.
 *
 * @link https://shapedplugin.com
 * @since 2.0.0
 *
 * @package Logo_Carousel_Free
 * @subpackage Logo_Carousel_Free/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.

if ( ! class_exists( 'SPLC_FREE_Field_shortcode' ) ) {
	/**
	 *
	 * Field: Shortcode
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	class SPLC_FREE_Field_shortcode extends SPLC_FREE_Fields {

		/**
		 * Field constructor.
		 *
		 * @param array  $field The field type.
		 * @param string $value The values of the field.
		 * @param string $unique The unique ID for the field.
		 * @param string $where To where show the output CSS.
		 * @param string $parent The parent args.
		 */
		public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {

			parent::__construct( $field, $value, $unique, $where, $parent );
		}
		/**
		 * Render
		 *
		 * @return void
		 */
		public function render() {

			$post_id = get_the_ID();
			if ( ! empty( $this->field['shortcode'] ) && 'manage_view' === $this->field['shortcode'] ) {
				echo ( ! empty( $post_id ) ) ? '
				
				<div class="splogocarousel-scode-wrap-side lc_shortcode">
					<p>
					' .
					sprintf(
						/* translators: 1: start link tag, 2: close tag. */
						__( 'To display your logo carousel view, add the following shortcode to your post, custom post types, page, widget, or block editor. If you are adding the logo carousel view to your theme files, additionally include the surrounding PHP code, %1$ssee how%2$s.', 'logo-carousel-free' ),
						'<a href="https://docs.shapedplugin.com/docs/logo-carousel-pro/configurations/how-to-insert-logo-carousel-view-to-your-theme-files-or-other-php-files/" target="_blank">',
						'</a>'
					) . '
					</p>
				
					<div class="lc_shortcode_content">
						<div class="shortcode-wrap">
							<div class="lc-after-copy-text">
								<i class="fa fa-check-circle"></i> ' . esc_html__( 'Shortcode Copied to Clipboard!', 'logo-carousel-free' ) . ' 
							</div>
							<div class="lc-sc-code selectable">[logocarousel id="' . esc_attr( $post_id ) . '"]</div>
						</div>
					</div>

				</div>
				
				' : '';
			} else {
				echo ( ! empty( $post_id ) ) ? '
				<div class="splogocarousel-scode-wrap-side">
					<p>
						' .
							sprintf(
								/* translators: 1: start strong tag, 2: close tag. */
								__( 'Logo Carousel has seamless integration with Gutenberg, Classic Editor, %1$sElementor%2$s, Divi, Bricks, Beaver, Oxygen, WPBakery Builder, etc.', 'logo-carousel-free' ),
								'<strong>',
								'</strong>'
							)
						. '
					</p>
				</div>
				' : '';
			}
		}
	}
}
