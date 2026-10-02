/**
 * External dependencies
 */
import { Page } from '@wordpress/admin-ui';
import { __ } from '@wordpress/i18n';
import { useParams } from '@wordpress/route';

// useParams() is untyped without a registered router, so narrow it here.
function getPageParam( params: unknown ): string {
	if ( typeof params === 'object' && params !== null && 'page' in params ) {
		return typeof params.page === 'string' ? params.page : '';
	}
	return '';
}

function SettingsStage() {
	const page = getPageParam( useParams( { strict: false } ) );

	return (
		<Page
			title={ __( 'Payment settings', 'woocommerce' ) }
			showSidebarToggle={ false }
			hasPadding
		>
			<code>{ page }</code>
		</Page>
	);
}

export const stage = SettingsStage;
