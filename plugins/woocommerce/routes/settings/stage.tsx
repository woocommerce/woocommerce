/**
 * External dependencies
 */
import { Page } from '@wordpress/admin-ui';
import { __ } from '@wordpress/i18n';
import { useParams } from '@wordpress/route';

function SettingsStage() {
	const { page } = useParams( { strict: false } ) as { page?: string };

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
