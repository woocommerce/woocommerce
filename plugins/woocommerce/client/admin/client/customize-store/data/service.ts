/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

export const installAndActivateTheme = async ( themeSlug: string ) => {
	await apiFetch( {
		path: `/wc-admin/onboarding/themes/install?theme=${ themeSlug }`,
		method: 'POST',
	} );

	await apiFetch( {
		path: `/wc-admin/onboarding/themes/activate?theme=${ themeSlug }&theme_switch_via_cys_ai_loader=1`,
		method: 'POST',
	} );
};
