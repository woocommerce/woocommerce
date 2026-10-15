/**
 * External dependencies
 */
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { isWooExpress } from '~/utils/is-woo-express';

/**
 * WordPress's own theme preview in wp-admin. Its address can't carry anything else, so the page
 * to come back to is kept in session storage (see `rememberPreviewReturn`).
 */
export const getPreviewDemoUrl = ( slug: string ) =>
	getAdminLink( `theme-install.php?theme=${ encodeURIComponent( slug ) }` );

/**
 * Remember the current screen, so closing WordPress's theme preview comes back here.
 */
export const rememberPreviewReturn = () =>
	window.sessionStorage.setItem( 'purpleCysReturn', window.location.href );

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
