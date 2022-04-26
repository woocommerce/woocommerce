/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PageIcon from 'gridicons/dist/pages';
import ReblogIcon from 'gridicons/dist/reblog';

export const importTypes = [
	{
		key: 'from-csv' as const,
		title: __( 'FROM A CSV FILE', 'woocommerce' ),
		content: __(
			'Import all products at once by uploading a CSV file.',
			'woocommerce'
		),
		before: <PageIcon />,
	},
	{
		key: 'from-cart2cart' as const,
		title: __( 'FROM CART2CART', 'woocommerce' ),
		content: __(
			'Migrate all store data like products, customers, orders and much more in no time and in a fully automated way',
			'woocommerce'
		),
		before: <ReblogIcon />,
	},
];
