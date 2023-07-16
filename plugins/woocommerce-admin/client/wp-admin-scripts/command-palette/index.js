/**
 * External dependencies
 */
import { useCommand } from '@wordpress/commands';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';
import { box } from '@wordpress/icons';

const WooCommerceCommands = () => {
	useCommand( {
		name: 'woocommere-commands-view-products',
		label: __( 'View Products', 'woocommerce' ),
		searchLabel: __( 'View Products', 'woocommerce' ),
		icon: box,
		callback: () => {
			window.location.href = '/wp-admin/edit.php?post_type=product';
		},
	} );
	return null;
};

registerPlugin( 'woocommerce-commands-registration', {
	render: WooCommerceCommands,
} );
