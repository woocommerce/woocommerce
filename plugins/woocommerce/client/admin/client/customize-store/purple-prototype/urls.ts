/**
 * External dependencies
 */
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { isWooExpress } from '~/utils/is-woo-express';

export const PURPLE_DEMO_URL = 'https://wordpress.org/themes/purple/preview/';

export const getSiteEditorUrl = () => getAdminLink( 'site-editor.php' );

export const getLivePreviewUrl = ( slug: string ) =>
	getAdminLink(
		`site-editor.php?wp_theme_preview=${ encodeURIComponent( slug ) }`
	);

export const getMarketplaceThemesUrl = () =>
	isWooExpress()
		? getAdminLink( 'themes.php' )
		: getAdminLink(
				'admin.php?page=wc-admin&tab=themes&path=%2Fextensions'
			);

/**
 * Where "Open Site Editor" goes for the current theme. Classic themes have no Site Editor.
 */
export const getCurrentThemeEditorUrl = ( isBlockTheme: boolean ) =>
	isBlockTheme ? getSiteEditorUrl() : getAdminLink( 'customize.php' );
