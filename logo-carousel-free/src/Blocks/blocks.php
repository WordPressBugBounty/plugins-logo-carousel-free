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
	)
);
