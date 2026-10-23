<?php
/**
 * Block Usage.
 *
 * Builds the dashboard's "Logo Showcase Placements" table: every page, post,
 * template and widget area that shows a Logo Carousel block, a shortcode view
 * or a saved template, plus the shortcode views that are not placed anywhere
 * yet. A saved template is listed only once it is placed. Page builders count too: Divi keeps its elements
 * in the content, Elementor, Bricks, Beaver Builder and Oxygen in post meta.
 *
 * @package Logo_Carousel_Free
 */

namespace ShapedPlugin\LogoCarouselFree\Admin\Dashboard;

use ShapedPlugin\LogoCarouselFree\Includes\SavedTemplates;
use ShapedPlugin\LogoCarouselFree\Includes\Dashboard\ModulesVisibility;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Manager as PageBuilders;
use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Divi5\Divi5_Builder;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Admin\Dashboard\BlockUsage' ) ) {

	/**
	 * Scans content for Logo Carousel placements.
	 */
	class BlockUsage {

		/**
		 * Block namespace every Logo Carousel block shares.
		 */
		const NAMESPACE_PREFIX = 'sp-logo-carousel/';

		/**
		 * The Classic Shortcode block, which embeds a shortcode view by id.
		 * Free registers it under Pro's name so content moves between the
		 * two plugins unchanged.
		 */
		const SHORTCODE_BLOCK = 'sp-logo-carousel-pro/shortcode';

		/**
		 * The Divi 5 module, which embeds a saved template. Divi 5 saves its
		 * modules as block markup in the post content.
		 */
		const DIVI5_BLOCK = 'lcpro/divi5-logo-carousel';

		/**
		 * Shortcode view post type.
		 */
		const SHORTCODE_POST_TYPE = 'sp_lc_shortcodes';

		/**
		 * Shortcode tags that embed something, what they embed, and the
		 * attribute holding its id. The WPBakery element and the Divi 4
		 * module are shortcodes too; both embed a saved template.
		 *
		 * @var array<string, array{0: string, 1: string}>
		 */
		const SHORTCODE_TAGS = array(
			'logocarousel'           => array( 'shortcode', 'id' ),
			'logo_carousel_pro'      => array( 'shortcode', 'id' ),
			'lc_template'            => array( 'template', 'id' ),
			'vc_sp_logo_carousel'    => array( 'template', 'template_id' ),
			'et_pb_sp_logo_carousel' => array( 'template', 'template_id' ),
		);

		/**
		 * Page-builder shortcode tags, by builder: they render only while
		 * that builder's integration is live.
		 *
		 * @var array<string, string>
		 */
		const BUILDER_TAGS = array(
			'vc_sp_logo_carousel'    => 'wpbakery',
			'et_pb_sp_logo_carousel' => 'divi',
		);

		/**
		 * Elementor widgets that embed something: widget type → what it
		 * embeds and the setting holding the id. The two shortcode widgets
		 * are the current one and the deprecated one older pages still use.
		 *
		 * @var array<string, array{0: string, 1: string}>
		 */
		const ELEMENTOR_WIDGETS = array(
			'sp_logo_carousel_pro_saved_template' => array( 'template', 'template_id' ),
			'sp_logo_carousel_pro_shortcode'      => array( 'shortcode', 'sp_logo_carousel_pro_shortcode' ),
			'logo_carsousel_shortcode'            => array( 'shortcode', 'sp_logo_carousel_free_shortcode' ),
		);

		/**
		 * The Bricks element, which embeds a saved template.
		 */
		const BRICKS_ELEMENT = 'lcpro-bricks-logo-carousel';

		/**
		 * The Oxygen element's tag. Oxygen prefixes each option with it, so
		 * the template id is stored as `<tag>_template_id`.
		 */
		const OXYGEN_ELEMENT = 'oxy-sp-logo-carousel-pro';

		/**
		 * Beaver Builder module class. Beaver stores a module by its slug,
		 * which it derives from the class file, so the slug is looked up.
		 */
		const BEAVER_MODULE_CLASS = 'LCPRO_Beaver_Logo_Carousel_Module';

		/**
		 * The slug Beaver Builder gives the module (its file name), used when
		 * Beaver's module registry cannot be read.
		 */
		const BEAVER_MODULE_SLUG = 'Beaver_Builder';

		/**
		 * Post meta each page builder keeps its layout in.
		 *
		 * @var array<string, string[]>
		 */
		const BUILDER_META = array(
			'elementor' => array( '_elementor_data' ),
			'bricks'    => array( '_bricks_page_content_2', '_bricks_page_header_2', '_bricks_page_footer_2' ),
			'beaver'    => array( '_fl_builder_data' ),
			'oxygen'    => array( 'ct_builder_shortcodes', '_ct_builder_json' ),
		);

		/**
		 * Option holding when widgets were last saved. WordPress keeps widgets
		 * in options, which carry no date, so the plugin records one.
		 */
		const WIDGETS_MODIFIED_OPTION = 'sp_lcpro_widgets_modified';

		/**
		 * Widget options whose save counts as "widgets updated".
		 *
		 * @var string[]
		 */
		const WIDGET_OPTIONS = array( 'widget_block', 'widget_text', 'sidebars_widgets' );

		/**
		 * Most posts one scan reads. A site with more still gets a table; the
		 * response says it was capped.
		 */
		const MAX_POSTS = 500;

		/**
		 * Option holding the last scan. An option, not a transient: the table
		 * shows this report until someone syncs, so nothing may expire it.
		 */
		const CACHE_OPTION = 'sp_lcpro_placements_report';

		/**
		 * Version of the stored report. A report saved by an older version is
		 * scanned again rather than served. 2: the Classic Shortcode block is
		 * matched by its real name and page-builder layouts are read, so
		 * reports that listed placed views and templates as unused refresh.
		 * 3: unplaced saved templates are no longer listed.
		 */
		const CACHE_VERSION = 3;

		/**
		 * Transient the previous, self-refreshing cache used. Cleared when a
		 * new report is stored.
		 */
		const LEGACY_CACHE_KEY = 'sp_lcpro_block_usage_v3';

		/**
		 * Statuses a placement is listed under. Anything but "publish" is
		 * flagged in the table (Draft, Scheduled, …).
		 *
		 * @var string[]
		 */
		const STATUSES = array( 'publish', 'future', 'draft', 'pending', 'private' );

		/**
		 * Post types that are never a placement: the plugin's own content and
		 * WordPress internals.
		 *
		 * @var string[]
		 */
		const EXCLUDED_TYPES = array(
			'revision',
			'nav_menu_item',
			'customize_changeset',
			'oembed_cache',
			'user_request',
			'wp_global_styles',
			'wp_font_family',
			'wp_font_face',
			'attachment',
			'sp_logo_carousel',
			self::SHORTCODE_POST_TYPE,
			SavedTemplates::POST_TYPE,
		);

		/**
		 * Count Logo Carousel blocks in parsed blocks, nested ones included.
		 *
		 * Pure: walks what parse_blocks() returned.
		 *
		 * @param array $blocks Parsed blocks.
		 * @return array<string, int> Block slug (e.g. "carousel") → count.
		 */
		public static function count_blocks( array $blocks ) {
			$counts = array();

			foreach ( $blocks as $block ) {
				if ( ! is_array( $block ) ) {
					continue;
				}

				$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
				if ( 0 === strpos( $name, self::NAMESPACE_PREFIX ) ) {
					$slug            = substr( $name, strlen( self::NAMESPACE_PREFIX ) );
					$counts[ $slug ] = ( isset( $counts[ $slug ] ) ? $counts[ $slug ] : 0 ) + 1;
				}

				if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
					foreach ( self::count_blocks( $block['innerBlocks'] ) as $slug => $count ) {
						$counts[ $slug ] = ( isset( $counts[ $slug ] ) ? $counts[ $slug ] : 0 ) + $count;
					}
				}
			}

			return $counts;
		}

		/**
		 * Shortcode view ids embedded through the legacy shortcode block.
		 *
		 * Pure: walks what parse_blocks() returned.
		 *
		 * @param array $blocks Parsed blocks.
		 * @return int[] View ids, in page order, repeats kept.
		 */
		public static function find_block_shortcode_ids( array $blocks ) {
			$ids = array();

			foreach ( $blocks as $block ) {
				if ( ! is_array( $block ) ) {
					continue;
				}

				$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
				if ( self::SHORTCODE_BLOCK === $name && ! empty( $block['attrs']['shortcode'] ) ) {
					$id = absint( $block['attrs']['shortcode'] );
					if ( $id ) {
						$ids[] = $id;
					}
				}

				if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
					$ids = array_merge( $ids, self::find_block_shortcode_ids( $block['innerBlocks'] ) );
				}
			}

			return $ids;
		}

		/**
		 * Saved template ids embedded through the Divi 5 module.
		 *
		 * Pure: walks what parse_blocks() returned.
		 *
		 * @param array $blocks Parsed blocks.
		 * @return int[] Template ids, in page order, repeats kept.
		 */
		public static function find_block_template_ids( array $blocks ) {
			$ids = array();

			foreach ( $blocks as $block ) {
				if ( ! is_array( $block ) ) {
					continue;
				}

				$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
				if ( self::DIVI5_BLOCK === $name && isset( $block['attrs']['templateId']['innerContent']['desktop']['value'] ) ) {
					$id = absint( $block['attrs']['templateId']['innerContent']['desktop']['value'] );
					if ( $id ) {
						$ids[] = $id;
					}
				}

				if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
					$ids = array_merge( $ids, self::find_block_template_ids( $block['innerBlocks'] ) );
				}
			}

			return $ids;
		}

		/**
		 * Shortcode view and saved template ids a piece of content embeds.
		 *
		 * Pure. An escaped shortcode (`[[logocarousel id="1"]]`) prints as text,
		 * so it is not counted.
		 *
		 * @param string        $content Raw content.
		 * @param string[]|null $tags    Tags to look for (keys of SHORTCODE_TAGS);
		 *                               null for all of them.
		 * @return array{shortcode: int[], template: int[]} Unique ids per source.
		 */
		public static function find_shortcode_ids( $content, $tags = null ) {
			$found = array(
				'shortcode' => array(),
				'template'  => array(),
			);

			$content = (string) $content;
			if ( false === strpos( $content, '[' ) ) {
				return $found;
			}

			$tags = null === $tags ? array_keys( self::SHORTCODE_TAGS ) : array_values( array_intersect( (array) $tags, array_keys( self::SHORTCODE_TAGS ) ) );
			if ( empty( $tags ) ) {
				return $found;
			}

			$pattern = implode( '|', array_map( 'preg_quote', $tags ) );

			if ( ! preg_match_all( '/(?<!\[)\[(' . $pattern . ')(?=[\s\]\/])([^\]]*)\](?!\])/i', $content, $matches, PREG_SET_ORDER ) ) {
				return $found;
			}

			foreach ( $matches as $match ) {
				list( $source, $attribute ) = self::SHORTCODE_TAGS[ strtolower( $match[1] ) ];

				if ( preg_match( '/(?:^|\s)' . $attribute . '\s*=\s*(["\']?)(\d+)\1/i', $match[2], $id_match ) ) {
					$id = absint( $id_match[2] );
					if ( $id && ! in_array( $id, $found[ $source ], true ) ) {
						$found[ $source ][] = $id;
					}
				}
			}

			return $found;
		}

		/**
		 * An empty "found" pair: shortcode view ids and saved template ids.
		 *
		 * @return array{shortcode: int[], template: int[]}
		 */
		private static function no_ids() {
			return array(
				'shortcode' => array(),
				'template'  => array(),
			);
		}

		/**
		 * Add an id to a found pair once.
		 *
		 * @param array  $found  Found pair, by reference.
		 * @param string $source shortcode|template.
		 * @param mixed  $id     Raw id.
		 * @return void
		 */
		private static function add_id( array &$found, $source, $id ) {
			$id = is_scalar( $id ) ? absint( $id ) : 0;
			if ( $id && ! in_array( $id, $found[ $source ], true ) ) {
				$found[ $source ][] = $id;
			}
		}

		/**
		 * Shortcode view and saved template ids an Elementor layout embeds.
		 *
		 * Pure. Takes `_elementor_data` as stored (a JSON string) or decoded,
		 * and walks every nested element.
		 *
		 * @param mixed $data Elementor data.
		 * @return array{shortcode: int[], template: int[]} Unique ids per source.
		 */
		public static function find_elementor_ids( $data ) {
			$found = self::no_ids();

			if ( is_string( $data ) ) {
				$data = json_decode( $data, true );
			}
			if ( ! is_array( $data ) ) {
				return $found;
			}

			$stack = array_values( $data );
			while ( $stack ) {
				$element = array_pop( $stack );
				if ( ! is_array( $element ) ) {
					continue;
				}

				$widget = isset( $element['widgetType'] ) ? (string) $element['widgetType'] : '';
				if ( isset( self::ELEMENTOR_WIDGETS[ $widget ] ) && isset( $element['settings'] ) && is_array( $element['settings'] ) ) {
					list( $source, $setting ) = self::ELEMENTOR_WIDGETS[ $widget ];
					if ( isset( $element['settings'][ $setting ] ) ) {
						self::add_id( $found, $source, $element['settings'][ $setting ] );
					}
				}

				if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
					foreach ( array_reverse( $element['elements'] ) as $child ) {
						$stack[] = $child;
					}
				}
			}

			return $found;
		}

		/**
		 * Saved template ids a Bricks layout embeds.
		 *
		 * Pure. Bricks stores a flat list of elements (children point to
		 * ids), so every entry is read.
		 *
		 * @param mixed $elements Bricks page content.
		 * @return int[] Unique template ids.
		 */
		public static function find_bricks_template_ids( $elements ) {
			$found = self::no_ids();

			foreach ( is_array( $elements ) ? $elements : array() as $element ) {
				if ( is_array( $element ) && isset( $element['name'], $element['settings']['template_id'] ) && self::BRICKS_ELEMENT === $element['name'] ) {
					self::add_id( $found, 'template', $element['settings']['template_id'] );
				}
			}

			return $found['template'];
		}

		/**
		 * Saved template ids a Beaver Builder layout embeds.
		 *
		 * Pure. Beaver stores nodes keyed by id, as objects (or arrays once
		 * exported); a module node's settings name the module by slug.
		 *
		 * @param mixed    $nodes Beaver layout data.
		 * @param string[] $slugs Slugs the Logo Carousel module may have.
		 * @return int[] Unique template ids.
		 */
		public static function find_beaver_template_ids( $nodes, array $slugs ) {
			$found = self::no_ids();
			$slugs = array_map( 'strtolower', $slugs );

			foreach ( is_array( $nodes ) ? $nodes : array() as $node ) {
				$node     = is_object( $node ) ? get_object_vars( $node ) : $node;
				$settings = is_array( $node ) && isset( $node['settings'] ) ? $node['settings'] : null;
				$settings = is_object( $settings ) ? get_object_vars( $settings ) : $settings;

				if ( ! is_array( $settings ) || ! isset( $node['type'], $settings['type'], $settings['template_id'] ) || 'module' !== $node['type'] ) {
					continue;
				}

				if ( in_array( strtolower( (string) $settings['type'] ), $slugs, true ) ) {
					self::add_id( $found, 'template', $settings['template_id'] );
				}
			}

			return $found['template'];
		}

		/**
		 * Saved template ids an Oxygen layout embeds.
		 *
		 * Pure. Oxygen keeps the layout as shortcodes whose options are JSON
		 * (or as a JSON tree), with each option prefixed by the element tag,
		 * so the prefixed key is read straight from the stored text.
		 *
		 * @param mixed $raw Oxygen layout, as stored.
		 * @return int[] Unique template ids.
		 */
		public static function find_oxygen_template_ids( $raw ) {
			$found = self::no_ids();

			if ( ! is_string( $raw ) || false === strpos( $raw, self::OXYGEN_ELEMENT ) ) {
				return $found['template'];
			}

			if ( preg_match_all( '/' . preg_quote( self::OXYGEN_ELEMENT, '/' ) . '_template_id\\\\?"\s*:\s*\\\\?"?(\d+)/', $raw, $matches ) ) {
				foreach ( $matches[1] as $id ) {
					self::add_id( $found, 'template', $id );
				}
			}

			return $found['template'];
		}

		/**
		 * Display name of a shortcode view's layout.
		 *
		 * Pure. A carousel in Ticker mode reads as "Ticker"; a view saved
		 * before a layout was picked is the default carousel.
		 *
		 * @param mixed $layout_options The view's `sp_lcp_layout_options` meta.
		 * @return string Layout name.
		 */
		public static function shortcode_layout_label( $layout_options ) {
			$options = is_array( $layout_options ) ? $layout_options : array();
			$layout  = ! empty( $options['lcp_layout'] ) ? (string) $options['lcp_layout'] : 'carousel';

			if ( 'carousel' === $layout && isset( $options['lcp_logo_carousel_mode'] ) && 'ticker' === $options['lcp_logo_carousel_mode'] ) {
				return __( 'Ticker', 'logo-carousel-free' );
			}

			// The names the view editor's Layout Preset field shows.
			$names = array(
				'carousel'   => __( 'Carousel', 'logo-carousel-free' ),
				'multi_rows' => __( 'Multi Rows', 'logo-carousel-free' ),
				'grid'       => __( 'Grid', 'logo-carousel-free' ),
				'masonry'    => __( 'Masonry', 'logo-carousel-free' ),
				'filter'     => __( 'Isotope', 'logo-carousel-free' ),
				'list'       => __( 'List', 'logo-carousel-free' ),
				'inline'     => __( 'Inline', 'logo-carousel-free' ),
				'table'      => __( 'Table', 'logo-carousel-free' ),
			);

			if ( isset( $names[ $layout ] ) ) {
				return $names[ $layout ];
			}

			return ucwords( str_replace( array( '-', '_' ), ' ', $layout ) );
		}

		/**
		 * Display names of counted blocks, most used first.
		 *
		 * Pure.
		 *
		 * @param array<string, int>    $blocks Block slug → count.
		 * @param array<string, string> $labels Block slug → display name.
		 * @return string[] Names.
		 */
		public static function block_layouts( array $blocks, array $labels = array() ) {
			arsort( $blocks );

			$layouts = array();
			foreach ( array_keys( $blocks ) as $slug ) {
				$layouts[] = isset( $labels[ $slug ] ) ? (string) $labels[ $slug ] : ucwords( str_replace( array( '-', '_' ), ' ', (string) $slug ) );
			}

			return $layouts;
		}

		/**
		 * Turn scanned locations and sources into table rows.
		 *
		 * Pure. A location gets one row per kind of placement it holds (its
		 * blocks, its shortcode views, its saved templates), listing the
		 * layouts shown there. A shortcode view placed nowhere gets a row with
		 * no location; a saved template placed nowhere is not listed — saving
		 * one does not put it on the site. Newest first.
		 *
		 * @param array $locations    Locations: key, title, type, status, statusLabel,
		 *                            editUrl, viewUrl, modified, blocks (slug → count),
		 *                            shortcodes (ids), templates (ids).
		 * @param array $shortcodes   View id → title, layouts, editUrl, modified.
		 * @param array $templates    Template id → title, layouts, editUrl, modified.
		 * @param array $block_labels Block slug → display name.
		 * @return array<int, array<string, mixed>> Rows.
		 */
		public static function build_rows( array $locations, array $shortcodes, array $templates, array $block_labels = array() ) {
			$rows    = array();
			$sources = array(
				'shortcode' => $shortcodes,
				'template'  => $templates,
			);
			$placed  = array(
				'shortcode' => array(),
				'template'  => array(),
			);

			foreach ( $locations as $location ) {
				$place    = array(
					'title'       => isset( $location['title'] ) ? (string) $location['title'] : '',
					'type'        => isset( $location['type'] ) ? (string) $location['type'] : '',
					'status'      => isset( $location['status'] ) ? (string) $location['status'] : 'publish',
					'statusLabel' => isset( $location['statusLabel'] ) ? (string) $location['statusLabel'] : '',
					'editUrl'     => isset( $location['editUrl'] ) ? (string) $location['editUrl'] : '',
					'viewUrl'     => isset( $location['viewUrl'] ) ? (string) $location['viewUrl'] : '',
				);
				$key      = isset( $location['key'] ) ? (string) $location['key'] : '';
				$modified = isset( $location['modified'] ) ? (int) $location['modified'] : 0;

				$blocks = isset( $location['blocks'] ) && is_array( $location['blocks'] ) ? $location['blocks'] : array();
				if ( ! empty( $blocks ) ) {
					$rows[] = array(
						'key'      => $key . ':block',
						'source'   => 'block',
						'location' => $place,
						'layouts'  => self::block_layouts( $blocks, $block_labels ),
						'names'    => array(),
						'modified' => $modified,
						'editUrl'  => $place['editUrl'],
					);
				}

				foreach ( array(
					'shortcode' => 'shortcodes',
					'template'  => 'templates',
				) as $source => $field ) {
					$ids     = isset( $location[ $field ] ) && is_array( $location[ $field ] ) ? $location[ $field ] : array();
					$layouts = array();
					$names   = array();

					foreach ( array_unique( array_map( 'intval', $ids ) ) as $id ) {
						if ( ! isset( $sources[ $source ][ $id ] ) ) {
							continue;
						}

						$item                     = $sources[ $source ][ $id ];
						$placed[ $source ][ $id ] = true;
						$names[]                  = isset( $item['title'] ) ? (string) $item['title'] : '';
						$layouts                  = array_merge( $layouts, isset( $item['layouts'] ) ? (array) $item['layouts'] : array() );
					}

					if ( empty( $names ) ) {
						continue;
					}

					$rows[] = array(
						'key'      => $key . ':' . $source,
						'source'   => $source,
						'location' => $place,
						'layouts'  => array_values( array_unique( $layouts ) ),
						'names'    => $names,
						'modified' => $modified,
						'editUrl'  => $place['editUrl'],
					);
				}
			}

			foreach ( $sources as $source => $items ) {
				// Saved templates appear only where they are used.
				if ( 'template' === $source ) {
					continue;
				}

				foreach ( $items as $id => $item ) {
					if ( isset( $placed[ $source ][ $id ] ) ) {
						continue;
					}

					$rows[] = array(
						'key'      => $source . '-' . $id . ':unused',
						'source'   => $source,
						'location' => null,
						'layouts'  => array_values( array_unique( isset( $item['layouts'] ) ? (array) $item['layouts'] : array() ) ),
						'names'    => array( isset( $item['title'] ) ? (string) $item['title'] : '' ),
						'modified' => isset( $item['modified'] ) ? (int) $item['modified'] : 0,
						'editUrl'  => isset( $item['editUrl'] ) ? (string) $item['editUrl'] : '',
					);
				}
			}

			// Stable newest-first: equal dates keep scan order.
			$order = array_keys( $rows );
			array_multisort( array_column( $rows, 'modified' ), SORT_DESC, $order, SORT_ASC, $rows );

			return $rows;
		}

		/**
		 * Record the time whenever widgets are saved, from any screen: the
		 * block widget editor (REST), the classic Widgets screen or the
		 * Customizer. Runs on every request, not only on the dashboard.
		 *
		 * @return void
		 */
		public static function register_hooks() {
			foreach ( self::WIDGET_OPTIONS as $option ) {
				add_action( 'add_option_' . $option, array( __CLASS__, 'touch_widgets' ) );
				add_action( 'update_option_' . $option, array( __CLASS__, 'touch_widgets' ) );
			}
		}

		/**
		 * Stamp the widgets as updated now.
		 *
		 * WordPress fires the update hook only when the stored value really
		 * changed, so opening a screen without saving leaves the date alone.
		 *
		 * @return void
		 */
		public static function touch_widgets() {
			update_option( self::WIDGETS_MODIFIED_OPTION, time(), false );
		}

		/**
		 * The placements report.
		 *
		 * Served from the stored report whenever there is one — content edited
		 * since does not change it. The site is scanned only when nothing is
		 * stored yet (the first visit) or when `$force` asks for it (the
		 * dashboard's Sync button).
		 *
		 * @param bool $force Scan the site again and replace the stored report.
		 * @return array<string, mixed> Report: rows, capped, scannedAt.
		 */
		public static function report( $force = false ) {
			$cached = get_option( self::CACHE_OPTION );

			if ( ! $force && self::is_current_cache( $cached ) ) {
				return $cached['report'];
			}

			$report = self::scan();
			update_option(
				self::CACHE_OPTION,
				array(
					'version' => self::CACHE_VERSION,
					'report'  => $report,
				),
				false
			);
			delete_transient( self::LEGACY_CACHE_KEY );

			return $report;
		}

		/**
		 * Whether the site already has a logo showcase, from the stored report.
		 *
		 * Reads the cache only — never scans — so it is cheap enough for the
		 * dashboard bootstrap. Null when nothing usable is stored yet; the
		 * dashboard learns the answer once its first report loads.
		 *
		 * @return bool|null
		 */
		public static function stored_has_showcases() {
			return self::cache_has_rows( get_option( self::CACHE_OPTION ) );
		}

		/**
		 * Whether a stored value is a current report with at least one row.
		 *
		 * Pure.
		 *
		 * @param mixed $cached What the cache option holds.
		 * @return bool|null Null when the value is not a report this version can serve.
		 */
		public static function cache_has_rows( $cached ) {
			if ( ! self::is_current_cache( $cached ) ) {
				return null;
			}

			return count( $cached['report']['rows'] ) > 0;
		}

		/**
		 * Whether a stored value is a report this version can serve.
		 *
		 * Pure.
		 *
		 * @param mixed $cached What the cache option holds.
		 * @return bool
		 */
		public static function is_current_cache( $cached ) {
			return is_array( $cached )
				&& isset( $cached['version'], $cached['report'] )
				&& self::CACHE_VERSION === $cached['version']
				&& is_array( $cached['report'] )
				&& isset( $cached['report']['rows'] )
				&& is_array( $cached['report']['rows'] );
		}

		/**
		 * Whether saved templates are on (their CPT and shortcode are live).
		 *
		 * @return bool
		 */
		private static function templates_enabled() {
			return class_exists( ModulesVisibility::class ) && (bool) ( new ModulesVisibility() )->is_enabled( SavedTemplates::MODULE );
		}

		/**
		 * Whether a page builder's Logo Carousel element renders here.
		 *
		 * @param string $builder Builder key.
		 * @return bool
		 */
		private static function builder_live( $builder ) {
			return class_exists( PageBuilders::class ) && PageBuilders::is_integration_live( $builder );
		}

		/**
		 * What the scan looks for on this site: the shortcode tags that
		 * render, whether Divi 5 modules render, the builders whose post
		 * meta is read and the Beaver module's slugs.
		 *
		 * @return array{tags: string[], divi5: bool, meta: array<string, string[]>, beaver: string[]}
		 */
		private static function scan_context() {
			$tags = array();
			foreach ( array_keys( self::SHORTCODE_TAGS ) as $tag ) {
				if ( ! isset( self::BUILDER_TAGS[ $tag ] ) || self::builder_live( self::BUILDER_TAGS[ $tag ] ) ) {
					$tags[] = $tag;
				}
			}

			// Divi 4 modules are shortcodes; under Divi 5 they are blocks.
			$divi  = self::builder_live( 'divi' );
			$divi5 = $divi && class_exists( Divi5_Builder::class ) && Divi5_Builder::is_divi_5_active();
			if ( $divi5 ) {
				$tags = array_values( array_diff( $tags, array( 'et_pb_sp_logo_carousel' ) ) );
			}

			$meta = array();
			foreach ( self::BUILDER_META as $builder => $keys ) {
				if ( self::builder_live( $builder ) ) {
					$meta[ $builder ] = $keys;
				}
			}

			return array(
				'tags'   => $tags,
				'divi5'  => $divi5,
				'meta'   => $meta,
				'beaver' => isset( $meta['beaver'] ) ? self::beaver_slugs() : array(),
			);
		}

		/**
		 * Slugs the Logo Carousel module has in Beaver Builder: the one
		 * Beaver registered it under, plus the file-name slug it defaults to.
		 *
		 * @return string[]
		 */
		private static function beaver_slugs() {
			$slugs = array( self::BEAVER_MODULE_SLUG );

			if ( class_exists( '\FLBuilderModel' ) && isset( \FLBuilderModel::$modules ) && is_array( \FLBuilderModel::$modules ) ) {
				foreach ( \FLBuilderModel::$modules as $slug => $module ) {
					if ( is_a( $module, self::BEAVER_MODULE_CLASS ) ) {
						$slugs[] = (string) $slug;
					}
				}
			}

			return array_values( array_unique( $slugs ) );
		}

		/**
		 * Strings whose presence in a builder's meta means a post may hold a
		 * Logo Carousel element, per builder.
		 *
		 * @param array $context Scan context.
		 * @return array<string, string[]>
		 */
		private static function meta_markers( array $context ) {
			$markers = array(
				'elementor' => array_keys( self::ELEMENTOR_WIDGETS ),
				'bricks'    => array( self::BRICKS_ELEMENT ),
				'beaver'    => $context['beaver'],
				'oxygen'    => array( self::OXYGEN_ELEMENT ),
			);

			return array_intersect_key( $markers, $context['meta'] );
		}

		/**
		 * Shortcode view and saved template ids a post's page-builder layout
		 * embeds. A layout counts only while the post is built with that
		 * builder; otherwise WordPress shows the post content instead.
		 *
		 * @param \WP_Post $post    Post.
		 * @param array    $context Scan context.
		 * @return array{shortcode: int[], template: int[]}
		 */
		private static function builder_ids( \WP_Post $post, array $context ) {
			$found = self::no_ids();

			foreach ( $context['meta'] as $builder => $keys ) {
				if ( 'elementor' === $builder && 'builder' !== get_post_meta( $post->ID, '_elementor_edit_mode', true ) ) {
					continue;
				}
				if ( 'beaver' === $builder && ! get_post_meta( $post->ID, '_fl_builder_enabled', true ) ) {
					continue;
				}
				if ( 'bricks' === $builder ) {
					$mode = get_post_meta( $post->ID, '_bricks_editor_mode', true );
					if ( '' !== $mode && 'bricks' !== $mode ) {
						continue;
					}
				}

				foreach ( $keys as $key ) {
					$value = get_post_meta( $post->ID, $key, true );
					if ( empty( $value ) ) {
						continue;
					}

					switch ( $builder ) {
						case 'elementor':
							$ids = self::find_elementor_ids( $value );
							break;
						case 'bricks':
							$ids = array( 'template' => self::find_bricks_template_ids( $value ) );
							break;
						case 'beaver':
							$ids = array( 'template' => self::find_beaver_template_ids( $value, $context['beaver'] ) );
							break;
						default:
							$ids = array( 'template' => self::find_oxygen_template_ids( is_string( $value ) ? $value : wp_json_encode( $value ) ) );
					}

					foreach ( $ids as $source => $list ) {
						foreach ( $list as $id ) {
							self::add_id( $found, $source, $id );
						}
					}
				}
			}

			return $found;
		}

		/**
		 * What a piece of content embeds: block counts, shortcode views and
		 * saved templates.
		 *
		 * @param string $content Raw content.
		 * @param array  $context Scan context.
		 * @return array{blocks: array<string, int>, shortcode: int[], template: int[]}
		 */
		private static function content_ids( $content, array $context ) {
			$parsed    = parse_blocks( $content );
			$found     = self::find_shortcode_ids( $content, $context['tags'] );
			$templates = $found['template'];

			if ( $context['divi5'] ) {
				$templates = array_merge( $templates, self::find_block_template_ids( $parsed ) );
			}

			return array(
				'blocks'    => self::count_blocks( $parsed ),
				'shortcode' => array_values( array_unique( array_merge( self::find_block_shortcode_ids( $parsed ), $found['shortcode'] ) ) ),
				'template'  => array_values( array_unique( $templates ) ),
			);
		}

		/**
		 * Read every place that shows a Logo Carousel, and what it shows.
		 *
		 * The LIKE narrows the read to posts whose content mentions a block or
		 * shortcode at all; parse_blocks() and the shortcode matcher then read
		 * what is really there, so a post quoting the markup as text is not
		 * reported as using it.
		 *
		 * @return array<string, mixed>
		 */
		private static function scan() {
			global $wpdb;

			$types = array_values( array_diff( get_post_types( array(), 'names' ), self::EXCLUDED_TYPES ) );

			if ( empty( $types ) ) {
				return self::empty_report();
			}

			$context = self::scan_context();

			$markers = array( '<!-- wp:' . self::NAMESPACE_PREFIX, '<!-- wp:' . self::SHORTCODE_BLOCK );
			if ( $context['divi5'] ) {
				$markers[] = '<!-- wp:' . self::DIVI5_BLOCK;
			}
			foreach ( $context['tags'] as $tag ) {
				$markers[] = '[' . $tag;
			}

			$likes = array();
			foreach ( $markers as $marker ) {
				$likes[] = '%' . $wpdb->esc_like( $marker ) . '%';
			}

			$type_placeholders   = implode( ', ', array_fill( 0, count( $types ), '%s' ) );
			$status_placeholders = implode( ', ', array_fill( 0, count( self::STATUSES ), '%s' ) );
			$where               = implode( ' OR ', array_fill( 0, count( $likes ), 'post_content LIKE %s' ) );
			$args                = array_merge( $types, self::STATUSES, $likes );

			// Page builders that keep their layout in post meta: also read the
			// posts whose layout names a Logo Carousel element.
			$meta_keys  = array();
			$meta_likes = array();
			foreach ( self::meta_markers( $context ) as $builder => $builder_markers ) {
				$meta_keys = array_merge( $meta_keys, $context['meta'][ $builder ] );
				foreach ( $builder_markers as $marker ) {
					$meta_likes[] = '%' . $wpdb->esc_like( $marker ) . '%';
				}
			}
			if ( $meta_keys && $meta_likes ) {
				$key_placeholders = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );
				$meta_like        = implode( ' OR ', array_fill( 0, count( $meta_likes ), 'meta_value LIKE %s' ) );
				$where           .= " OR ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ( {$key_placeholders} ) AND ( {$meta_like} ) )";
				$args             = array_merge( $args, $meta_keys, $meta_likes );
			}

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- one bounded read, cached above; placeholders are built from counted arrays.
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type IN ( {$type_placeholders} ) AND post_status IN ( {$status_placeholders} ) AND ( {$where} ) ORDER BY post_modified_gmt DESC LIMIT %d",
					array_merge( $args, array( self::MAX_POSTS + 1 ) )
				)
			);
			// phpcs:enable

			$capped    = count( $ids ) > self::MAX_POSTS;
			$ids       = array_slice( array_map( 'absint', (array) $ids ), 0, self::MAX_POSTS );
			$locations = array();

			foreach ( $ids as $id ) {
				$post = get_post( $id );
				if ( ! $post ) {
					continue;
				}

				$location = self::post_location( $post, $context );
				if ( $location ) {
					$locations[] = $location;
				}
			}

			$widgets = self::widget_location( $context );
			if ( $widgets ) {
				$locations[] = $widgets;
			}

			$templates = self::templates_enabled() ? self::sources( SavedTemplates::POST_TYPE ) : array();

			return array(
				'rows'      => self::build_rows( $locations, self::sources( self::SHORTCODE_POST_TYPE ), $templates, self::block_labels() ),
				'capped'    => $capped,
				'scannedAt' => time(),
			);
		}

		/**
		 * What one post shows, or null when it shows nothing.
		 *
		 * @param \WP_Post $post    Post.
		 * @param array    $context Scan context.
		 * @return array<string, mixed>|null
		 */
		private static function post_location( \WP_Post $post, array $context ) {
			$found   = self::content_ids( $post->post_content, $context );
			$builder = self::builder_ids( $post, $context );
			$blocks  = $found['blocks'];
			$views   = array_values( array_unique( array_merge( $found['shortcode'], $builder['shortcode'] ) ) );
			$library = array_values( array_unique( array_merge( $found['template'], $builder['template'] ) ) );

			if ( empty( $blocks ) && empty( $views ) && empty( $library ) ) {
				return null;
			}

			$type_object = get_post_type_object( $post->post_type );
			$is_public   = $type_object && ( $type_object->public || $type_object->publicly_queryable );
			$status      = get_post_status_object( $post->post_status );
			$view_url    = '';

			if ( $is_public ) {
				$view_url = in_array( $post->post_status, array( 'publish', 'private' ), true )
					? (string) get_permalink( $post )
					: (string) get_preview_post_link( $post );
			}

			return array(
				'key'         => 'post-' . $post->ID,
				'title'       => self::title( $post ),
				'type'        => $type_object ? $type_object->labels->singular_name : $post->post_type,
				'status'      => $post->post_status,
				'statusLabel' => 'publish' === $post->post_status ? '' : ( $status ? $status->label : $post->post_status ),
				'editUrl'     => current_user_can( 'edit_post', $post->ID ) ? (string) get_edit_post_link( $post->ID, 'raw' ) : '',
				'viewUrl'     => $view_url,
				'modified'    => (int) get_post_modified_time( 'U', true, $post ),
				'blocks'      => $blocks,
				'shortcodes'  => $views,
				'templates'   => $library,
			);
		}

		/**
		 * The active widget areas, as one location, or null when no active
		 * widget shows anything.
		 *
		 * Block widgets and Text widgets are read; a widget parked under
		 * "Inactive widgets" is not on the site.
		 *
		 * @param array $context Scan context.
		 * @return array<string, mixed>|null
		 */
		private static function widget_location( array $context ) {
			$sidebars = get_option( 'sidebars_widgets' );
			if ( ! is_array( $sidebars ) ) {
				return null;
			}

			$active = array();
			foreach ( $sidebars as $sidebar => $widget_ids ) {
				if ( 'wp_inactive_widgets' === $sidebar || ! is_array( $widget_ids ) ) {
					continue;
				}
				foreach ( $widget_ids as $widget_id ) {
					$active[ (string) $widget_id ] = true;
				}
			}

			$content = '';
			foreach ( array(
				'block' => 'content',
				'text'  => 'text',
			) as $base => $field ) {
				$instances = get_option( 'widget_' . $base );
				if ( ! is_array( $instances ) ) {
					continue;
				}
				foreach ( $instances as $number => $instance ) {
					if ( isset( $active[ $base . '-' . $number ] ) && is_array( $instance ) && ! empty( $instance[ $field ] ) ) {
						$content .= "\n" . (string) $instance[ $field ];
					}
				}
			}

			if ( '' === $content ) {
				return null;
			}

			$found  = self::content_ids( $content, $context );
			$blocks = $found['blocks'];
			$views  = $found['shortcode'];

			if ( empty( $blocks ) && empty( $views ) && empty( $found['template'] ) ) {
				return null;
			}

			return array(
				'key'         => 'widgets',
				'title'       => __( 'Widgets', 'logo-carousel-free' ),
				'type'        => __( 'Widget', 'logo-carousel-free' ),
				'status'      => 'publish',
				'statusLabel' => '',
				'editUrl'     => current_user_can( 'edit_theme_options' ) ? admin_url( 'widgets.php' ) : '',
				'viewUrl'     => home_url( '/' ),
				// 0 (shown as a dash) until widgets are next saved after this
				// was added — there is no earlier date to recover.
				'modified'    => absint( get_option( self::WIDGETS_MODIFIED_OPTION, 0 ) ),
				'blocks'      => $blocks,
				'shortcodes'  => $views,
				'templates'   => $found['template'],
			);
		}

		/**
		 * Shortcode views or saved templates, keyed by id.
		 *
		 * @param string $post_type Source post type.
		 * @return array<int, array<string, mixed>>
		 */
		private static function sources( $post_type ) {
			$posts = get_posts(
				array(
					'post_type'              => $post_type,
					'post_status'            => self::STATUSES,
					'numberposts'            => self::MAX_POSTS,
					'orderby'                => 'modified',
					'order'                  => 'DESC',
					'suppress_filters'       => true,
					'update_post_term_cache' => false,
				)
			);

			$labels  = self::block_labels();
			$sources = array();

			foreach ( $posts as $post ) {
				if ( self::SHORTCODE_POST_TYPE === $post_type ) {
					$layouts = array( self::shortcode_layout_label( get_post_meta( $post->ID, 'sp_lcp_layout_options', true ) ) );
				} else {
					$layouts = self::block_layouts( self::count_blocks( parse_blocks( $post->post_content ) ), $labels );
				}

				$sources[ (int) $post->ID ] = array(
					'title'    => self::title( $post ),
					'layouts'  => $layouts,
					'editUrl'  => current_user_can( 'edit_post', $post->ID ) ? (string) get_edit_post_link( $post->ID, 'raw' ) : '',
					'modified' => (int) get_post_modified_time( 'U', true, $post ),
				);
			}

			return $sources;
		}

		/**
		 * Display names of the registered Logo Carousel blocks.
		 *
		 * @return array<string, string> Block slug → title.
		 */
		private static function block_labels() {
			$labels = array();

			if ( ! class_exists( '\WP_Block_Type_Registry' ) ) {
				return $labels;
			}

			foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $type ) {
				if ( 0 === strpos( $name, self::NAMESPACE_PREFIX ) && ! empty( $type->title ) ) {
					$labels[ substr( $name, strlen( self::NAMESPACE_PREFIX ) ) ] = (string) $type->title;
				}
			}

			return $labels;
		}

		/**
		 * A post's title as plain text, or a placeholder for an untitled one.
		 *
		 * @param \WP_Post $post Post.
		 * @return string
		 */
		private static function title( \WP_Post $post ) {
			$title = get_the_title( $post );

			return '' !== trim( $title ) ? wp_strip_all_tags( html_entity_decode( $title, ENT_QUOTES, 'UTF-8' ) ) : __( '(no title)', 'logo-carousel-free' );
		}

		/**
		 * A report for a site with nothing to scan.
		 *
		 * @return array<string, mixed>
		 */
		private static function empty_report() {
			return array(
				'rows'      => array(),
				'capped'    => false,
				'scannedAt' => time(),
			);
		}
	}
}
