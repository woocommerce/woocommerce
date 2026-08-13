<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

namespace Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags;

/**
 * The class represents a personalization tag that contains  all necessary information
 * for replacing the tag with its value and displaying it in the UI.
 */
class Personalization_Tag {
	/**
	 * Value type for tags whose callback returns an HTML fragment appropriate to the rendering
	 * context it receives. The Personalizer inserts the value untouched — the callback owns escaping.
	 */
	public const VALUE_TYPE_HTML = 'html';

	/**
	 * Value type for tags whose callback always returns raw plain text. The Personalizer
	 * escapes the value as needed for the rendering context (e.g. esc_html() in HTML content).
	 */
	public const VALUE_TYPE_TEXT = 'text';

	/**
	 * The name of the tag displayed in the UI.
	 *
	 * @var string
	 */
	private string $name;
	/**
	 * The token which is used in HTML_Tag_Processor to replace the tag with its value.
	 *
	 * @var string
	 */
	private string $token;
	/**
	 * The category of the personalization tag for categorization on the UI.
	 *
	 * @var string
	 */
	private string $category;
	/**
	 * The callback function which returns the value of the personalization tag.
	 *
	 * @var callable
	 */
	private $callback;
	/**
	 * The attributes which are used in the Personalization Tag UI.
	 *
	 * @var array
	 */
	private array $attributes;
	/**
	 * The value that is inserted via the UI. When the value is null the token is generated based on $token attribute and $attributes.
	 *
	 * @var string
	 */
	private string $value_to_insert;
	/**
	 * The list of supported post types.
	 *
	 * @var string[]
	 */
	private array $post_types;
	/**
	 * The type of value the callback returns — one of the VALUE_TYPE_* constants.
	 *
	 * @var string
	 */
	private string $value_type;

	/**
	 * Personalization_Tag constructor.
	 *
	 * Example usage:
	 *   $tag = new Personalization_Tag(
	 *     'First Name',
	 *     'user:first_name',
	 *     'User',
	 *      function( $context, $args ) {
	 *        return $context['user_firstname'] ?? 'user';
	 *      },
	 *      array( default => 'user' ),
	 *      'user:first default="user"'
	 *   );
	 *
	 * @param string      $name The name of the tag displayed in the UI.
	 * @param string      $token The token used in HTML_Tag_Processor to replace the tag with its value.
	 * @param string      $category The category of the personalization tag for categorization on the UI.
	 * @param callable    $callback The callback function which returns the value of the personalization tag.
	 * @param array       $attributes The attributes which are used in the Personalization Tag UI.
	 * @param string|null $value_to_insert The value that is inserted via the UI. When the value is null the token is generated based on $token attribute and $attributes.
	 * @param string[]    $post_types The list of supported post types.
	 * @param string      $value_type The type of value the callback returns — one of the VALUE_TYPE_* constants. Unknown values fall back to VALUE_TYPE_HTML.
	 */
	public function __construct(
		string $name,
		string $token,
		string $category,
		callable $callback,
		array $attributes = array(),
		?string $value_to_insert = null,
		array $post_types = array(),
		string $value_type = self::VALUE_TYPE_HTML
	) {
		$this->name = $name;
		// Because Gutenberg does not wrap the token with square brackets, we need to add them here.
		$this->token      = strpos( $token, '[' ) === 0 ? $token : "[$token]";
		$this->category   = $category;
		$this->callback   = $callback;
		$this->attributes = $attributes;
		// Composing token to insert based on the token and attributes if it is not set.
		if ( ! $value_to_insert ) {
			if ( $this->attributes ) {
				$value_to_insert = substr( $this->token, 0, -1 ) . ' ' .
					implode(
						' ',
						array_map(
							function ( $key ) {
								return $key . '="' . esc_attr( $this->attributes[ $key ] ) . '"';
							},
							array_keys( $this->attributes )
						)
					) . ']';
			} else {
				$value_to_insert = $this->token;
			}
		}
		$this->value_to_insert = $value_to_insert;
		$this->post_types      = $post_types;
		$this->value_type      = in_array( $value_type, array( self::VALUE_TYPE_HTML, self::VALUE_TYPE_TEXT ), true ) ? $value_type : self::VALUE_TYPE_HTML;
	}

	/**
	 * Prevents deserialization of this class to avoid callback replacement attacks.
	 *
	 * @param array $data The serialized data.
	 * @return void
	 * @throws \Exception Always throws an exception to prevent deserialization.
	 */
	public function __unserialize( array $data ): void {
		throw new \Exception( 'Deserialization of Personalization_Tag is not allowed for security reasons.' );
	}

	/**
	 * Returns the name of the personalization tag.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Returns the token of the personalization tag.
	 *
	 * @return string
	 */
	public function get_token(): string {
		return $this->token;
	}

	/**
	 * Returns the category of the personalization tag.
	 *
	 * @return string
	 */
	public function get_category(): string {
		return $this->category;
	}

	/**
	 * Returns the attributes of the personalization tag.
	 *
	 * @return array
	 */
	public function get_attributes(): array {
		return $this->attributes;
	}

	/**
	 * Returns the token to insert via UI in the editor.
	 *
	 * @return string
	 */
	public function get_value_to_insert(): string {
		return $this->value_to_insert;
	}

	/**
	 * Returns the list of supported post types.
	 *
	 * @return array|string[]
	 */
	public function get_post_types(): array {
		return $this->post_types;
	}

	/**
	 * Returns the type of value the callback returns — one of the VALUE_TYPE_* constants.
	 *
	 * @return string
	 */
	public function get_value_type(): string {
		return $this->value_type;
	}

	/**
	 * Returns the callback function of the personalization tag.
	 *
	 * @return callable
	 */
	public function get_callback(): callable {
		return $this->callback;
	}

	/**
	 * Executes the callback function for the personalization tag.
	 *
	 * @param mixed $context The context for the personalization tag.
	 * @param array $args The additional arguments for the callback.
	 * @return string The value of the personalization tag.
	 */
	public function execute_callback( $context, $args = array() ): string {
		return call_user_func( $this->callback, ...array_merge( array( $context ), array( $args ) ) );
	}
}
