<?php
/**
 *
 * Field: Preview
 *
 * @link       https://shapedplugin.com/
 *
 * @package    Logo_Carousel_Free
 * @subpackage Logo_Carousel_Free/sp-framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.

/**
 *
 * Field: Preview
 *
 * @since 3.4.0
 */
if ( ! class_exists( 'SPLC_FREE_Field_preview' ) ) {
	/**
	 *
	 * Field: preview
	 *
	 * @since 3.4.0
	 */
	class SPLC_FREE_Field_preview extends SPLC_FREE_Fields {

		/**
		 * The class constructor.
		 *
		 * @param array  $field The field type.
		 * @param string $value The values of the field.
		 * @param string $unique The unique ID for the field.
		 * @param string $where To where show the output CSS.
		 * @param string $parent The parent args.
		 */
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
		 * Render.
		 *
		 * @return void
		 */
		/**
		 * Render
		 *
		 * @return void
		 */
		public function render() {
			echo '<div class="splcp-preview-box"><div id="splcp-preview-box"></div></div>';
		}
	}
}
