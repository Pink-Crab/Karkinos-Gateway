<?php
/**
 * Dev Asset meta box view.
 *
 * Vars come from Dev_Asset::meta_boxes() view_data_filter():
 *
 * @var \WP_Post                                $post
 * @var \PinkCrab\Perique\Application\App_Config $config
 * @var array<int, string>                       $types
 * @var string                                   $nonce_action
 * @var string                                   $nonce_field
 *
 * Inside this template `$this` is the Perique View — `$this->component(...)`
 * renders Form Components. wp.media picker glue lives in
 * assets/dev-asset-media.js (enqueued by Dev_Asset_Media_Picker).
 *
 * @package Karkinos\Gateway
 */

use Karkinos\Gateway\PostType\Dev_Asset;
use PinkCrab\Form_Components\Util\Make;

$type_key      = $config->post_meta( 'dev_asset_type' );
$url_key       = $config->post_meta( 'dev_asset_url' );
$attachment_id = $config->post_meta( 'dev_asset_attachment_id' );

$current_type       = (string) get_post_meta( $post->ID, $type_key, true );
$current_url        = (string) get_post_meta( $post->ID, $url_key, true );
$current_attachment = (int) get_post_meta( $post->ID, $attachment_id, true );

if ( '' === $current_type ) {
	$current_type = Dev_Asset::TYPE_SNIPPET;
}

$type_options = array(
	Dev_Asset::TYPE_SNIPPET => __( 'Snippet', 'karkinos-gateway' ),
	Dev_Asset::TYPE_LINK    => __( 'Link', 'karkinos-gateway' ),
	Dev_Asset::TYPE_FILE    => __( 'File', 'karkinos-gateway' ),
);

// Pre-render the picker's title + preview (JS keeps these in sync on user action).
//
// Image attachments get a full-size <img> in the preview and a plain-text
// title. Non-image attachments leave the preview empty and render the title
// as a clickable link (with the mime icon as a visual cue) so the user can
// open the file in a new tab.
$preview_html = '';
$title_html   = esc_html__( 'No file attached', 'karkinos-gateway' );

if ( $current_attachment > 0 ) {
	$existing_title = (string) get_the_title( $current_attachment );
	$label          = '' !== $existing_title ? $existing_title : '#' . $current_attachment;
	$mime           = (string) get_post_mime_type( $current_attachment );

	if ( str_starts_with( $mime, 'image/' ) ) {
		$preview_src = wp_get_attachment_image_url( $current_attachment, 'full' );
		if ( $preview_src ) {
			$preview_html = sprintf(
				'<img src="%s" alt="%s" />',
				esc_url( $preview_src ),
				esc_attr( $label )
			);
		}
		$title_html = esc_html( $label );
	} else {
		$file_url = (string) wp_get_attachment_url( $current_attachment );
		$icon_url = (string) wp_mime_type_icon( $current_attachment );
		$title_html = sprintf(
			'<a href="%s" target="_blank" rel="noopener">%s<span>%s</span></a>',
			esc_url( $file_url ),
			'' !== $icon_url ? sprintf( '<img src="%s" alt="" />', esc_url( $icon_url ) ) : '',
			esc_html( $label )
		);
	}
}

$file_section = sprintf(
	'<div class="kg-da-section">' .
		'<span class="kg-da-section-title">%1$s</span>' .
		'<div class="kg-da-file-preview">' .
			'<span data-kg-da-preview="%2$s">%3$s</span>' .
			'<strong data-kg-da-title="%2$s">%4$s</strong>' .
		'</div>' .
		'<p>' .
			'<button type="button" class="button kg-da-media-select" data-key="%2$s" data-frame-title="%5$s" data-button-text="%6$s">%7$s</button>' .
			'<button type="button" class="button-link kg-da-media-clear" data-key="%2$s">%8$s</button>' .
		'</p>' .
	'</div>',
	esc_html__( 'File', 'karkinos-gateway' ),
	esc_attr( $attachment_id ),
	$preview_html,
	$title_html,
	esc_attr__( 'Select asset file', 'karkinos-gateway' ),
	esc_attr__( 'Use this file', 'karkinos-gateway' ),
	esc_html__( 'Select / change file', 'karkinos-gateway' ),
	esc_html__( 'Remove', 'karkinos-gateway' )
);

$this->component( Make::nonce( $nonce_action, $nonce_field ) );

// Wrapper open — CSS lives in assets/dev-asset-media.css, enqueued by
// Dev_Asset_Media_Picker on the dev_asset edit screen.
$this->component( Make::raw_html( 'kg_da_open', '<div class="kg-da-meta">' ) );

$this->component(
	Make::select(
		$type_key,
		fn( $f ) => $f
			->label( __( 'Type', 'karkinos-gateway' ) )
			->options( $type_options )
			->value( $current_type )
	)
);

$this->component(
	Make::url(
		$url_key,
		fn( $f ) => $f
			->label( __( 'URL', 'karkinos-gateway' ) )
			->value( $current_url )
			->placeholder( 'https://…' )
	)
);

// File picker: hidden input + visual chrome.
$this->component(
	Make::hidden(
		$attachment_id,
		fn( $f ) => $f
			->value( $current_attachment > 0 ? (string) $current_attachment : '' )
			->attribute( 'data-kg-da-attachment', $attachment_id )
	)
);

$this->component( Make::raw_html( $attachment_id . '_picker', $file_section ) );

// Wrapper close.
$this->component( Make::raw_html( 'kg_da_close', '</div>' ) );
