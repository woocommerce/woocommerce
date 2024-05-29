<?php

namespace Automattic\WooCommerce\Internal\Admin\Onboarding;

use Automattic\WooCommerce\Internal\Font\FontFace;
use Automattic\WooCommerce\Internal\Font\FontFamily;


/**
 * Class to install fonts for the Assembler.
 *
 * @internal
 */
class OnboardingFonts {

	/**
	 * Initialize the class.
	 *
	 * @internal This method is for internal purposes only.
	 */
	final public static function init() {
		add_action( 'woocommerce_install_assembler_fonts', array( __CLASS__, 'install_fonts' ) );
		add_filter( 'update_option_woocommerce_allow_tracking', array( self::class, 'start_install_fonts_async_job' ), 10, 2 );
	}

	const SOURCE_LOGGER = 'font_loader';

	/**
	 * Font families to install.
	 * PHP version of https://github.com/woocommerce/woocommerce/blob/45923dc5f38150c717210ae9db10045cd9582331/plugins/woocommerce-admin/client/customize-store/assembler-hub/sidebar/global-styles/font-pairing-variations/constants.ts/#L13-L74
	 *
	 * @var array
	 */
	const FONT_FAMILIES_TO_INSTALL = array(
		'inter'       => array(
			'fontFamily'  => 'Inter',
			'fontWeights' => array( '400', '500', '600' ),
			'fontStyles'  => array( 'normal' ),
		),
		'bodoni-moda' => array(
			'fontFamily'  => 'Bodoni Moda',
			'fontWeights' => array( '400' ),
			'fontStyles'  => array( 'normal' ),
		),
		'overpass'    => array(
			'fontFamily'  => 'Overpass',
			'fontWeights' => array( '300', '400' ),
			'fontStyles'  => array( 'normal' ),
		),
		'albert-sans' => array(
			'fontFamily'  => 'Albert Sans',
			'fontWeights' => array( '700' ),
			'fontStyles'  => array( 'normal' ),
		),
		'lora'        => array(
			'fontFamily'  => 'Lora',
			'fontWeights' => array( '400' ),
			'fontStyles'  => array( 'normal' ),
		),
		'montserrat'  => array(
			'fontFamily'  => 'Montserrat',
			'fontWeights' => array( '500', '700' ),
			'fontStyles'  => array( 'normal' ),
		),
		'arvo'        => array(
			'fontFamily'  => 'Arvo',
			'fontWeights' => array( '400' ),
			'fontStyles'  => array( 'normal' ),
		),
		'rubik'       => array(
			'fontFamily'  => 'Rubik',
			'fontWeights' => array( '400', '800' ),
			'fontStyles'  => array( 'normal' ),
		),
		'newsreader'  => array(
			'fontFamily'  => 'Newsreader',
			'fontWeights' => array( '400' ),
			'fontStyles'  => array( 'normal' ),
		),
		'cormorant'   => array(
			'fontFamily'  => 'Cormorant',
			'fontWeights' => array( '400', '500' ),
			'fontStyles'  => array( 'normal' ),
		),
		'work-sans'   => array(
			'fontFamily'  => 'Work Sans',
			'fontWeights' => array( '400' ),
			'fontStyles'  => array( 'normal' ),
		),
		'raleway'     => array(
			'fontFamily'  => 'Raleway',
			'fontWeights' => array( '700' ),
			'fontStyles'  => array( 'normal' ),
		),
	);

	/**
	 * Start install fonts async job.
	 *
	 * @param string $old_value Old option value.
	 * @param string $value Option value.
	 * @return string
	 */
	public static function start_install_fonts_async_job( $old_value, $value ) {
		if ( 'yes' !== $value || ! class_exists( 'WP_Font_Library' ) ) {
			return;
		}
		WC()->call_function(
			'as_schedule_single_action',
			WC()->call_function( 'time' ),
			'woocommerce_install_assembler_fonts',
		);
	}


	/**
	 * Create Font Families and Font Faces.
	 *
	 * @return void
	 */
	public static function install_fonts() {
		$collections                   = \WP_Font_Library::get_instance()->get_font_collections();
		$google_fonts                  = $collections['google-fonts']->get_data();
		$font_collection               = $google_fonts['font_families'];
		$slug_font_families_to_install = array_keys( self::FONT_FAMILIES_TO_INSTALL );
		$installed_font_families       = self::install_font_families( $slug_font_families_to_install, $font_collection );

		if ( ! empty( $installed_font_families ) ) {
			$font_faces_from_collection = self::get_font_faces_data_from_font_collection( $slug_font_families_to_install, $font_collection );
			self::install_font_faces( $slug_font_families_to_install, $installed_font_families, $font_faces_from_collection );
		}

	}

	/**
	 * Install font families.
	 *
	 * @param array $slug_font_families_to_install Font families to install.
	 * @param array $font_collection Font collection.
	 * @return array
	 */
	private static function install_font_families( $slug_font_families_to_install, $font_collection ) {
		return array_reduce(
			$slug_font_families_to_install,
			function( $carry, $slug ) use ( $font_collection ) {
				$font_family_from_collection = self::get_font_family_by_slug_from_font_collection( $slug, $font_collection );
				$font_family_name            = $font_family_from_collection['fontFamily'];
				$font_family_installed       = FontFamily::get_font_family_by_name( $font_family_name );
				if ( $font_family_installed ) {
					return array_merge( $carry, array( $slug => $font_family_installed ) );
				}

				$font_family_settings = array(
					'fontFamily' => $font_family_from_collection['fontFamily'],
					'preview'    => $font_family_from_collection['preview'],
					'slug'       => $font_family_from_collection['slug'],
					'name'       => $font_family_from_collection['name'],
				);

				$font_family_id = FontFamily::insert_font_family( $font_family_settings );
				if ( is_wp_error( $font_family_id ) ) {
					if ( 'duplicate_font_family' !== $font_family_id->get_error_code() ) {
						wc_get_logger()->error(
							sprintf(
								'Font Family installation error: %s',
								$font_family_id->get_error_message(),
							),
							array( 'source' => self::SOURCE_LOGGER )
						);
					}

					return $carry;
				}
				return array_merge( $carry, array( $slug => get_post( $font_family_id ) ) );
			},
			array(),
		);
	}

	/**
	 * Install font faces.
	 *
	 * @param array $slug_font_families_to_install Font families to install.
	 * @param array $installed_font_families Installed font families.
	 * @param array $font_faces_from_collection Font faces from collection.
	 */
	private static function install_font_faces( $slug_font_families_to_install, $installed_font_families, $font_faces_from_collection ) {
		foreach ( $slug_font_families_to_install as $slug ) {
			$font_family           = $installed_font_families[ $slug ];
			$font_faces            = $font_faces_from_collection[ $slug ];
			$font_faces_to_install = self::FONT_FAMILIES_TO_INSTALL[ $slug ]['fontWeights'];

			foreach ( $font_faces as $font_face ) {
				if ( ! in_array( $font_face['fontWeight'], $font_faces_to_install, true ) ) {
					continue;
				}

				$slug                = \WP_Font_Utils::get_font_face_slug( $font_face );
				$font_face_installed = FontFace::get_installed_font_faces_by_slug( $slug );
				if ( $font_face_installed ) {
					continue;
				}

				$wp_error = FontFace::insert_font_face( $font_face, $font_family->ID );

				if ( is_wp_error( $wp_error ) ) {
					wc_get_logger()->error(
						sprintf(
							/* translators: %s: error message */
							__( 'Font Face installation error: %s', 'woocommerce' ),
							$wp_error->get_error_message()
						),
						array( 'source' => self::SOURCE_LOGGER )
					);
				}
			}
		}
	}

	/**
	 * Get font faces data from font collection.
	 *
	 * @param array $slug_font_families_to_install Font families to install.
	 * @param array $font_collection Font collection.
	 * @return array
	 */
	private static function get_font_faces_data_from_font_collection( $slug_font_families_to_install, $font_collection ) {
		return array_reduce(
			$slug_font_families_to_install,
			function( $carry, $slug ) use ( $font_collection ) {
				$font_family = self::get_font_family_by_slug_from_font_collection( $slug, $font_collection );
				if ( ! $font_family ) {
					return $carry;
				}
				return array_merge( $carry, array( $slug => $font_family['fontFace'] ) );
			},
			array()
		);
	}

	/**
	 * Get font family by slug from font collection.
	 *
	 * @param string $slug Font slug.
	 * @param array  $font_families_collection Font families collection.
	 * @return array|null
	 */
	private static function get_font_family_by_slug_from_font_collection( $slug, $font_families_collection ) {
		$font_family = null;

		foreach ( $font_families_collection as $font_family ) {
			if ( $font_family['font_family_settings']['slug'] === $slug ) {
				$font_family = $font_family['font_family_settings'];
				break;
			}
		}
		return $font_family;
	}

}
