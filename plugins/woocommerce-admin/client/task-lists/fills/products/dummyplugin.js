/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { Icon, chevronRight } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import ComputerIcon from 'gridicons/dist/computer';

addFilter(
	'woocommerce_tasklist_experimental_product_types',
	'custom-plugin',
	( productTypes ) => {
		const customProduct = {
			key: 'custom-product',
			title: __( 'Custom product', 'woocommerce' ),
			content: __( 'A custom product', 'woocommerce' ),
			before: <ComputerIcon />,
			after: <Icon icon={ chevronRight } />,
			onClick: () => {
				console.log( 'Custom click event' );
			},
		};
		return [ ...productTypes, customProduct ];
	}
);
