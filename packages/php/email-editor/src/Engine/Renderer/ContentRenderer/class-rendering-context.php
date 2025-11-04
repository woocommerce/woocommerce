<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer;

use Automattic\WooCommerce\EmailEditor\Integrations\Utils\Styles_Helper;
use WP_Theme_JSON;

/**
 * Class Rendering_Context
 */
class Rendering_Context {
	/**
	 * Instance of the WP Theme.
	 *
	 * @var WP_Theme_JSON
	 */
	private WP_Theme_JSON $theme_json;

	/**
	 * Email-specific context data.
	 *
	 * This array contains email-specific information that can be used during rendering,
	 * such as:
	 * - 'user_id': The ID of the user receiving the email
	 * - 'recipient_email': The recipient's email address
	 * - Additional context can be added by extensions using the generic get() method
	 *
	 * @var array<string, mixed>
	 */
	private array $email_context;

	/**
	 * Rendering_Context constructor.
	 *
	 * @param WP_Theme_JSON        $theme_json Theme Json used in the email.
	 * @param array<string, mixed> $email_context Email-specific context data.
	 */
	public function __construct( WP_Theme_JSON $theme_json, array $email_context = array() ) {
		$this->theme_json    = $theme_json;
		$this->email_context = $email_context;
	}

	/**
	 * Returns WP_Theme_JSON instance that should be used during the email rendering.
	 *
	 * @return WP_Theme_JSON
	 */
	public function get_theme_json(): WP_Theme_JSON {
		return $this->theme_json;
	}

	/**
	 * Get the email theme styles.
	 *
	 * @return array{
	 *   spacing: array{
	 *     blockGap: string,
	 *     padding: array{bottom: string, left: string, right: string, top: string}
	 *   },
	 *   color: array{
	 *     background: string,
	 *     text: string
	 *   },
	 *   typography: array{
	 *     fontFamily: string
	 *   }
	 * }
	 */
	public function get_theme_styles(): array {
		$theme = $this->get_theme_json();
		return $theme->get_data()['styles'] ?? array();
	}

	/**
	 * Get settings from the theme.
	 *
	 * @return array
	 */
	public function get_theme_settings() {
		return $this->get_theme_json()->get_settings();
	}

	/**
	 * Returns the width of the layout without padding.
	 *
	 * @return string
	 */
	public function get_layout_width_without_padding(): string {
		$styles          = $this->get_theme_styles();
		$layout_settings = $this->get_theme_settings()['layout'] ?? array();
		$width           = Styles_Helper::parse_value( $layout_settings['contentSize'] ?? '0px' );
		$padding         = $styles['spacing']['padding'] ?? array();
		$width          -= Styles_Helper::parse_value( $padding['left'] ?? '0px' );
		$width          -= Styles_Helper::parse_value( $padding['right'] ?? '0px' );
		return "{$width}px";
	}

	/**
	 * Translate color slug to color.
	 *
	 * @param string $color_slug Color slug.
	 * @return string
	 */
	public function translate_slug_to_color( string $color_slug ): string {
		$settings = $this->get_theme_settings();

		$color_definitions = array_merge(
			$settings['color']['palette']['theme'] ?? array(),
			$settings['color']['palette']['default'] ?? array()
		);
		foreach ( $color_definitions as $color_definition ) {
			if ( $color_definition['slug'] === $color_slug ) {
				return strtolower( $color_definition['color'] );
			}
		}
		return $color_slug;
	}

	/**
	 * Get the email-specific context data.
	 *
	 * @return array<string, mixed>
	 */
	public function get_email_context(): array {
		return $this->email_context;
	}

	/**
	 * Get the user ID from the email context.
	 *
	 * @return int|null The user ID if available, null otherwise.
	 */
	public function get_user_id(): ?int {
		return isset( $this->email_context['user_id'] ) && is_numeric( $this->email_context['user_id'] ) ? (int) $this->email_context['user_id'] : null;
	}

	/**
	 * Get the recipient email address from the email context.
	 *
	 * @return string|null The email address if available, null otherwise.
	 */
	public function get_recipient_email(): ?string {
		return isset( $this->email_context['recipient_email'] ) && is_string( $this->email_context['recipient_email'] ) ? $this->email_context['recipient_email'] : null;
	}

	/**
	 * Get a specific value from the email context.
	 *
	 * This method allows extensions to access custom context data that may be
	 * specific to their implementation (e.g., order IDs, email types, etc.).
	 *
	 * @param string $key The context key.
	 * @param mixed  $default_value Default value if key is not found.
	 * @return mixed The context value or default.
	 */
	public function get( string $key, $default_value = null ) {
		return $this->email_context[ $key ] ?? $default_value;
	}
}
