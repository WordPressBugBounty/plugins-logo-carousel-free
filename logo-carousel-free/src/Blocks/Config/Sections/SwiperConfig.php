<?php
/**
 * Shared Swiper config normalizer.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections;

use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase\SwiperLayoutProfileResolver;
use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase\SwiperModulesConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Config\Sections\LogoShowcase\SwiperPresentationConfig;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\LayoutDefaults;
use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CssUtils;
use ShapedPlugin\LogoCarouselFree\Blocks\Support\ResponsiveCount;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'SwiperConfig' ) ) {
	/**
	 * Normalize carousel layout attributes into a Swiper-ready config model.
	 */
	class SwiperConfig implements SectionConfigInterface {
		/**
		 * Build the canonical Swiper config payload.
		 *
		 * @param array<string, mixed> $attributes Raw block attributes.
		 * @return array<string, mixed>
		 */
		public function normalize( array $attributes ) {
			$defaults                            = LayoutDefaults::get_layout_defaults();
			$layout                              = isset( $attributes['layout'] ) && is_array( $attributes['layout'] ) ? $attributes['layout'] : array();
			$raw_layout                          = $layout;
			$style                               = $this->normalize_enum(
				isset( $layout['carouselStyle'] ) ? $layout['carouselStyle'] : null,
				array( 'standard', 'staggered-carousel', 'center', 'multirow' ),
				$defaults['carouselStyle']
			);
			$layout                              = ( new SwiperLayoutProfileResolver() )->resolve( $layout, $style );
			$behavior                            = array_replace_recursive( $defaults, $layout );
			$behavior['carouselStyle']           = $style;
			$behavior['displayOrientation']      = $this->normalize_enum(
				isset( $layout['displayOrientation'] ) ? $layout['displayOrientation'] : null,
				array( 'horizontal', 'vertical' ),
				$defaults['displayOrientation']
			);
			$behavior['centeredLogoScale']       = $this->normalize_number(
				isset( $layout['centeredLogoScale'] ) ? $layout['centeredLogoScale'] : null,
				isset( $defaults['center']['centeredLogoScale'] ) ? $defaults['center']['centeredLogoScale'] : 1.2,
				1,
				2
			);
			$behavior['slideLogosOpacity']       = $this->normalize_number(
				isset( $layout['slideLogosOpacity'] ) ? $layout['slideLogosOpacity'] : null,
				isset( $defaults['center']['slideLogosOpacity'] ) ? $defaults['center']['slideLogosOpacity'] : 0.5,
				0,
				1
			);
			$behavior['partialSlideView']        = $this->normalize_boolean(
				isset( $layout['partialSlideView'] ) ? $layout['partialSlideView'] : null,
				$defaults['partialSlideView']
			);
			$behavior['variableWidth']           = $this->normalize_boolean(
				isset( $layout['variableWidth'] ) ? $layout['variableWidth'] : null,
				$defaults['variableWidth']
			);
			// Columns and Rows are responsive. `columns`/`rows` stay the Desktop
			// integers every existing reader expects; the raw branch rides along
			// so `build_breakpoints()` can ask whether the author set a value for
			// Tablet or Mobile — when they did not, the caps this config has
			// always derived for small screens still apply, so existing content
			// keeps rendering exactly as it did.
			$behavior['columnsRaw']              = isset( $layout['columns'] ) ? $layout['columns'] : $defaults['columns'];
			$behavior['rowsRaw']                 = isset( $layout['rows'] ) ? $layout['rows'] : $defaults['rows'];
			$behavior['columns']                 = ResponsiveCount::resolve(
				$behavior['columnsRaw'],
				'Desktop',
				ResponsiveCount::resolve( $defaults['columns'], 'Desktop', 5, 1, 50 ),
				1,
				50
			);
			$behavior['rows']                    = ResponsiveCount::resolve(
				$behavior['rowsRaw'],
				'Desktop',
				ResponsiveCount::resolve( $defaults['rows'], 'Desktop', 5, 1, 50 ),
				1,
				50
			);
			$behavior['gapBetweenLogos']         = $this->normalize_gap_between_logos(
				isset( $layout['gapBetweenLogos'] ) && is_array( $layout['gapBetweenLogos'] ) ? $layout['gapBetweenLogos'] : array(),
				$defaults['gapBetweenLogos']
			);
			$behavior['autoplay']                = $this->normalize_boolean(
				isset( $layout['autoplay'] ) ? $layout['autoplay'] : null,
				$defaults['autoplay']
			);
			$behavior['autoplayDelay']           = $this->normalize_positive_integer(
				isset( $layout['autoplayDelay'] ) ? $layout['autoplayDelay'] : null,
				$defaults['autoplayDelay']
			);
			$behavior['sliderSpeed']             = $this->normalize_positive_integer(
				isset( $layout['sliderSpeed'] ) ? $layout['sliderSpeed'] : null,
				$defaults['sliderSpeed']
			);
			$behavior['hasCustomSliderSpeed']    = $this->has_custom_slider_speed( $raw_layout, $style );
			$behavior['pauseOnHover']            = $this->normalize_boolean(
				isset( $layout['pauseOnHover'] ) ? $layout['pauseOnHover'] : null,
				$defaults['pauseOnHover']
			);
			$behavior['carouselDirection']       = $this->normalize_enum(
				isset( $layout['carouselDirection'] ) ? $layout['carouselDirection'] : null,
				array( 'right-to-left', 'left-to-right', 'bottom-to-top', 'top-to-bottom' ),
				$defaults['carouselDirection']
			);
			$behavior['slideToScroll']           = $this->normalize_slide_to_scroll(
				isset( $layout['slideToScroll'] ) && is_array( $layout['slideToScroll'] ) ? $layout['slideToScroll'] : array(),
				$defaults['slideToScroll']
			);
			$behavior['slidingEffects']          = $this->normalize_enum(
				isset( $layout['slidingEffects'] ) ? $layout['slidingEffects'] : null,
				array( 'none', 'slide', 'ease-in-out-slide', 'spring-bounce', 'snappy', 'fade', 'slide-fade' ),
				$defaults['slidingEffects']
			);
			$behavior['adaptiveHeight']          = $this->normalize_boolean(
				isset( $layout['adaptiveHeight'] ) ? $layout['adaptiveHeight'] : null,
				$defaults['adaptiveHeight']
			);
			$behavior['infiniteLoop']            = $this->normalize_boolean(
				isset( $layout['infiniteLoop'] ) ? $layout['infiniteLoop'] : null,
				$defaults['infiniteLoop']
			);
			$behavior['carouselStartsOnScreen']  = $this->normalize_boolean(
				isset( $layout['carouselStartsOnScreen'] ) ? $layout['carouselStartsOnScreen'] : null,
				$defaults['carouselStartsOnScreen']
			);
			$behavior['tabsAndKeyNavigation']    = $this->normalize_boolean(
				isset( $layout['tabsAndKeyNavigation'] ) ? $layout['tabsAndKeyNavigation'] : null,
				$defaults['tabsAndKeyNavigation']
			);
			$behavior['mouseWheelControl']       = $this->normalize_boolean(
				isset( $layout['mouseWheelControl'] ) ? $layout['mouseWheelControl'] : null,
				$defaults['mouseWheelControl']
			);
			$behavior['freeScrollMode']          = $this->normalize_boolean(
				isset( $layout['freeScrollMode'] ) ? $layout['freeScrollMode'] : null,
				$defaults['freeScrollMode']
			);
			$behavior['navigationArrows']        = $this->normalize_boolean(
				isset( $layout['navigationArrows'] ) ? $layout['navigationArrows'] : null,
				$defaults['navigationArrows']
			);
			$behavior['paginationDots']          = $this->normalize_boolean(
				isset( $layout['paginationDots'] ) ? $layout['paginationDots'] : null,
				$defaults['paginationDots']
			);
			$behavior['ajaxLiveFilter']          = $this->normalize_boolean(
				isset( $layout['ajaxLiveFilter'] ) ? $layout['ajaxLiveFilter'] : null,
				$defaults['ajaxLiveFilter']
			);
			$behavior['ajaxSearch']              = $this->normalize_boolean(
				isset( $layout['ajaxSearch'] ) ? $layout['ajaxSearch'] : null,
				$defaults['ajaxSearch']
			);
			$behavior['filterSearchOrientation'] = $this->normalize_enum(
				isset( $layout['filterSearchOrientation'] ) ? $layout['filterSearchOrientation'] : null,
				array( 'inline', 'stacked' ),
				$defaults['filterSearchOrientation']
			);
			$behavior['filterSearchGap']         = $this->normalize_gap_between_logos(
				isset( $layout['filterSearchGap'] ) && is_array( $layout['filterSearchGap'] ) ? $layout['filterSearchGap'] : array(),
				$defaults['filterSearchGap']
			);
			$behavior['justifyFilterSearch']     = $this->normalize_enum(
				isset( $layout['justifyFilterSearch'] ) ? $layout['justifyFilterSearch'] : null,
				array( 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly' ),
				$defaults['justifyFilterSearch']
			);
			$behavior['isVertical']              = 'vertical' === $behavior['displayOrientation'];
			$behavior['isStandard']              = 'standard' === $behavior['carouselStyle'];
			$behavior['isCenter']                = 'center' === $behavior['carouselStyle'];
			$behavior['languageDirection']       = ! $behavior['isVertical'] && 'right-to-left' === $behavior['carouselDirection'] ? 'rtl' : 'ltr';
			$behavior['reverseDirection']        = 'top-to-bottom' === $behavior['carouselDirection'];

			$pagination        = isset( $attributes['pagination'] ) && is_array( $attributes['pagination'] ) ? $attributes['pagination'] : array();
			$pagination_style  = isset( $pagination['paginationStyle'] ) ? (string) $pagination['paginationStyle'] : 'style-1';
			$allowed_pg_styles = array( 'style-1', 'style-2', 'style-3', 'style-4', 'style-5', 'style-6' );
			if ( ! in_array( $pagination_style, $allowed_pg_styles, true ) ) {
				$pagination_style = 'style-1';
			}

			$swiper  = $this->build_swiper_options( $behavior, $pagination_style );
			$modules = ( new SwiperModulesConfig() )->build( $swiper );

			return array(
				'behavior'     => $behavior,
				'swiper'       => $swiper,
				'modules'      => $modules,
				'presentation' => ( new SwiperPresentationConfig() )->build( $behavior ),
				'ui'           => array(
					'showNavigation' => ! empty( $behavior['navigationArrows'] ),
					'showPagination' => ! empty( $behavior['paginationDots'] ),
					'showAutoplay'   => ! empty( $behavior['autoplay'] ),
				),
			);
		}

		/**
		 * Resolve the profile key for a carousel style.
		 *
		 * @param string $style Carousel style.
		 * @return string
		 */
		private function get_profile_key( $style ) {
			switch ( $style ) {
				case 'staggered-carousel':
					return 'staggered';
				case 'center':
					return 'center';
				case 'multirow':
					return 'multirow';
				default:
					return 'standard';
			}
		}

		/**
		 * Build the runtime Swiper options.
		 *
		 * @param array<string, mixed> $behavior Normalized behavior state.
		 * @param string               $pagination_style Pagination style ID (style-5 → scrollbar).
		 * @return array<string, mixed>
		 */
		private function build_swiper_options( array $behavior, $pagination_style = 'style-1' ) {
			$breakpoints              = $this->build_breakpoints( $behavior );
			$effective_variable_width = $this->supports_variable_width( $behavior );
			$centered_preview         = ! empty( $behavior['isCenter'] ) || ( $effective_variable_width && ! empty( $behavior['partialSlideView'] ) );
			$uses_overlay_fade        = in_array( $behavior['slidingEffects'], array( 'fade', 'slide-fade' ), true );
			$supports_loop            = ! empty( $behavior['infiniteLoop'] );
			$uses_auto_grouped_fade   = $effective_variable_width && $uses_overlay_fade;
			$use_scrollbar            = ! empty( $behavior['paginationDots'] ) && 'style-5' === $pagination_style;

			$swiper = array(
				'direction'                => $behavior['isVertical'] ? 'vertical' : 'horizontal',
				'slidesPerView'            => isset( $breakpoints[0]['slidesPerView'] ) ? $breakpoints[0]['slidesPerView'] : 1.2,
				'slidesPerGroup'           => isset( $breakpoints[0]['slidesPerGroup'] ) ? $breakpoints[0]['slidesPerGroup'] : 1,
				'slidesPerGroupAuto'       => $uses_auto_grouped_fade,
				'spaceBetween'             => isset( $breakpoints[0]['spaceBetween'] ) ? $breakpoints[0]['spaceBetween'] : 15,
				'breakpoints'              => $breakpoints,
				'centeredSlides'           => $centered_preview,
				'centeredSlidesBounds'     => $centered_preview,
				'centerInsufficientSlides' => $centered_preview,
				'loop'                     => $uses_overlay_fade ? false : $supports_loop,
				'rewind'                   => $uses_overlay_fade ? ! empty( $behavior['infiniteLoop'] ) : false,
				'loopAddBlankSlides'       => false,
				'autoHeight'               => ! empty( $behavior['adaptiveHeight'] ) && empty( $behavior['isVertical'] ),
				'watchOverflow'            => false,
				'watchSlidesProgress'      => true,
				'resizeObserver'           => true,
				'observer'                 => true,
				'observeParents'           => true,
				'allowTouchMove'           => true,
				'grabCursor'               => $behavior['isCenter'] || $effective_variable_width,
				'roundLengths'             => true,
				'speed'                    => $this->resolve_speed(
					$behavior['sliderSpeed'],
					$behavior['slidingEffects'],
					! empty( $behavior['hasCustomSliderSpeed'] )
				),
				'effect'                   => 'slide',
				'autoplay'                 => array(
					'enabled'              => ! empty( $behavior['autoplay'] ),
					'delay'                => $behavior['autoplayDelay'],
					'disableOnInteraction' => false,
					'pauseOnMouseEnter'    => ! empty( $behavior['pauseOnHover'] ),
					'reverseDirection'     => ! empty( $behavior['isVertical'] ) && ! empty( $behavior['reverseDirection'] ),
				),
				'navigation'               => array(
					'enabled' => ! empty( $behavior['navigationArrows'] ),
				),
				'pagination'               => array(
					'enabled'   => ! empty( $behavior['paginationDots'] ) && ! $use_scrollbar,
					'clickable' => true,
					'type'      => 'bullets',
				),
				'scrollbar'                => array(
					'enabled'   => $use_scrollbar,
					'draggable' => true,
					'hide'      => false,
				),
				'keyboard'                 => array(
					'enabled'        => ! empty( $behavior['tabsAndKeyNavigation'] ),
					'onlyInViewport' => true,
				),
				// forceToAxis MUST stay false (mirrors swiperConfig.js). Swiper's
				// mousewheel handler bails out when the dominant wheel delta is
				// off-axis, so on a horizontal carousel a normal mouse wheel
				// (deltaY only) would be ignored and the toggle would look dead.
				'mousewheel'               => array(
					'enabled'        => ! empty( $behavior['mouseWheelControl'] ),
					'forceToAxis'    => false,
					'releaseOnEdges' => true,
				),
				'freeMode'                 => array(
					'enabled'        => ! empty( $behavior['freeScrollMode'] ),
					'sticky'         => false,
					'momentumBounce' => false,
				),
				'a11y'                     => array(
					'enabled' => true,
				),
			);

			return $swiper;
		}

		/**
		 * Build responsive breakpoints.
		 *
		 * @param array<string, mixed> $behavior Normalized behavior state.
		 * @return array<int, array<string, mixed>>
		 */
		private function build_breakpoints( array $behavior ) {
			$space_between          = array(
				'mobile'  => $this->resolve_device_gap( $behavior['gapBetweenLogos'], 'Mobile', 15 ),
				'tablet'  => $this->resolve_device_gap( $behavior['gapBetweenLogos'], 'Tablet', 15 ),
				'desktop' => $this->resolve_device_gap( $behavior['gapBetweenLogos'], 'Desktop', 15 ),
			);
			$slides_to_scroll       = array(
				'mobile'  => $this->resolve_device_scroll( $behavior['slideToScroll'], 'Mobile', 1 ),
				'tablet'  => $this->resolve_device_scroll( $behavior['slideToScroll'], 'Tablet', 1 ),
				'desktop' => $this->resolve_device_scroll( $behavior['slideToScroll'], 'Desktop', 1 ),
			);
			$breakpoints            = array();
			$use_overlay_fade       = in_array( $behavior['slidingEffects'], array( 'fade', 'slide-fade' ), true );

			if ( $this->supports_variable_width( $behavior ) ) {
				$breakpoints = array(
					0   => array(
						'slidesPerView'  => 'auto',
						'slidesPerGroup' => $use_overlay_fade ? $this->resolve_fade_group_size( 'auto' ) : $slides_to_scroll['mobile'],
						'spaceBetween'   => $space_between['mobile'],
					),
					601 => array(
						'slidesPerView'  => 'auto',
						'slidesPerGroup' => $use_overlay_fade ? $this->resolve_fade_group_size( 'auto' ) : $slides_to_scroll['tablet'],
						'spaceBetween'   => $space_between['tablet'],
					),
					901 => array(
						'slidesPerView'  => 'auto',
						'slidesPerGroup' => $use_overlay_fade ? $this->resolve_fade_group_size( 'auto' ) : $slides_to_scroll['desktop'],
						'spaceBetween'   => $space_between['desktop'],
					),
				);
			} elseif ( ! empty( $behavior['isVertical'] ) ) {
				$visible_rows        = max( 1, (int) $behavior['rows'] );
				$rows_raw            = isset( $behavior['rowsRaw'] ) ? $behavior['rowsRaw'] : null;
				$is_partial          = ! empty( $behavior['partialSlideView'] );
				$mobile_slides_view  = $this->device_slides_per_view(
					ResponsiveCount::device( $rows_raw, 'Mobile' ),
					$is_partial,
					$is_partial
						? $this->resolve_partial_slides_per_view( $visible_rows, 1.2 )
						: max( 1, min( 3, $visible_rows ) )
				);
				$tablet_slides_view  = $this->device_slides_per_view(
					ResponsiveCount::device( $rows_raw, 'Tablet' ),
					$is_partial,
					$is_partial
						? $this->resolve_partial_slides_per_view( $visible_rows, 2.2 )
						: max( 1, min( 4, $visible_rows ) )
				);
				$desktop_slides_view = $is_partial
					? $this->resolve_desktop_partial_slides_per_view( $visible_rows )
					: $visible_rows;
				$breakpoints         = array(
					0   => array(
						'slidesPerView'  => $mobile_slides_view,
						'slidesPerGroup' => $use_overlay_fade ? $this->resolve_fade_group_size( $mobile_slides_view ) : $slides_to_scroll['mobile'],
						'spaceBetween'   => $space_between['mobile'],
					),
					601 => array(
						'slidesPerView'  => $tablet_slides_view,
						'slidesPerGroup' => $use_overlay_fade ? $this->resolve_fade_group_size( $tablet_slides_view ) : $slides_to_scroll['tablet'],
						'spaceBetween'   => $space_between['tablet'],
					),
					901 => array(
						'slidesPerView'  => $desktop_slides_view,
						'slidesPerGroup' => $use_overlay_fade ? $this->resolve_fade_group_size( $desktop_slides_view ) : $slides_to_scroll['desktop'],
						'spaceBetween'   => $space_between['desktop'],
					),
				);
			} else {
				$is_partial          = ! empty( $behavior['partialSlideView'] );
				$columns_raw         = isset( $behavior['columnsRaw'] ) ? $behavior['columnsRaw'] : null;
				$mobile_slides_view  = $this->device_slides_per_view(
					ResponsiveCount::device( $columns_raw, 'Mobile' ),
					$is_partial,
					$is_partial ? 1.2 : 1
				);
				$tablet_slides_view  = $this->device_slides_per_view(
					ResponsiveCount::device( $columns_raw, 'Tablet' ),
					$is_partial,
					$is_partial ? 2.2 : 2
				);
				$desktop_slides_view = $is_partial
					? $this->resolve_desktop_partial_slides_per_view( (int) $behavior['columns'] )
					: max( 1, (int) $behavior['columns'] );
				$breakpoints         = array(
					0   => array(
						'slidesPerView'  => $mobile_slides_view,
						'slidesPerGroup' => $use_overlay_fade ? $this->resolve_fade_group_size( $mobile_slides_view ) : $slides_to_scroll['mobile'],
						'spaceBetween'   => $space_between['mobile'],
					),
					601 => array(
						'slidesPerView'  => $tablet_slides_view,
						'slidesPerGroup' => $use_overlay_fade ? $this->resolve_fade_group_size( $tablet_slides_view ) : $slides_to_scroll['tablet'],
						'spaceBetween'   => $space_between['tablet'],
					),
					901 => array(
						'slidesPerView'  => $desktop_slides_view,
						'slidesPerGroup' => $use_overlay_fade ? $this->resolve_fade_group_size( $desktop_slides_view ) : $slides_to_scroll['desktop'],
						'spaceBetween'   => $space_between['desktop'],
					),
				);
			}

			return $breakpoints;
		}

		/**
		 * Determine whether variable-width behavior should be active for the current layout.
		 *
		 * @param array<string, mixed> $behavior Normalized behavior state.
		 * @return bool
		 */
		private function supports_variable_width( array $behavior ) {
			return ! empty( $behavior['variableWidth'] ) && empty( $behavior['isVertical'] );
		}

		/**
		 * Resolve a desktop partial slides-per-view value.
		 *
		 * @param int $visible_count Visible slide count for the active layout.
		 * @return float|int
		 */
		private function resolve_desktop_partial_slides_per_view( $visible_count ) {
			$visible_count = max( 1, (int) $visible_count );

			if ( $visible_count <= 1 ) {
				return 1;
			}

			return $visible_count + 0.2;
		}

		/**
		 * Slides-per-view for one device, honouring an explicit Columns/Rows value.
		 *
		 * The author's own number wins; when they set none for this breakpoint the
		 * caller's `$derived` value — the cap this config has always applied to
		 * small screens — is used unchanged, which is what keeps content saved
		 * before the field was responsive rendering exactly as before. Partial
		 * Slide View keeps its peek on top of an explicit count, the same +0.2 the
		 * Desktop bucket has always used.
		 *
		 * @param int|null  $explicit Author's value for this device, or null.
		 * @param bool      $is_partial Whether Partial Slide View is on.
		 * @param int|float $derived Value this config derives when none is set.
		 * @return int|float
		 */
		private function device_slides_per_view( $explicit, $is_partial, $derived ) {
			if ( null === $explicit ) {
				return $derived;
			}

			$explicit = max( 1, (int) $explicit );

			// `resolve_desktop_partial_slides_per_view()` drops the peek at a count
			// of 1, which is right when the count is DERIVED (a single slide fills
			// the screen) but wrong for a count the author chose: asking for 1
			// column with Partial Slide View on is asking for one slide plus the
			// peek, which is what the Desktop bucket gives for every other number.
			return $is_partial ? $explicit + 0.2 : $explicit;
		}

		/**
		 * Resolve a fractional slides-per-view value for partial preview modes.
		 *
		 * @param int   $visible_count Visible slide count for the active layout.
		 * @param float $partial_value Requested fractional preview count.
		 * @return float|int
		 */
		private function resolve_partial_slides_per_view( $visible_count, $partial_value ) {
			if ( $visible_count <= 1 ) {
				return 1;
			}

			return min( $visible_count, $partial_value );
		}

		/**
		 * Determine whether slider speed was explicitly stored in the raw layout.
		 *
		 * @param array<string, mixed> $layout Raw layout state.
		 * @param string               $style Active style.
		 * @return bool
		 */
		private function has_custom_slider_speed( array $layout, $style ) {
			$profile_key = $this->get_profile_key( $style );
			$shared      = isset( $layout['shared'] ) && is_array( $layout['shared'] ) ? $layout['shared'] : array();
			$profile     = isset( $layout[ $profile_key ] ) && is_array( $layout[ $profile_key ] ) ? $layout[ $profile_key ] : array();

			return array_key_exists( 'sliderSpeed', $layout ) ||
				array_key_exists( 'sliderSpeed', $shared ) ||
				array_key_exists( 'sliderSpeed', $profile );
		}

		/**
		 * Resolve the slide group size used by custom fade transitions.
		 *
		 * @param int|float|string $slides_per_view Slides-per-view value.
		 * @return int
		 */
		private function resolve_fade_group_size( $slides_per_view ) {
			if ( 'auto' === $slides_per_view ) {
				return 1;
			}

			$parsed = (float) $slides_per_view;

			if ( $parsed <= 0 ) {
				return 1;
			}

			return max( 1, (int) ceil( $parsed ) );
		}

		/**
		 * Resolve speed from the configured slider speed, falling back to the preset.
		 *
		 * @param int|string $speed Slider speed value.
		 * @param string     $effect Preset value.
		 * @return int
		 */
		private function resolve_speed( $speed, $effect, $has_custom_speed = false ) {
			$speed_value = max( 0, (int) $speed );

			if ( 'slide' === $effect && ! $has_custom_speed ) {
				return 450;
			}

			if ( $speed_value > 0 ) {
				return $speed_value;
			}

			switch ( $effect ) {
				case 'slide':
					return 450;
				case 'snappy':
					return 220;
				case 'spring-bounce':
					return 650;
				case 'ease-in-out-slide':
					return 420;
				case 'fade':
					return 0;
				default:
					return 300;
			}
		}

		/**
		 * Resolve a per-device gap value.
		 *
		 * @param array<string, mixed> $gap Gap config.
		 * @param string               $device Device name.
		 * @param int                  $default Default value.
		 * @return int
		 */
		private function resolve_device_gap( array $gap, $device, $default ) {
			$value = isset( $gap['device'][ $device ] ) ? $gap['device'][ $device ] : $default;

			return $this->normalize_integer( $value, $default, 0 );
		}

		/**
		 * Resolve a per-device slide-to-scroll value.
		 *
		 * @param array<string, mixed> $slide_to_scroll Slide to scroll config.
		 * @param string               $device Device name.
		 * @param int                  $default Default value.
		 * @return int
		 */
		private function resolve_device_scroll( array $slide_to_scroll, $device, $default ) {
			$value = isset( $slide_to_scroll['device'][ $device ] ) ? $slide_to_scroll['device'][ $device ] : $default;

			return $this->normalize_integer( $value, $default, 1 );
		}

		/**
		 * Normalize responsive gap values.
		 *
		 * @param array<string, mixed> $value Raw value.
		 * @param array<string, mixed> $defaults Default value.
		 * @return array<string, mixed>
		 */
		private function normalize_gap_between_logos( array $value, array $defaults ) {
			$states     = array( 'Desktop', 'Tablet', 'Mobile' );
			$device     = isset( $value['device'] ) && is_array( $value['device'] ) ? $value['device'] : array();
			$unit       = isset( $value['unit'] ) && is_array( $value['unit'] ) ? $value['unit'] : array();
			$normalized = array(
				'device'    => array(),
				'unit'      => array(),
				'allChange' => $this->normalize_boolean(
					isset( $value['allChange'] ) ? $value['allChange'] : null,
					! empty( $defaults['allChange'] )
				),
			);

			foreach ( $states as $state ) {
				$normalized['device'][ $state ] = $this->normalize_integer(
					isset( $device[ $state ] ) ? $device[ $state ] : null,
					isset( $defaults['device'][ $state ] ) ? (int) $defaults['device'][ $state ] : 0,
					0
				);
				$normalized['unit'][ $state ]   = $this->normalize_enum(
					isset( $unit[ $state ] ) ? $unit[ $state ] : null,
					array( 'px', '%', 'em' ),
					isset( $defaults['unit'][ $state ] ) ? (string) $defaults['unit'][ $state ] : 'px'
				);
			}

			return $normalized;
		}

		/**
		 * Normalize responsive slide-to-scroll values.
		 *
		 * @param array<string, mixed> $value Raw value.
		 * @param array<string, mixed> $defaults Default value.
		 * @return array<string, mixed>
		 */
		private function normalize_slide_to_scroll( array $value, array $defaults ) {
			$states     = array( 'Desktop', 'Tablet', 'Mobile' );
			$device     = isset( $value['device'] ) && is_array( $value['device'] ) ? $value['device'] : array();
			$normalized = array(
				'device' => array(),
			);

			foreach ( $states as $state ) {
				$normalized['device'][ $state ] = $this->normalize_integer(
					isset( $device[ $state ] ) ? $device[ $state ] : null,
					isset( $defaults['device'][ $state ] ) ? (int) $defaults['device'][ $state ] : 1,
					1
				);
			}

			return $normalized;
		}

		/**
		 * Normalize a boolean-like value.
		 *
		 * @param mixed $value Raw value.
		 * @param bool  $default Default value.
		 * @return bool
		 */
		private function normalize_boolean( $value, $default ) {
			if ( is_bool( $value ) ) {
				return $value;
			}

			if ( null === $value || '' === $value ) {
				return (bool) $default;
			}

			$normalized = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
			if ( null === $normalized ) {
				return (bool) $default;
			}

			return (bool) $normalized;
		}

		/**
		 * Normalize an enum-like value.
		 *
		 * @param mixed  $value Raw value.
		 * @param array  $allowed Allowed values.
		 * @param string $default Default value.
		 * @return string
		 */
		private function normalize_enum( $value, array $allowed, $default ) {
			$value = is_scalar( $value ) ? trim( (string) $value ) : '';

			if ( '' !== $value && in_array( $value, $allowed, true ) ) {
				return $value;
			}

			return $default;
		}

		/**
		 * Normalize an integer with a lower bound.
		 *
		 * @param mixed $value Raw value.
		 * @param int   $default Default value.
		 * @param int   $min Minimum allowed value.
		 * @return int
		 */
		private function normalize_integer( $value, $default, $min = 0 ) {
			if ( is_array( $value ) && array_key_exists( 'value', $value ) ) {
				$value = $value['value'];
			}

			if ( is_numeric( $value ) ) {
				$value = (int) $value;

				if ( $value >= $min ) {
					return $value;
				}
			}

			return max( $min, (int) $default );
		}

		/**
		 * Normalize a bounded decimal number.
		 *
		 * @param mixed     $value Raw value.
		 * @param float|int $default Default value.
		 * @param float|int $min Minimum allowed value.
		 * @param float|int $max Maximum allowed value.
		 * @return float
		 */
		private function normalize_number( $value, $default, $min = 0, $max = 9999 ) {
			if ( is_numeric( $value ) ) {
				$value = (float) $value;

				if ( $value >= $min && $value <= $max ) {
					return $value;
				}
			}

			$default = is_numeric( $default ) ? (float) $default : (float) $min;

			return max( (float) $min, min( (float) $max, $default ) );
		}

		/**
		 * Normalize a positive integer.
		 *
		 * @param mixed $value Raw value.
		 * @param int   $default Default value.
		 * @return int
		 */
		private function normalize_positive_integer( $value, $default ) {
			return $this->normalize_integer( $value, $default, 1 );
		}
	}
}
