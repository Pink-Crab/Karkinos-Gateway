<?php
/**
 * Kitchen sink post template — wired at render time via before_render()
 * on Test_Kitchen_Sink_Page. Exercises the "runtime" path: data is
 * populated from the live settings instance on every render.
 *
 * @var string $text_basic_value The current value of the text_basic field.
 * @var int    $number_basic_value The current value of the number_basic field.
 */
?>
<div id="kitchen-sink-post" data-testid="kitchen-sink-post" class="pc-settings-post">
	<p>Rendered from <code>before_render()</code> with runtime data.</p>
	<ul>
		<li>text_basic: <span data-testid="post-text-basic"><?php echo esc_html( (string) ( $text_basic_value ?? '' ) ); ?></span></li>
		<li>number_basic: <span data-testid="post-number-basic"><?php echo (int) ( $number_basic_value ?? 0 ); ?></span></li>
	</ul>
</div>
