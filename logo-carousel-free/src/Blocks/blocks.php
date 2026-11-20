<?php
/**
 * Block Metadata Registry
 *
 * Auto-generated from editor-side block.json files.
 *
 * @package Logo_Carousel_Pro
 */

defined( 'ABSPATH' ) || exit;

return array(
	'carousel' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'sp-logo-carousel/carousel',
		'title' => 'Carousel',
		'description' => 'Display logos in a rotating carousel layout.',
		'category' => 'sp-logo-carousel',
		'keywords' => array(
			'carousel',
			'slider',
			'test'
		),
		'textdomain' => 'logo-carousel-free',
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'customClassName' => false,
			'anchor' => false
		),
		'example' => array(
			'attributes' => array(
				'isPreview' => true
			)
		)
	),

	'carousel3d' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'sp-logo-carousel/carousel3d',
		'title' => '3D Carousel',
		'description' => 'Showcase logos in a rotating 3D carousel.',
		'category' => 'sp-logo-carousel',
		'keywords' => array(
			'3d',
			'carousel',
			'coverflow',
			'ring',
			'perspective',
			'logos'
		),
		'textdomain' => 'logo-carousel-free',
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'customClassName' => false,
			'anchor' => false
		),
		'example' => array(
			'attributes' => array(
				'isPreview' => true
			)
		)
	),

	'grid' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'sp-logo-carousel/grid',
		'title' => 'Grid',
		'description' => 'Display logos evenly in a responsive multi-column grid.',
		'category' => 'sp-logo-carousel',
		'keywords' => array(
			'grid',
			'logos',
			'gallery',
			'columns'
		),
		'textdomain' => 'logo-carousel-free',
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'customClassName' => false,
			'anchor' => false
		),
		'example' => array(
			'attributes' => array(
				'isPreview' => true
			)
		)
	),

	'masonry' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'sp-logo-carousel/masonry',
		'title' => 'Masonry',
		'description' => 'Display logos in a dynamic masonry layout with varying sizes.',
		'category' => 'sp-logo-carousel',
		'keywords' => array(
			'masonry',
			'logos',
			'columns',
			'pinterest'
		),
		'textdomain' => 'logo-carousel-free',
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'html' => false,
			'customClassName' => false,
			'anchor' => false
		),
		'example' => array(
			'attributes' => array(
				'isPreview' => true
			)
		)
	)
);
