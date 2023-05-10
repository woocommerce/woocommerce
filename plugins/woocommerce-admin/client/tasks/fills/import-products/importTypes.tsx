/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PageIcon from 'gridicons/dist/pages';
import { getAdminLink } from '@woocommerce/settings';
import { recordEvent } from '@woocommerce/tracks';

export const importTypes = [
	{
		key: 'from-csv' as const,
		title: __( 'FROM A CSV FILE', 'woocommerce' ),
		content: __(
			'Import all products at once by uploading a CSV file.',
			'woocommerce'
		),
		before: <PageIcon />,
		onClick: () => {
			recordEvent( 'tasklist_add_product', { method: 'import' } );
			window.location.href = getAdminLink(
				'edit.php?post_type=product&page=product_importer&wc_onboarding_active_task=products'
			);
		},
	},
];
