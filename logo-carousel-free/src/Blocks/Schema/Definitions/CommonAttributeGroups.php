<?php
/**
 * Shared grouped attribute definitions for block schemas.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Blocks\Schema\Definitions;

use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\LayoutDefaults;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\NavigationDefaults;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Defaults\PaginationDefaults;
use ShapedPlugin\LogoCarouselFree\Blocks\Schema\Factories\AttributeFactory;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'CommonAttributeGroups' ) ) {
	/**
	 * Shared grouped schema branches.
	 */
	class CommonAttributeGroups {
		/**
		 * Shared section-level wrapper settings.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function section() {
			return array(
				'section' => AttributeFactory::object(
					array(
						'background' => array(
							'style'    => 'transparent',
							'solid'    => '',
							'gradient' => '',
						),
						'padding'    => array(
							'device'    => array(
								'Desktop' => array(
									'top'    => 0,
									'right'  => 0,
									'bottom' => 0,
									'left'   => 0,
								),
								'Tablet'  => array(
									'top'    => 0,
									'right'  => 0,
									'bottom' => 0,
									'left'   => 0,
								),
								'Mobile'  => array(
									'top'    => 0,
									'right'  => 0,
									'bottom' => 0,
									'left'   => 0,
								),
							),
							'unit'      => array(
								'Desktop' => 'px',
								'Tablet'  => 'px',
								'Mobile'  => 'px',
							),
							'allChange' => true,
						),
						'margin'     => array(
							'device'    => array(
								'Desktop' => array(
									'top'    => 0,
									'right'  => 0,
									'bottom' => 0,
									'left'   => 0,
								),
								'Tablet'  => array(
									'top'    => 0,
									'right'  => 0,
									'bottom' => 0,
									'left'   => 0,
								),
								'Mobile'  => array(
									'top'    => 0,
									'right'  => 0,
									'bottom' => 0,
									'left'   => 0,
								),
							),
							'unit'      => array(
								'Desktop' => 'px',
								'Tablet'  => 'px',
								'Mobile'  => 'px',
							),
							'allChange' => true,
						),
					),
					array(
						'background' => AttributeFactory::object(
							array(
								'style'    => 'transparent',
								'solid'    => '',
								'gradient' => '',
							),
							array(
								'style'    => AttributeFactory::string( 'transparent' ),
								'solid'    => AttributeFactory::string( '' ),
								'gradient' => AttributeFactory::string( '' ),
							)
						),
						'padding'    => AttributeFactory::responsive_spacing( 0, 0, 0, 0, 'px', true ),
						'margin'     => AttributeFactory::responsive_spacing( 0, 0, 0, 0, 'px', true ),
					)
				),
			);
		}

		/**
		 * Shared responsive visibility settings.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function visibility() {
			return array(
				'visibility' => AttributeFactory::object(
					array(
						'hideOnDesktop'         => false,
						'hideOnTablet'          => false,
						'hideOnMobilePortrait'  => false,
						'hideOnMobileLandscape' => false,
					),
					array(
						'hideOnDesktop'         => AttributeFactory::boolean( false ),
						'hideOnTablet'          => AttributeFactory::boolean( false ),
						'hideOnMobilePortrait'  => AttributeFactory::boolean( false ),
						'hideOnMobileLandscape' => AttributeFactory::boolean( false ),
					)
				),
			);
		}

		/**
		 * Shared advanced settings.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function advanced() {
			return array(
				'advanced' => AttributeFactory::object(
					array(
						'cssClass' => '',
						'cssId'    => '',
					),
					array(
						'cssClass' => AttributeFactory::string( '' ),
						'cssId'    => AttributeFactory::string( '' ),
					)
				),
			);
		}

		/**
		 * Shared logo showcase source settings.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function logo_showcase_source() {
			return array(
				// Editor-only: the logos came from a Ready Pattern or the AI
				// builder's demo set. Shows the "replace the demo logos" notice and
				// turns Edit Logos into a media-library replace. Any edit the user
				// makes to the logos clears it; nothing on the frontend reads it.
				'patternDemo' => AttributeFactory::boolean( false ),
				'logoSource'  => AttributeFactory::object(
					array(
						'mode'      => '',
						'patternId' => '',
						'items'     => array(),
					),
					array(
						'mode'      => AttributeFactory::string( '' ),
						'patternId' => AttributeFactory::string( '' ),
						'items'     => AttributeFactory::array(
							array(),
							array(
								'type'       => 'object',
								'properties' => self::logo_item_properties(),
							)
						),
					)
				),
			);
		}

		/**
		 * Shared layout settings for the carousel family.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function layout() {
			return array(
				'layout' => AttributeFactory::object(
					LayoutDefaults::get_layout_defaults(),
					array(
						'carouselStyle'           => AttributeFactory::string(
							'standard',
							array(
								'standard',
								'staggered-carousel',
								'center',
								'multirow',
							)
						),
						'shared'                  => AttributeFactory::object(
							LayoutDefaults::get_shared_layout_defaults(),
							self::layout_shared_properties()
						),
						'standard'                => AttributeFactory::object(
							LayoutDefaults::get_standard_layout_defaults(),
							self::layout_standard_properties()
						),
						'center'                  => AttributeFactory::object(
							LayoutDefaults::get_center_layout_defaults(),
							self::layout_center_properties()
						),
						'staggered'               => AttributeFactory::object(
							LayoutDefaults::get_staggered_layout_defaults(),
							self::layout_staggered_properties()
						),
						'multirow'                => AttributeFactory::object(
							LayoutDefaults::get_multirow_layout_defaults(),
							array(
								'horizontal' => AttributeFactory::object(
									LayoutDefaults::get_multirow_layout_defaults()['horizontal'],
									array(
										'rows'    => AttributeFactory::responsive_count( 2, 2, 2 ),
										'columns' => AttributeFactory::responsive_count( 5, 3, 2 ),
									)
								),
								'vertical'   => AttributeFactory::object(
									LayoutDefaults::get_multirow_layout_defaults()['vertical'],
									array(
										'rows'    => AttributeFactory::responsive_count( 5, 3, 2 ),
										'columns' => AttributeFactory::responsive_count( 2, 2, 2 ),
									)
								),
							)
						),
						'displayOrientation'      => AttributeFactory::string(
							'horizontal',
							array( 'horizontal', 'vertical' )
						),
						'staggeredAnimation'      => AttributeFactory::string(
							'slide-to-bottom',
							array(
								'slide-to-top',
								'slide-to-bottom',
								'slide-to-right',
								'slide-to-left',
								'random-slide',
								'alternating-direction',
								'fade-in',
								'blur-reveal',
								'crossfade-scale',
								'flip-curtain',
								'bounce-in',
							)
						),
						'randomLogoAnimation'     => AttributeFactory::boolean( false ),
						'easing'                  => AttributeFactory::string(
							'default',
							array(
								'default',
								'linear',
								'ease-in',
								'ease-out',
								'ease-in-out',
								'ease-in-back',
								'ease-out-back',
								'ease-in-out-back',
							)
						),
						'partialSlideView'        => AttributeFactory::boolean( false ),
						'variableWidth'           => AttributeFactory::boolean( false ),
						// Responsive. Tablet/Mobile are seeded with the counts this block
						// has always derived for small screens (see SwiperConfig's
						// breakpoint builder), so a new block renders exactly like an
						// existing one — the numbers are simply visible and editable now.
						'columns'                 => AttributeFactory::responsive_count( 5, 2, 1 ),
						'rows'                    => AttributeFactory::responsive_count( 5, 4, 3 ),
						'gapBetweenLogos'         => AttributeFactory::single_responsive( 15, 15, 15, 'px' ),
						'alignment'               => AttributeFactory::object(
							LayoutDefaults::get_alignment_defaults(),
							array(
								'horizontal' => AttributeFactory::string(
									'center',
									array( 'top', 'center', 'bottom' )
								),
								'vertical'   => AttributeFactory::string(
									'center',
									array( 'left', 'center', 'right' )
								),
							)
						),
						'edgeFade'                => AttributeFactory::object(
							LayoutDefaults::get_edge_fade_defaults(),
							array(
								'enabled'   => AttributeFactory::boolean( false ),
								'strength'  => AttributeFactory::integer_like( 0 ),
								'width'     => AttributeFactory::integer_like( 0 ),
								'colorMode' => AttributeFactory::string(
									'auto',
									array( 'auto', 'custom' )
								),
								'color'     => AttributeFactory::string( '' ),
							)
						),
						'autoplay'                => AttributeFactory::boolean( true ),
						'autoplayDelay'           => AttributeFactory::integer_like( 3000 ),
						'sliderSpeed'             => AttributeFactory::integer_like( 600 ),
						'pauseOnHover'            => AttributeFactory::boolean( true ),
						'carouselDirection'       => AttributeFactory::string(
							'left-to-right',
							array(
								'right-to-left',
								'left-to-right',
								'bottom-to-top',
								'top-to-bottom',
							)
						),
						'slideToScroll'           => AttributeFactory::single_responsive( 1, 1, 1, '' ),
						'slidingEffects'          => AttributeFactory::string(
							'none',
							array(
								'none',
								'slide',
								'ease-in-out-slide',
								'spring-bounce',
								'snappy',
								'fade',
								'slide-fade',
							)
						),
						'adaptiveHeight'          => AttributeFactory::boolean( false ),
						'infiniteLoop'            => AttributeFactory::boolean( true ),
						'carouselStartsOnScreen'  => AttributeFactory::boolean( false ),
						'tabsAndKeyNavigation'    => AttributeFactory::boolean( true ),
						'mouseWheelControl'       => AttributeFactory::boolean( false ),
						'freeScrollMode'          => AttributeFactory::boolean( false ),
						'centeredLogoScale'       => AttributeFactory::number( 1.2 ),
						'slideLogosOpacity'       => AttributeFactory::number( 0.5 ),
						'navigationArrows'        => AttributeFactory::boolean( true ),
						'paginationDots'          => AttributeFactory::boolean( true ),
						'ajaxLiveFilter'          => AttributeFactory::boolean( false ),
						'ajaxSearch'              => AttributeFactory::boolean( false ),
						'filterSearchOrientation' => AttributeFactory::string(
							'inline',
							array( 'inline', 'stacked' )
						),
						'filterSearchGap'         => AttributeFactory::single_responsive( 15, 15, 15, 'px' ),
						'justifyFilterSearch'     => AttributeFactory::string(
							'flex-start',
							array(
								'flex-start',
								'center',
								'flex-end',
								'space-between',
								'space-around',
								'space-evenly',
							)
						),
					)
				),
			);
		}

		/**
		 * Navigation arrows grouped branch.
		 *
		 * Top-level `navigation` branch (sibling to layout). The enable toggle
		 * stays in the Layouts section as `layout.navigationArrows`; this branch
		 * owns icon / size / placement / behavior. Settings-tab fields only.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function navigation() {
			return array(
				'navigation' => AttributeFactory::object(
					NavigationDefaults::get_navigation_defaults(),
					array(
						'presetIcon'         => AttributeFactory::string(
							'arrow-01',
							NavigationDefaults::get_preset_icon_options()
						),
						'iconSize'           => AttributeFactory::single_responsive( 24, 22, 20, 'px' ),
						'navStyle'           => AttributeFactory::string(
							'style-1',
							array( 'style-1', 'style-2' )
						),
						'position'           => AttributeFactory::string(
							'center-outer',
							NavigationDefaults::get_position_options()
						),
						'horizontalDistance' => AttributeFactory::single_responsive( 6, 6, 6, 'px' ),
						'gapBetweenArrows'   => AttributeFactory::single_responsive( 10, 10, 10, 'px' ),
						'showOnHover'        => AttributeFactory::boolean( false ),
						'hideOnMobile'       => AttributeFactory::boolean( true ),
						// Style-tab fields. background + iconColor + border are
						// per state (Normal/Hover); radius/padding/margin shared.
						'background'         => AttributeFactory::object( NavigationDefaults::get_background_defaults() ),
						'iconColor'          => AttributeFactory::object( NavigationDefaults::get_icon_color_defaults() ),
						'border'             => AttributeFactory::object( NavigationDefaults::get_border_defaults() ),
						'borderWidth'        => AttributeFactory::responsive_spacing( 0, 0, 0, 0, 'px' ),
						'borderRadius'       => AttributeFactory::responsive_spacing( 4, 4, 4, 4, 'px' ),
						'innerPadding'       => AttributeFactory::responsive_spacing( 0, 0, 0, 0, 'px' ),
						'margin'             => AttributeFactory::responsive_spacing( 0, 20, 0, 20, 'px' ),
					)
				),
			);
		}

		/**
		 * Pagination dots grouped branch.
		 *
		 * Top-level `pagination` branch (sibling to navigation). The enable
		 * toggle stays in the Layouts section as `layout.paginationDots`; this
		 * branch owns style / size / gap / behavior and Style-tab paint.
		 *
		 * `paginationStyle` is the structural driver: Width/Height/Gap meaning
		 * and the static CSS presets branch on it. Width/Height are absolute
		 * pixel sizes on bullets. `gapBetweenDots` emits CSS `gap` on the
		 * container (style-2 falls back to half-margin). Gap and Width are
		 * hidden (editor) and not emitted for `style-5` (scrollbar; Height only).
		 *
		 * @return array<string, array<string, mixed>>
		 */
		public static function pagination() {
			return array(
				'pagination' => AttributeFactory::object(
					PaginationDefaults::get_pagination_defaults(),
					array(
						'paginationStyle' => AttributeFactory::string(
							'style-1',
							PaginationDefaults::get_style_options()
						),
						'width'           => AttributeFactory::single_responsive( 10, 10, 10, 'px' ),
						'height'          => AttributeFactory::single_responsive( 10, 10, 10, 'px' ),
						'gapBetweenDots'  => AttributeFactory::single_responsive( 8, 8, 8, 'px' ),
						'hideOnMobile'    => AttributeFactory::boolean( true ),
						'backgroundColor' => AttributeFactory::object(
							array(
								'normal' => '#9fb3bf',
								'active' => '#557182',
							)
						),
						'textColor'       => AttributeFactory::object(
							array(
								'normal' => '#ffffff',
								'active' => '#ffffff',
							)
						),
						'border'          => AttributeFactory::object(
							array(
								'style'       => 'none',
								'color'       => '#9fb3bf',
								'activeColor' => '#557182',
							)
						),
						'borderWidth'     => AttributeFactory::responsive_spacing( 0, 0, 0, 0, 'px' ),
						'borderRadius'    => AttributeFactory::responsive_spacing( 3, 3, 3, 3, 'px' ),
						'margin'          => AttributeFactory::responsive_spacing( 0, 0, 0, 0, 'px' ),
						'typography'      => AttributeFactory::object(
							PaginationDefaults::get_pagination_defaults()['typography']
						),
					)
				),
			);
		}

		/**
		 * Shared layout profile properties.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		private static function layout_shared_properties() {
			return array(
				'displayOrientation'      => AttributeFactory::string(
					'horizontal',
					array( 'horizontal', 'vertical' )
				),
				'gapBetweenLogos'         => AttributeFactory::single_responsive( 15, 15, 15, 'px' ),
				'alignment'               => AttributeFactory::object(
					LayoutDefaults::get_alignment_defaults(),
					array(
						'horizontal' => AttributeFactory::string(
							'center',
							array( 'top', 'center', 'bottom' )
						),
						'vertical'   => AttributeFactory::string(
							'center',
							array( 'left', 'center', 'right' )
						),
					)
				),
				'edgeFade'                => AttributeFactory::object(
					LayoutDefaults::get_edge_fade_defaults(),
					array(
						'enabled'   => AttributeFactory::boolean( false ),
						'strength'  => AttributeFactory::integer_like( 0 ),
						'width'     => AttributeFactory::integer_like( 0 ),
						'colorMode' => AttributeFactory::string(
							'auto',
							array( 'auto', 'custom' )
						),
						'color'     => AttributeFactory::string( '' ),
					)
				),
				'autoplay'                => AttributeFactory::boolean( true ),
				'autoplayDelay'           => AttributeFactory::integer_like( 3000 ),
				'sliderSpeed'             => AttributeFactory::integer_like( 600 ),
				'pauseOnHover'            => AttributeFactory::boolean( true ),
				'adaptiveHeight'          => AttributeFactory::boolean( false ),
				'infiniteLoop'            => AttributeFactory::boolean( true ),
				'partialSlideView'        => AttributeFactory::boolean( false ),
				'variableWidth'           => AttributeFactory::boolean( false ),
				'navigationArrows'        => AttributeFactory::boolean( true ),
				'paginationDots'          => AttributeFactory::boolean( true ),
				'ajaxLiveFilter'          => AttributeFactory::boolean( false ),
				'ajaxSearch'              => AttributeFactory::boolean( false ),
				'filterSearchOrientation' => AttributeFactory::string(
					'inline',
					array( 'inline', 'stacked' )
				),
				'filterSearchGap'         => AttributeFactory::single_responsive( 15, 15, 15, 'px' ),
				'justifyFilterSearch'     => AttributeFactory::string(
					'flex-start',
					array(
						'flex-start',
						'center',
						'flex-end',
						'space-between',
						'space-around',
						'space-evenly',
					)
				),
			);
		}

		/**
		 * Standard layout profile properties.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		private static function layout_standard_properties() {
			return array(
				'columns'                => AttributeFactory::responsive_count( 5, 2, 1 ),
				'rows'                   => AttributeFactory::responsive_count( 5, 4, 3 ),
				'carouselDirection'      => AttributeFactory::string(
					'left-to-right',
					array(
						'right-to-left',
						'left-to-right',
						'bottom-to-top',
						'top-to-bottom',
					)
				),
				'slideToScroll'          => AttributeFactory::single_responsive( 1, 1, 1, '' ),
				'slidingEffects'         => AttributeFactory::string(
					'none',
					array(
						'none',
						'slide',
						'ease-in-out-slide',
						'spring-bounce',
						'snappy',
						'fade',
						'slide-fade',
					)
				),
				'carouselStartsOnScreen' => AttributeFactory::boolean( false ),
				'tabsAndKeyNavigation'   => AttributeFactory::boolean( true ),
				'mouseWheelControl'      => AttributeFactory::boolean( false ),
				'freeScrollMode'         => AttributeFactory::boolean( false ),
			);
		}

		/**
		 * Staggered layout profile properties.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		private static function layout_staggered_properties() {
			return array(
				'columns'             => AttributeFactory::responsive_count( 5, 2, 1 ),
				'rows'                => AttributeFactory::responsive_count( 5, 4, 3 ),
				'staggeredAnimation'  => AttributeFactory::string(
					'slide-to-bottom',
					array(
						'slide-to-top',
						'slide-to-bottom',
						'slide-to-right',
						'slide-to-left',
						'random-slide',
						'alternating-direction',
						'fade-in',
						'blur-reveal',
						'crossfade-scale',
						'flip-curtain',
						'bounce-in',
					)
				),
				'randomLogoAnimation' => AttributeFactory::boolean( false ),
				'easing'              => AttributeFactory::string(
					'default',
					array(
						'default',
						'linear',
						'ease-in',
						'ease-out',
						'ease-in-out',
						'ease-in-back',
						'ease-out-back',
						'ease-in-out-back',
					)
				),
			);
		}

		/**
		 * Center layout profile properties.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		private static function layout_center_properties() {
			return array(
				'columns'                => AttributeFactory::responsive_count( 5, 2, 1 ),
				'rows'                   => AttributeFactory::responsive_count( 5, 4, 3 ),
				'carouselDirection'      => AttributeFactory::string(
					'left-to-right',
					array(
						'right-to-left',
						'left-to-right',
						'bottom-to-top',
						'top-to-bottom',
					)
				),
				'slideToScroll'          => AttributeFactory::single_responsive( 1, 1, 1, '' ),
				'slidingEffects'         => AttributeFactory::string(
					'none',
					array(
						'none',
						'slide',
						'ease-in-out-slide',
						'spring-bounce',
						'snappy',
						'fade',
						'slide-fade',
					)
				),
				'carouselStartsOnScreen' => AttributeFactory::boolean( false ),
				'tabsAndKeyNavigation'   => AttributeFactory::boolean( true ),
				'mouseWheelControl'      => AttributeFactory::boolean( false ),
				'freeScrollMode'         => AttributeFactory::boolean( false ),
				'centeredLogoScale'      => AttributeFactory::number( 1.2 ),
				'slideLogosOpacity'      => AttributeFactory::number( 0.5 ),
			);
		}

		/**
		 * Return reusable logo item properties.
		 *
		 * @return array<string, array<string, mixed>>
		 */
		private static function logo_item_properties() {
			return array(
				'id'             => AttributeFactory::string( '' ),
				'mediaId'        => AttributeFactory::string( '' ),
				// Legacy compatibility fallback. Attachment ID is the primary source of truth.
				'imageUrl'       => AttributeFactory::string( '' ),
				'title'          => AttributeFactory::string( '' ),
				'caption'        => AttributeFactory::string( '' ),
				'tagline'        => AttributeFactory::string( '' ),
				'description'    => AttributeFactory::string( '' ),
				// Multi-category source of truth (array of strings).
				'categories'     => AttributeFactory::array( array() ),
				// Legacy single category — kept for deserialization of blocks saved
				// before multi-category. Read-only: the normalizer coerces it into
				// `categories`; nothing writes it back. Remove in a later major.
				'category'       => AttributeFactory::string( '' ),
				'tooltipText'    => AttributeFactory::string( '' ),
				'url'            => AttributeFactory::string( '' ),
				'altText'        => AttributeFactory::string( '' ),
				'rating'         => AttributeFactory::number( 0 ),
				'additionalInfo' => AttributeFactory::object(
					array(
						'fileName' => '',
						'mimeType' => '',
						'fileSize' => '',
						'width'    => '',
						'height'   => '',
					),
					array(
						'fileName' => AttributeFactory::string( '' ),
						'mimeType' => AttributeFactory::string( '' ),
						'fileSize' => AttributeFactory::string( '' ),
						'width'    => AttributeFactory::string( '' ),
						'height'   => AttributeFactory::string( '' ),
					)
				),
				'order'          => AttributeFactory::integer( 0 ),
				'createdAt'      => AttributeFactory::string( '' ),
				'updatedAt'      => AttributeFactory::string( '' ),
			);
		}
	}
}
