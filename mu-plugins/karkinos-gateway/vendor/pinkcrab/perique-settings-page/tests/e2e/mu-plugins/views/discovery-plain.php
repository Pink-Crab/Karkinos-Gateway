<?php
/**
 * View template for Test_Discovery_Plain_Page.
 *
 * View_data keys are extracted into local scope by PHP_Engine::render_buffer().
 *
 * @var string $marker
 */
$marker = isset( $marker ) ? (string) $marker : '';
?>
<div class="wrap">
	<h1>Discovery Plain Page</h1>
	<p id="discovery-plain-marker"><?php echo esc_html( $marker ); ?></p>
</div>
