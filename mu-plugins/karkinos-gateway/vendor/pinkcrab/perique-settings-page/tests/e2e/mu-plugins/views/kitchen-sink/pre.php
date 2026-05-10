<?php
/**
 * Kitchen sink pre template — wired via $pre_template property default
 * on Test_Kitchen_Sink_Page. Exercises the "static" path: no runtime
 * data, just a template file referenced by name.
 *
 * @var string $heading
 */
?>
<div id="kitchen-sink-pre" data-testid="kitchen-sink-pre" class="pc-settings-pre">
	<h2><?php echo esc_html( (string) ( $heading ?? 'Pre heading' ) ); ?></h2>
	<p>Rendered from the static <code>$pre_template</code> property.</p>
</div>
