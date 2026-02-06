<?php
/**
 * Single item from loop.
 *
 * @package    logo-carousel-free
 * @subpackage logo-carousel-free/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

while ( $args->have_posts() ) :
	$args->the_post();

	$ids       = get_the_ID();
	$lcp_thumb = get_post_thumbnail_id();
	$image_url = wp_get_attachment_image_src( $lcp_thumb, $image_sizes );
	if ( ! $image_url ) {
		continue;
	}
	$the_image_title_attr = the_title_attribute( array( 'echo' => false ) );
	$image_title_attr     = $show_image_title_attr ? $the_image_title_attr : '';
	$logo_image_alt       = get_post_meta( $lcp_thumb, '_wp_attachment_image_alt', true );
	$logo_image_alt_tag   = ! empty( $logo_image_alt ) ? $logo_image_alt : get_the_title();
	$lazy_load_class      = 'true' === $enable_lazy_load ? ' lcp-lazyload' : '';
	$src_url              = 'true' === $enable_lazy_load ? SP_LC_URL . 'admin/assets/images/spinner.svg' : $image_url[0];
	$data_src             = 'true' === $enable_lazy_load ? 'data-src="' . esc_url( $image_url[0] ) . '"' : '';

	$image = has_post_thumbnail() && $show_image ? sprintf( '<img %1$s src="%2$s" title="%3$s" alt="%4$s" width="%5$s" height="%6$s" class="sp-lc-image%7$s">', $data_src, esc_url( $src_url ), esc_attr( $image_title_attr ), esc_attr( $logo_image_alt_tag ), esc_attr( $image_url[1] ), esc_attr( $image_url[2] ), esc_attr( $lazy_load_class ) ) : '';
	?>

	<div class="sp-lc-grid-item sp-lc-col-xl-<?php echo esc_attr( $items ); ?> sp-lc-col-lg-<?php echo esc_attr( $items_desktop ); ?> sp-lc-col-md-<?php echo esc_attr( $items_desktop_small ); ?> sp-lc-col-sm-<?php echo esc_attr( $items_tablet ); ?> sp-lc-col-xs-<?php echo esc_attr( $items_mobile ); ?>">
		<div class="sp-lc-logo">
			<?php echo wp_kses_post( $image ); ?>
		</div>	
	</div>

	<?php
endwhile;
wp_reset_postdata();
