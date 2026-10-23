<?php
/**
 * Canonical Ready Patterns browse vocabularies.
 *
 * @package ShapedPlugin\LogoCarouselFree\Includes\ReadyPatterns
 */

namespace ShapedPlugin\LogoCarouselFree\Includes\ReadyPatterns;

defined( 'ABSPATH' ) || exit;

/**
 * Supplies human labels and a stable display order for pattern taxonomy terms.
 *
 * These lists label and order; they never decide which patterns are shown. A term
 * the plugin has never heard of is still listed, humanized, and filterable — the
 * category→block map became a silent gate exactly because that was left implicit.
 */
class PatternVocabulary {

	/**
	 * Canonical use-case slugs in display order.
	 *
	 * @var string[]
	 */
	public const USE_CASES = array(
		'hero',
		'features',
		'portfolio',
		'gallery',
		'testimonials',
		'team',
		'logos',
		'pricing',
		'cta',
		'faq',
		'blog',
		'shop',
		'video',
		'audio',
		'events',
		'listings',
		'education',
		'hospitality',
		'downloads',
		'social-feed',
		'page',
	);

	/**
	 * Canonical complexity slugs in display order.
	 *
	 * @var string[]
	 */
	public const COMPLEXITY_LEVELS = array( 'starter', 'standard', 'showcase' );

	/**
	 * Recommended layout-trait tags in display order (free-form tags are still accepted).
	 *
	 * @var string[]
	 */
	public const RECOMMENDED_TRAITS = array(
		'fullscreen',
		'boxed',
		'overlay',
		'classic',
		'bento',
		'vertical',
		'filterable',
		'autoplay',
		'webgl',
		'lightbox',
		'ajax',
		'dark',
		'light',
		'minimal',
		'bold',
	);

	/**
	 * Tag that declares a pattern was designed on a dark background.
	 */
	public const DARK_TRAIT = 'dark';

	/**
	 * Use-case slug → translated label.
	 *
	 * @return array<string, string>
	 */
	public static function use_case_labels(): array {
		return array(
			'hero'         => __( 'Hero', 'logo-carousel-free' ),
			'features'     => __( 'Features', 'logo-carousel-free' ),
			'portfolio'    => __( 'Portfolio', 'logo-carousel-free' ),
			'gallery'      => __( 'Gallery', 'logo-carousel-free' ),
			'testimonials' => __( 'Testimonials', 'logo-carousel-free' ),
			'team'         => __( 'Team', 'logo-carousel-free' ),
			'logos'        => __( 'Logos', 'logo-carousel-free' ),
			'pricing'      => __( 'Pricing', 'logo-carousel-free' ),
			'cta'          => __( 'Call to Action', 'logo-carousel-free' ),
			'faq'          => __( 'FAQ', 'logo-carousel-free' ),
			'blog'         => __( 'Blog', 'logo-carousel-free' ),
			'shop'         => __( 'Shop', 'logo-carousel-free' ),
			'video'        => __( 'Video', 'logo-carousel-free' ),
			'audio'        => __( 'Audio', 'logo-carousel-free' ),
			'events'       => __( 'Events', 'logo-carousel-free' ),
			'listings'     => __( 'Listings', 'logo-carousel-free' ),
			'education'    => __( 'Education', 'logo-carousel-free' ),
			'hospitality'  => __( 'Hospitality', 'logo-carousel-free' ),
			'downloads'    => __( 'Downloads', 'logo-carousel-free' ),
			'social-feed'  => __( 'Social Feed', 'logo-carousel-free' ),
			'page'         => __( 'Full Page', 'logo-carousel-free' ),
		);
	}

	/**
	 * Complexity slug → translated label.
	 *
	 * @return array<string, string>
	 */
	public static function complexity_labels(): array {
		return array(
			'starter'  => __( 'Starter', 'logo-carousel-free' ),
			'standard' => __( 'Standard', 'logo-carousel-free' ),
			'showcase' => __( 'Showcase', 'logo-carousel-free' ),
		);
	}

	/**
	 * Label for a use-case term, humanizing terms outside the canonical list.
	 *
	 * @param string $term Use-case slug.
	 * @return string
	 */
	public static function use_case_label( string $term ): string {
		$labels = self::use_case_labels();

		return $labels[ $term ] ?? self::humanize( $term );
	}

	/**
	 * Label for a complexity term, humanizing unknown terms.
	 *
	 * @param string $term Complexity slug.
	 * @return string
	 */
	public static function complexity_label( string $term ): string {
		$labels = self::complexity_labels();

		return $labels[ $term ] ?? self::humanize( $term );
	}

	/**
	 * Order terms canonically first, then unknown terms alphabetically.
	 *
	 * @param string[] $terms     Terms present in the manifest.
	 * @param string[] $canonical Canonical order to apply first.
	 * @return string[]
	 */
	public static function order_terms( array $terms, array $canonical ): array {
		$present = array_values( array_unique( array_filter( array_map( 'strval', $terms ) ) ) );
		$known   = array_values( array_intersect( $canonical, $present ) );
		$unknown = array_values( array_diff( $present, $canonical ) );
		sort( $unknown );

		return array_merge( $known, $unknown );
	}

	/**
	 * Turn a slug into a readable label ("real-estate" → "Real Estate").
	 *
	 * @param string $term Slug.
	 * @return string
	 */
	public static function humanize( string $term ): string {
		return ucwords( str_replace( array( '-', '_' ), ' ', $term ) );
	}
}
