<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare( strict_types = 1 );
namespace Automattic\WooCommerce\EmailEditor\Integrations\Utils;

/**
 * This class should provide helper functions for the Social Links block.
 */
class Social_Links_Helper {
	/**
	 * Detects if the input color is whiteish.
	 * This is a helper function to detect if the input color is white or white-ish colors when
	 * provided an input color in format #ffffff or #fff.
	 *
	 * @param string $input_color The input color.
	 * @return bool True if the color is whiteish, false otherwise.
	 */
	public static function detect_whiteish_color( $input_color ) {

		if ( empty( $input_color ) ) {
			return false;
		}

		// Remove # if present.
		$color = ltrim( $input_color, '#' );

		// Convert 3-digit hex to 6-digit hex.
		if ( strlen( $color ) === 3 ) {
			$color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
		}

		// Convert hex to RGB.
		$r = hexdec( substr( $color, 0, 2 ) );
		$g = hexdec( substr( $color, 2, 2 ) );
		$b = hexdec( substr( $color, 4, 2 ) );

		// Calculate brightness using perceived brightness formula.
		// Using the formula: (0.299*R + 0.587*G + 0.114*B).
		$brightness = ( 0.299 * $r + 0.587 * $g + 0.114 * $b );

		// Consider colors with brightness above 240 as whiteish.
		// This threshold can be adjusted based on requirements.
		return $brightness > 240;
	}

	/**
	 * Gets the brand color for a given service.
	 *
	 * From core: https://github.com/WordPress/gutenberg/blob/edbd36057d3d25b7140af9e90a2adcca02a9201c/packages/block-library/src/social-link/socials-without-bg.scss
	 *
	 * @param string $service_name The name of the service.
	 * @return string The brand color for the service.
	 */
	public static function get_service_brand_color( $service_name ) {
		$service_brand_color = array(
			'amazon'        => '#f90',
			'bandcamp'      => '#1ea0c3',
			'behance'       => '#0757fe',
			'bluesky'       => '#0a7aff',
			'codepen'       => '#1e1f26',
			'deviantart'    => '#02e49b',
			'discord'       => '#5865f2',
			'dribbble'      => '#e94c89',
			'dropbox'       => '#4280ff',
			'etsy'          => '#f45800',
			'facebook'      => '#0866ff',
			'fivehundredpx' => '#000',
			'flickr'        => '#0461dd',
			'foursquare'    => '#e65678',
			'github'        => '#24292d',
			'goodreads'     => '#382110',
			'google'        => '#ea4434',
			'gravatar'      => '#1d4fc4',
			'instagram'     => '#f00075',
			'lastfm'        => '#e21b24',
			'linkedin'      => '#0d66c2',
			'mastodon'      => '#3288d4',
			'medium'        => '#000',
			'meetup'        => '#f6405f',
			'patreon'       => '#000',
			'pinterest'     => '#e60122',
			'pocket'        => '#ef4155',
			'reddit'        => '#ff4500',
			'skype'         => '#0478d7',
			'snapchat'      => '#fff',
			'soundcloud'    => '#ff5600',
			'spotify'       => '#1bd760',
			'telegram'      => '#2aabee',
			'threads'       => '#000',
			'tiktok'        => '#000',
			'tumblr'        => '#011835',
			'twitch'        => '#6440a4',
			'twitter'       => '#1da1f2',
			'vimeo'         => '#1eb7ea',
			'vk'            => '#4680c2',
			'whatsapp'      => '#25d366',
			'wordpress'     => '#3499cd',
			'x'             => '#000',
			'yelp'          => '#d32422',
			'youtube'       => '#f00',
		);
		return $service_brand_color[ $service_name ] ?? '';
	}

	/**
	 * Gets the default social link size.
	 *
	 * @return string The default social link size.
	 */
	public static function get_default_social_link_size() {
		return 'has-normal-icon-size';
	}

	/**
	 * Gets the size option value for a given size.
	 * Source: https://github.com/WordPress/gutenberg/blob/c7c09cfe16c78f9a949956e5d0088cd4c747bdca/packages/block-library/src/social-links/style.scss#L36-L56
	 *
	 * @param string $size The size.
	 * @return string The size option value.
	 */
	public static function get_social_link_size_option_value( $size ) {
		$options = array(
			'has-small-icon-size'  => '16px',
			'has-normal-icon-size' => '24px',
			'has-large-icon-size'  => '36px',
			'has-huge-icon-size'   => '48px',
		);
		return $options[ $size ] ?? '24px'; // default to normal size.
	}
}
