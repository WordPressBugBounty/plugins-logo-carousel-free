<?php
/**
 * Beaver Builder - Logo Carousel Module Frontend Template
 *
 * Loaded by Beaver Builder when rendering the module. `$module`, `$id` and
 * `$settings` are provided by Beaver's rendering engine.
 *
 * @since 4.0.0
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/Admin/PageBuilders
 *
 * @var LCPRO_Beaver_Logo_Carousel_Module $module   The module instance.
 * @var string                            $id       The module's unique node ID.
 * @var object                            $settings The module's saved settings.
 */

use ShapedPlugin\LogoCarouselFree\Admin\PageBuilders\Base\Template_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Cannot access directly.
}

$lcpro_template_html = Template_Renderer::render(
	isset( $settings->template_id ) ? (int) $settings->template_id : 0,
	'lcpro-beaver-template-wrapper',
	class_exists( 'FLBuilderModel' ) && \FLBuilderModel::is_builder_active()
);

echo $lcpro_template_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block markup, escaped by the block renderers.
