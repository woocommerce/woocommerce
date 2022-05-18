/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import PageIcon from 'gridicons/dist/pages';
import ReblogIcon from 'gridicons/dist/reblog';
import { getAdminLink } from '@woocommerce/settings';
import interpolateComponents from '@automattic/interpolate-components';
import { ExternalLink } from '@wordpress/components';
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
	{
		key: 'from-cart2cart' as const,
		title: __( 'FROM CART2CART', 'woocommerce' ),
		content: interpolateComponents( {
			mixedString: __(
				'Migrate all store data like products, customers, and orders in no time with this 3rd party plugin. {{link}}Learn more{{/link}}',
				'woocommerce'
			),
			components: {
				link: (
					<ExternalLink
						href="https://woocommerce.com/products/cart2cart/?utm_medium=product"
						onClickCapture={ ( e ) => e.preventDefault() }
					></ExternalLink>
				),
			},
		} ),
		before: <ReblogIcon />,
		onClick: () => {
			recordEvent( 'tasklist_add_product', { method: 'migrate' } );
			window
				.open(
					'https://woocommerce.com/products/cart2cart/?utm_medium=product',
					'_blank'
				)
				?.focus();
		},
	},
];
