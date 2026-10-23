<?php
/**
 * Normalized block config value object.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BlockConfig' ) ) {
	/**
	 * Lightweight config container for block rendering.
	 */
	class BlockConfig {
		/**
		 * Block slug.
		 *
		 * @var string
		 */
		private $slug;

		/**
		 * Normalized heading.
		 *
		 * @var string
		 */
		private $heading;

		/**
		 * Normalized caption.
		 *
		 * @var string
		 */
		private $caption;

		/**
		 * Block unique ID.
		 *
		 * @var string
		 */
		private $unique_id;

		/**
		 * Raw normalized attributes.
		 *
		 * @var array
		 */
		private $attributes;

		/**
		 * Normalized logo images settings.
		 *
		 * @var array
		 */
		private $images_settings;

		/**
		 * Derived logo images flags.
		 *
		 * @var array
		 */
		private $images_flags;

		/**
		 * Normalized logo images style values.
		 *
		 * @var array
		 */
		private $logo_images_style;

		/**
		 * Normalized Swiper config values.
		 *
		 * @var array
		 */
		private $swiper_config;

		/**
		 * Whether the block uses the Swiper engine (config + render shell).
		 *
		 * @var bool
		 */
		private $uses_swiper;

		/**
		 * Constructor.
		 *
		 * @param string $slug Block slug.
		 * @param string $heading Heading.
		 * @param string $caption Caption.
		 * @param string $unique_id Unique ID.
		 * @param array  $attributes Normalized attributes.
		 * @param array  $images_settings Normalized logo images settings.
		 * @param array  $images_flags Derived logo images flags.
		 * @param array  $logo_images_style Normalized logo images style.
		 * @param array  $swiper_config Normalized Swiper config.
		 * @param bool   $uses_swiper Whether the block uses the Swiper engine. Defaults to true for back-compat.
		 */
		public function __construct( $slug, $heading, $caption, $unique_id, array $attributes, array $images_settings = array(), array $images_flags = array(), array $logo_images_style = array(), array $swiper_config = array(), $uses_swiper = true ) {
			$this->slug              = $slug;
			$this->heading           = $heading;
			$this->caption           = $caption;
			$this->unique_id         = $unique_id;
			$this->attributes        = $attributes;
			$this->images_settings   = $images_settings;
			$this->images_flags      = $images_flags;
			$this->logo_images_style = $logo_images_style;
			$this->swiper_config     = $swiper_config;
			$this->uses_swiper       = (bool) $uses_swiper;
		}

		/**
		 * Return the block slug.
		 *
		 * @return string
		 */
		public function get_slug() {
			return $this->slug;
		}

		/**
		 * Return the heading.
		 *
		 * @return string
		 */
		public function get_heading() {
			return $this->heading;
		}

		/**
		 * Return the caption.
		 *
		 * @return string
		 */
		public function get_caption() {
			return $this->caption;
		}

		/**
		 * Return the unique ID.
		 *
		 * @return string
		 */
		public function get_unique_id() {
			return $this->unique_id;
		}

		/**
		 * Return normalized attributes.
		 *
		 * @return array
		 */
		public function get_attributes() {
			return $this->attributes;
		}

		/**
		 * Return normalized logo images settings.
		 *
		 * @return array
		 */
		public function get_images_settings() {
			return $this->images_settings;
		}

		/**
		 * Return derived logo images flags.
		 *
		 * @return array
		 */
		public function get_images_flags() {
			return $this->images_flags;
		}

		/**
		 * Return normalized logo images style values.
		 *
		 * @return array
		 */
		public function get_logo_images_style() {
			return $this->logo_images_style;
		}

		/**
		 * Return normalized Swiper config values.
		 *
		 * @return array
		 */
		public function get_swiper_config() {
			return $this->swiper_config;
		}

		/**
		 * Whether the block uses the Swiper engine (config payload + render shell).
		 *
		 * Non-Swiper family blocks (e.g. Grid) reuse the shared source and item
		 * renderers but supply their own shell/runtime; they set this false so no
		 * Swiper config or Swiper markup is emitted.
		 *
		 * @return bool
		 */
		public function uses_swiper() {
			return $this->uses_swiper;
		}

		/**
		 * Whether the block has at least one logo image.
		 *
		 * @return bool
		 */
		public function has_images() {
			return ! empty( $this->images_flags['has_images'] );
		}
	}
}
