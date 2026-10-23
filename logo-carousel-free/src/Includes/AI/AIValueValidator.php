<?php
/**
 * AI Value Validator.
 *
 * Leaf-level type/range/enum gate for AI-generated block attributes.
 *
 * @package Logo_Carousel_Pro
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\AI;

use ShapedPlugin\LogoCarouselFree\Blocks\Styles\CssUtils;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'ShapedPlugin\LogoCarouselFree\Includes\AI\AIValueValidator' ) ) {

	/**
	 * Drops or coerces every AI-supplied value the target block cannot use.
	 *
	 * Filtering a model response by top-level key alone lets everything below
	 * that through — an out-of-range `columns`, an invented `layout.cardStyle`,
	 * a `gridStyle` the Carousel does not have, a gap that arrived as the
	 * string "large" — to be merged into the block's saved attributes verbatim
	 * and then silently ignored by whatever consumer read it. From the user's
	 * side that reads as "the AI ignored what I asked for", with no way to tell a
	 * model mistake from an unsupported request.
	 *
	 * Two gates run here, both against the block's own schema and the same
	 * allow-lists the inspector controls use:
	 *
	 * 1. **Shape** — a first-level key under a known attribute group must exist
	 *    on that group's schema default. Deeper structure is left alone:
	 *    responsive `device`/`unit` maps and the background shape legitimately
	 *    carry keys the schema default does not enumerate.
	 * 2. **Value** — a leaf with a rule is coerced (numeric strings, boolish
	 *    strings), clamped when it is a range, and dropped when it is an enum,
	 *    colour, or type the value cannot satisfy.
	 *
	 * A dropped leaf leaves the merge untouched, so the block keeps its schema
	 * default (or, on a refine, the value it already had) instead of storing a
	 * value nothing can render.
	 */
	class AIValueValidator {

		/**
		 * CSS units accepted on a responsive `unit` map.
		 *
		 * @var string[]
		 */
		const SPACING_UNITS = array( 'px', '%', 'em', 'rem' );

		/**
		 * Attributes a model response never gets to set, whatever it sends.
		 *
		 * `uniqueId` scopes every per-instance CSS selector the block emits and
		 * `isPreview` switches the block to its static inserter illustration —
		 * both are assigned by the editor and the block registration, and a
		 * generated value would break the block rather than restyle it.
		 * `logoSource` carries the user's own media and is written by the source
		 * workflow, never by a prompt, and `patternDemo` is set only alongside
		 * the demo logos `AISchemaValidator` fills in. None of the four is in any
		 * catalog; this is the backstop.
		 *
		 * @var string[]
		 */
		const REJECTED_KEYS = array( 'uniqueId', 'isPreview', 'logoSource', 'patternDemo' );

		/**
		 * Border styles every border control in this plugin offers.
		 *
		 * @var string[]
		 */
		const BORDER_STYLES = array( 'none', 'solid', 'dashed', 'dotted', 'double' );

		/**
		 * Background styles the runtime background shape accepts.
		 *
		 * @var string[]
		 */
		const BACKGROUND_STYLES = array( 'solid', 'gradient', 'transparent' );

		/**
		 * Rules keyed by dotted attribute path. A `*` segment matches any key.
		 *
		 * Each rule is `array( <kind>, <arg> )`:
		 *  - `int`    — `array( min, max )`, clamped.
		 *  - `number` — `array( min, max )`, clamped, floats kept.
		 *  - `bool`   — no arg.
		 *  - `enum`   — allowed values, or a `@name` resolved per block.
		 *  - `color`  — any value `CssUtils::sanitize_css_color()` accepts.
		 *  - `text`   — a short single-line string.
		 *
		 * Layout paths are declared flat (`layout.columns`); the shared/profile
		 * fan-out runs afterwards in `AILayoutProfileWriter`, and the wildcard
		 * rows below cover the branch copies the model writes by name.
		 *
		 * @var array<string, array<int, mixed>>
		 */
		const RULES = array(
			// -- Layout: style pickers -------------------------------------
			'layout.carouselStyle'                     => array( 'enum', array( 'standard', 'center' ) ),
			'layout.gridStyle'                         => array( 'enum', array( 'standard' ) ),

			// -- Layout: shared behaviour ----------------------------------
			'layout.displayOrientation'                => array( 'enum', array( 'horizontal', 'vertical' ) ),
			'layout.autoplay'                          => array( 'bool' ),
			'layout.autoplayDelay'                     => array( 'int', array( 500, 15000 ) ),
			'layout.sliderSpeed'                       => array( 'int', array( 100, 5000 ) ),
			'layout.pauseOnHover'                      => array( 'bool' ),
			'layout.adaptiveHeight'                    => array( 'bool' ),
			'layout.infiniteLoop'                      => array( 'bool' ),
			'layout.navigationArrows'                  => array( 'bool' ),
			'layout.paginationDots'                    => array( 'bool' ),
			'layout.partialSlideView'                  => array( 'bool' ),
			'layout.variableWidth'                     => array( 'bool' ),
			'layout.tabsAndKeyNavigation'              => array( 'bool' ),
			'layout.mouseWheelControl'                 => array( 'bool' ),
			'layout.freeScrollMode'                    => array( 'bool' ),
			'layout.carouselStartsOnScreen'            => array( 'bool' ),
			'layout.carouselDirection'                 => array( 'enum', array( 'left-to-right', 'right-to-left', 'bottom-to-top', 'top-to-bottom' ) ),
			'layout.slidingEffects'                    => array( 'enum', array( 'none', 'slide', 'ease-in-out-slide', 'spring-bounce', 'snappy', 'fade', 'slide-fade' ) ),
			'layout.centeredLogoScale'                 => array( 'number', array( 1, 2 ) ),
			'layout.slideLogosOpacity'                 => array( 'number', array( 0, 1 ) ),
			'layout.justify'                           => array( 'enum', array( 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly' ) ),
			'layout.alignment.horizontal'              => array( 'enum', array( 'flex-start', 'center', 'flex-end', 'left', 'right' ) ),
			'layout.alignment.vertical'                => array( 'enum', array( 'flex-start', 'center', 'flex-end', 'top', 'bottom' ) ),
			'layout.edgeFade.enabled'                  => array( 'bool' ),
			'layout.edgeFade.strength'                 => array( 'int', array( 0, 100 ) ),
			'layout.edgeFade.width'                    => array( 'int', array( 0, 400 ) ),
			'layout.edgeFade.colorMode'                => array( 'enum', array( 'auto', 'custom' ) ),
			'layout.edgeFade.color'                    => array( 'color' ),

			// Values the model writes on a style branch by name. A `*` here
			// covers `shared` too, which is where the fan-out lands the same
			// values.
			'layout.*.alignment'                       => array( 'enum', array( 'top', 'center', 'bottom', 'left', 'right', 'start', 'end', 'stretch', 'flex-start', 'flex-end' ) ),

			// -- Images -----------------------------------------------------
			'imagesSettings.aspectRatio'               => array( 'enum', array( 'original', '1:1', '4:3', '3:4', '3:2', '2:3', '16:9', '9:16', 'custom' ) ),
			'imagesSettings.resolution'                => array( 'enum', array( 'full', 'large', 'medium', 'thumbnail' ) ),
			'imagesSettings.lazyLoad'                  => array( 'bool' ),
			'imagesSettings.randomOrder'               => array( 'bool' ),
			'imagesSettings.allowSvgUploads'           => array( 'bool' ),
			'imagesSettings.clickAction'               => array( 'enum', array( 'none', 'link', 'popup' ) ),

			'logoImagesStyle.hoverAnimation'           => array( 'enum', array( 'none', 'scale-up', 'fade-scale', 'rotate', 'slide-up', 'flip-x', 'flip-y', 'border-ring', 'skew', 'bounce', 'spin-360', 'jelly', 'shake', 'float', 'pulse', 'swing', 'wobble', 'heartbeat' ) ),
			'logoImagesStyle.*.customColorEnabled'     => array( 'bool' ),
			'logoImagesStyle.*.customColor'            => array( 'color' ),
			'logoImagesStyle.*.opacity'                => array( 'int', array( 0, 100 ) ),
			'logoImagesStyle.*.imageFilter.blur'       => array( 'number', array( 0, 20 ) ),
			'logoImagesStyle.*.imageFilter.brightness' => array( 'number', array( -100, 100 ) ),
			'logoImagesStyle.*.imageFilter.contrast'   => array( 'number', array( -100, 100 ) ),
			'logoImagesStyle.*.imageFilter.saturation' => array( 'number', array( -100, 100 ) ),
			'logoImagesStyle.*.imageFilter.hue'        => array( 'number', array( -180, 180 ) ),
			'logoImagesStyle.*.overlay.type'           => array( 'enum', array( 'solid', 'gradient' ) ),
			'logoImagesStyle.*.overlay.color'          => array( 'color' ),
			'logoImagesStyle.*.overlay.opacity'        => array( 'int', array( 0, 100 ) ),

			// -- Logo card ---------------------------------------------------
			'displayElements.alignment'                => array( 'enum', array( 'left', 'center', 'right' ) ),

			// -- Navigation (Carousel) --------------------------------------
			'navigation.presetIcon'                    => array( 'enum', '@arrow_icons' ),
			'navigation.navStyle'                      => array( 'enum', array( 'style-1', 'style-2' ) ),
			'navigation.position'                      => array( 'enum', array( 'center', 'center-outer', 'center-inner', 'top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right' ) ),
			'navigation.showOnHover'                   => array( 'bool' ),
			'navigation.hideOnMobile'                  => array( 'bool' ),

			// -- Pagination bullets (Carousel) ------------------------------
			'pagination.paginationStyle'               => array( 'enum', array( 'style-1', 'style-2', 'style-3', 'style-4', 'style-5', 'style-6' ) ),
			'pagination.hideOnMobile'                  => array( 'bool' ),

			// -- Section band -------------------------------------------------
			'section.background.style'                 => array( 'enum', self::BACKGROUND_STYLES ),
			'section.background.solid'                 => array( 'color' ),
		);

		/**
		 * Rules keyed by a path suffix pattern, applied when no exact `RULES`
		 * entry matches.
		 *
		 * Responsive spacing/typography/colour objects repeat the same handful of
		 * leaves under dozens of attribute paths; matching on the tail covers all
		 * of them without an entry per path.
		 *
		 * @var array<string, array<int, mixed>>
		 */
		const SUFFIX_RULES = array(
			'/\.device\.(Desktop|Tablet|Mobile)\.(top|right|bottom|left)$/' => array( 'int', array( -500, 500 ) ),
			'/\.device\.(Desktop|Tablet|Mobile)$/' => array( 'number', array( 0, 3000 ) ),
			'/\.unit\.(Desktop|Tablet|Mobile)$/'   => array( 'enum', self::SPACING_UNITS ),
			'/\.allChange$/'                       => array( 'bool' ),
			'/(^|\.)border\.style$/'               => array( 'enum', self::BORDER_STYLES ),
			'/(^|\.)border\.(color|hoverColor|activeColor)$/' => array( 'color' ),
			'/(^|\.)background\.style$/'           => array( 'enum', self::BACKGROUND_STYLES ),
			'/(^|\.)background\.solid$/'           => array( 'color' ),
			'/(^|\.)(titleColor|taglineColor|descriptionColor|descColor|ratingIconColor|textColor|iconColor|backgroundColor|placeholderColor|buttonTitleColor|searchIconColor)\.(normal|hover|active)$/' => array( 'color' ),
			'/\.(family\.fontWeight)$/'            => array( 'enum', array( '100', '200', '300', '400', '500', '600', '700', '800', '900', 'normal', 'bold' ) ),
			'/\.(family\.textTransform)$/'         => array( 'enum', array( 'uppercase', 'lowercase', 'capitalize', 'none', '' ) ),
			'/\.(family\.textDecoration)$/'        => array( 'enum', array( 'underline', 'overline', 'line-through', 'none', '' ) ),
			'/\.(family\.style)$/'                 => array( 'enum', array( 'normal', 'italic', 'oblique', '' ) ),
			'/\.letterSpacing\.unit$/'             => array( 'enum', self::SPACING_UNITS ),
			'/\.wordSpacing\.unit$/'               => array( 'enum', self::SPACING_UNITS ),
		);

		/**
		 * Drop or coerce every AI-supplied value the target block cannot use.
		 *
		 * @param array  $ai_attributes       Raw parsed JSON attributes from the model.
		 * @param string $block_name          Target block slug, with or without the prefix.
		 * @param array  $existing_attributes Block's current attributes, for context the response omits.
		 * @param array  $dropped             Out-param: dotted paths that were rejected.
		 * @return array<string, mixed> Attributes safe to merge over the schema defaults.
		 */
		public static function validate( array $ai_attributes, $block_name, array $existing_attributes = array(), array &$dropped = array() ) {
			$dropped  = array();
			$slug     = AICatalogBuilder::slug( $block_name );
			$defaults = AICatalogBuilder::schema_defaults( $slug );

			$context = array( 'slug' => $slug );

			$clean = array();
			foreach ( $ai_attributes as $key => $value ) {
				if ( ! array_key_exists( $key, $defaults ) || in_array( $key, self::REJECTED_KEYS, true ) ) {
					$dropped[] = (string) $key;
					continue;
				}

				$filtered = self::filter_node( (string) $key, $value, $defaults[ $key ], $context, $dropped, 1 );
				if ( null !== $filtered ) {
					$clean[ $key ] = $filtered;
				}
			}

			// Layout is answered flat; every reader looks in `shared` and the
			// active style branch first. Fan it out before it is merged.
			if ( isset( $clean['layout'] ) && is_array( $clean['layout'] ) ) {
				$existing_layout = isset( $existing_attributes['layout'] ) && is_array( $existing_attributes['layout'] )
					? $existing_attributes['layout']
					: array();
				$clean['layout'] = AILayoutProfileWriter::expand( $clean['layout'], $slug, $existing_layout );
			}

			return $clean;
		}

		/**
		 * Validate one node, recursing into containers.
		 *
		 * @param string $path    Dotted attribute path.
		 * @param mixed  $value   AI-supplied value.
		 * @param mixed  $schema_default Schema default at this path, or null when unknown.
		 * @param array  $context Block slug.
		 * @param array  $dropped Out-param: rejected paths.
		 * @param int    $depth   1 for a top-level attribute group.
		 * @return mixed Cleaned value, or null when the node is rejected.
		 */
		private static function filter_node( $path, $value, $schema_default, array $context, array &$dropped, $depth ) {
			// A null is never a value the block can use, and merging it would
			// strip whatever real default sat there.
			if ( null === $value ) {
				$dropped[] = $path;
				return null;
			}

			// A suffix rule is a structural guess about a leaf, so it only speaks
			// for scalars: `…borderRadius.device.Desktop` is a per-side map on one
			// attribute and a plain size on another, and only the first should
			// recurse. An exact rule is a statement about the path itself and
			// still applies to a container — a group where a scalar belongs is a
			// mistake.
			$rule = self::exact_rule( $path );
			if ( null === $rule && ! is_array( $value ) ) {
				$rule = self::suffix_rule( $path );
			}

			if ( null !== $rule ) {
				$applied = self::apply_rule( $rule, $value, $context );
				if ( null === $applied ) {
					$dropped[] = $path;
				}
				return $applied;
			}

			if ( ! is_array( $value ) ) {
				return $value;
			}

			// Only first-level keys under a known group are checked against the
			// schema: deeper structure legitimately carries keys the default never
			// enumerates (per-device spacing entries, the background shape, the
			// layout branches this plugin's profile shape fans out to).
			$check_keys = 1 === $depth && is_array( $schema_default ) && ! empty( $schema_default ) && self::is_assoc( $schema_default );

			// `layout` is the one group answered flat by design, so its accepted
			// keys are not just the default's own — see
			// `AILayoutProfileWriter::allowed_fields()`.
			$allowed_keys = $check_keys && 'layout' === $path
				? AILayoutProfileWriter::allowed_fields( $context['slug'], $schema_default )
				: null;

			$filtered = array();
			foreach ( $value as $child_key => $child_value ) {
				$child_path = $path . '.' . $child_key;
				$is_known   = null !== $allowed_keys
					? in_array( (string) $child_key, $allowed_keys, true )
					: array_key_exists( $child_key, $schema_default );
				if ( $check_keys && ! $is_known ) {
					$dropped[] = $child_path;
					continue;
				}

				$child_default = is_array( $schema_default ) && array_key_exists( $child_key, $schema_default )
					? $schema_default[ $child_key ]
					: self::branch_default( $path, (string) $child_key, $schema_default, $context );
				$child_clean   = self::filter_node( $child_path, $child_value, $child_default, $context, $dropped, $depth + 1 );
				if ( null !== $child_clean ) {
					$filtered[ $child_key ] = $child_clean;
				}
			}

			return empty( $filtered ) ? null : $filtered;
		}

		/**
		 * Default for a flat `layout` field that only exists inside a branch.
		 *
		 * A profile-shaped layout (the Grid's) may declare a field only inside a
		 * branch, e.g. `layout.shared.columns`. Without this the recursion would descend with
		 * no default at all and lose the shape information a nested check needs.
		 *
		 * @param string $path           Parent path.
		 * @param string $child_key      Field name.
		 * @param array  $schema_default Parent's schema default.
		 * @param array  $context        Block slug.
		 * @return mixed Default value, or null when no branch declares one.
		 */
		private static function branch_default( $path, $child_key, array $schema_default, array $context ) {
			if ( 'layout' !== $path ) {
				return null;
			}

			$containers = AILayoutProfileWriter::CONTAINER_KEYS[ $context['slug'] ] ?? array( 'shared' );
			foreach ( $containers as $container ) {
				if ( isset( $schema_default[ $container ][ $child_key ] ) ) {
					return $schema_default[ $container ][ $child_key ];
				}
			}

			return null;
		}

		/**
		 * Resolve the rule declared for this exact path (`*` matches any key).
		 *
		 * @param string $path Dotted attribute path.
		 * @return array|null Rule tuple, or null when the path has no declared rule.
		 */
		private static function exact_rule( $path ) {
			if ( isset( self::RULES[ $path ] ) ) {
				return self::RULES[ $path ];
			}

			foreach ( self::RULES as $pattern => $rule ) {
				if ( false !== strpos( $pattern, '*' ) && self::wildcard_matches( $pattern, $path ) ) {
					return $rule;
				}
			}

			return null;
		}

		/**
		 * Resolve the rule for a leaf by its path tail.
		 *
		 * @param string $path Dotted attribute path.
		 * @return array|null Rule tuple, or null when the tail matches nothing.
		 */
		private static function suffix_rule( $path ) {
			foreach ( self::SUFFIX_RULES as $pattern => $rule ) {
				if ( 1 === preg_match( $pattern, $path ) ) {
					return $rule;
				}
			}

			return null;
		}

		/**
		 * Match a dotted path against a pattern whose `*` segments match any key.
		 *
		 * @param string $pattern Rule key, e.g. `logoImagesStyle.*.overlay.color`.
		 * @param string $path    Dotted attribute path.
		 * @return bool
		 */
		private static function wildcard_matches( $pattern, $path ) {
			$pattern_segments = explode( '.', $pattern );
			$path_segments    = explode( '.', $path );
			if ( count( $pattern_segments ) !== count( $path_segments ) ) {
				return false;
			}

			foreach ( $pattern_segments as $index => $segment ) {
				if ( '*' !== $segment && $segment !== $path_segments[ $index ] ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Apply one rule to one value.
		 *
		 * @param array $rule    Rule tuple.
		 * @param mixed $value   AI-supplied value.
		 * @param array $context Block slug.
		 * @return mixed Cleaned value, or null when rejected.
		 */
		private static function apply_rule( array $rule, $value, array $context ) {
			$kind = $rule[0];
			$arg  = isset( $rule[1] ) ? $rule[1] : null;

			switch ( $kind ) {
				case 'bool':
					return self::to_bool( $value );
				case 'int':
					return self::to_clamped_number( $value, $arg[0], $arg[1], true );
				case 'number':
					return self::to_clamped_number( $value, $arg[0], $arg[1], false );
				case 'enum':
					return self::to_enum( $value, is_string( $arg ) ? self::dynamic_enum( $arg, $context ) : $arg );
				case 'color':
					$color = CssUtils::sanitize_css_color( is_string( $value ) ? $value : '' );
					return '' !== $color ? $color : null;
				case 'text':
					return self::to_text( $value );
			}

			return null;
		}

		/**
		 * Resolve a `@name` enum.
		 *
		 * These lists are long enough that spelling them into `RULES` would bury
		 * the table, and they are generated from the same shape the editor's own
		 * pickers use.
		 *
		 * @param string $name    Dynamic enum name.
		 * @param array  $context Block slug.
		 * @return string[]
		 */
		private static function dynamic_enum( $name, array $context ) {
			unset( $context );

			if ( '@arrow_icons' === $name ) {
				// `arrowIcons.js` keys: arrow-01 … arrow-12.
				$icons = array();
				for ( $index = 1; $index <= 12; $index++ ) {
					$icons[] = sprintf( 'arrow-%02d', $index );
				}
				return $icons;
			}

			return array();
		}

		/**
		 * Coerce a boolean, accepting the string/int forms a model tends to emit.
		 *
		 * @param mixed $value Raw value.
		 * @return bool|null
		 */
		private static function to_bool( $value ) {
			if ( is_bool( $value ) ) {
				return $value;
			}
			if ( is_int( $value ) && ( 0 === $value || 1 === $value ) ) {
				return 1 === $value;
			}
			if ( ! is_string( $value ) ) {
				return null;
			}

			$normalized = strtolower( trim( $value ) );
			if ( in_array( $normalized, array( 'true', 'yes', '1' ), true ) ) {
				return true;
			}
			if ( in_array( $normalized, array( 'false', 'no', '0' ), true ) ) {
				return false;
			}

			return null;
		}

		/**
		 * Coerce and clamp a number.
		 *
		 * @param mixed $value  Raw value.
		 * @param float $min    Lower bound.
		 * @param float $max    Upper bound.
		 * @param bool  $is_int Whether to return an int.
		 * @return int|float|string|null
		 */
		private static function to_clamped_number( $value, $min, $max, $is_int ) {
			// The schema uses '' for "unset" on responsive values; clamping it up
			// to `min` would invent a size nobody asked for. `null` is not that —
			// it is a model slip, and storing it would strip the real default.
			if ( '' === $value ) {
				return '';
			}
			if ( null === $value ) {
				return null;
			}
			if ( is_bool( $value ) || ! is_numeric( $value ) ) {
				return null;
			}

			$number = min( (float) $max, max( (float) $min, (float) $value ) );
			if ( $is_int ) {
				return (int) round( $number );
			}

			// A whole number stays an int so a size lands in the saved attributes
			// as `20`, the way the control writes it, rather than `20.0`.
			return floor( $number ) === $number ? (int) $number : $number;
		}

		/**
		 * Match a value against an allow-list, tolerating case and surrounding space.
		 *
		 * @param mixed    $value   Raw value.
		 * @param string[] $allowed Allowed values.
		 * @return string|null Canonical allowed value, or null.
		 */
		private static function to_enum( $value, array $allowed ) {
			if ( is_int( $value ) || is_float( $value ) ) {
				$value = (string) $value;
			}
			if ( ! is_string( $value ) || empty( $allowed ) ) {
				return null;
			}

			$normalized = strtolower( trim( $value ) );
			foreach ( $allowed as $candidate ) {
				if ( strtolower( (string) $candidate ) === $normalized ) {
					return $candidate;
				}
			}

			return null;
		}

		/**
		 * Reduce a model-supplied label to a short single-line string.
		 *
		 * @param mixed $value Raw value.
		 * @return string|null
		 */
		private static function to_text( $value ) {
			if ( ! is_scalar( $value ) ) {
				return null;
			}

			$text = sanitize_text_field( (string) $value );

			return '' !== $text ? mb_substr( $text, 0, 120 ) : null;
		}

		/**
		 * True when the array is empty or keyed by strings (associative).
		 *
		 * @param array $candidate Array to test.
		 * @return bool
		 */
		private static function is_assoc( array $candidate ) {
			if ( empty( $candidate ) ) {
				return true;
			}

			return array_keys( $candidate ) !== range( 0, count( $candidate ) - 1 );
		}
	}
}
