<?php

declare( strict_types=1 );

/**
 * Post Picker field — async search via REST API.
 *
 * Replaces the static Post_Selector with an interactive
 * search-as-you-type component backed by a REST endpoint.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\Perique_Settings_Page
 */

namespace PinkCrab\Perique_Settings_Page\Setting\Field;

use PinkCrab\Perique_Settings_Page\Setting\Field\Field;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Data;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Query;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Multiple;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Disabled;
use PinkCrab\Perique_Settings_Page\Setting\Field\Attribute\Placeholder;

class Post_Picker extends Field {

	/**
	 * The type of field.
	 */
	public const TYPE = 'post_picker';

	use Multiple, Data, Disabled, Placeholder, Query;

	/**
	 * Static constructor for field.
	 *
	 * @param string $key
	 * @return static
	 */
	public static function new( string $key ): static {
		return new static( $key );
	}

	public function __construct( string $key ) {
		parent::__construct( $key, self::TYPE );
	}

	/**
	 * Convenience setter for the post type to search.
	 *
	 * @param string $post_type
	 * @return static
	 */
	public function set_post_type( string $post_type ): static {
		$args              = $this->get_query_args();
		$args['post_type'] = $post_type;
		$this->set_query_args( $args );
		return $this;
	}

	/**
	 * Gets the post type to search, defaults to 'post'.
	 *
	 * @return string
	 */
	public function get_post_type(): string {
		$args = $this->get_query_args();
		return is_string( $args['post_type'] ?? null ) ? $args['post_type'] : 'post';
	}

	/**
	 * Returns the defined label callback or fallback to post title.
	 *
	 * @return callable(\WP_Post): string
	 */
	public function get_option_label(): callable {
		return $this->callbacks['option_label'] ?? function ( \WP_Post $post ): string {
			return $post->post_title;
		};
	}

	/**
	 * Returns the defined value callback or fallback to post ID.
	 *
	 * @return callable(\WP_Post): string
	 */
	public function get_option_value(): callable {
		return $this->callbacks['option_value'] ?? function ( \WP_Post $post ): string {
			return (string) $post->ID;
		};
	}
}
