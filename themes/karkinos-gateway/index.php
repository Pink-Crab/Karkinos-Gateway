<?php
/**
 * Karkinos Gateway theme front page.
 *
 * @package KarkinosGateway
 */

$image_url = karkinos_gateway_random_bg_url();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<title><?php bloginfo( 'name' ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php if ( $image_url ) : ?>
		<img class="gateway-image" src="<?php echo esc_url( $image_url ); ?>" alt="">
	<?php endif; ?>
	<?php wp_footer(); ?>
</body>
</html>
