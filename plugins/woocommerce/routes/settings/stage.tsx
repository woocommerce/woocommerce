/**
 * External dependencies
 */
import { Page } from '@wordpress/admin-ui';
import { __ } from '@wordpress/i18n';
import { useParams } from '@wordpress/route';

function SettingsStage() {
	// useParams() is untyped without a registered router.
	const { page }: { page?: string } = useParams( { strict: false } );

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
