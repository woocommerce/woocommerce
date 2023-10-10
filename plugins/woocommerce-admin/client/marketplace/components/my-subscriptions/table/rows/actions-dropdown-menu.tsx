/**
 * External dependencies
 */
import { DropdownMenu } from '@wordpress/components';
import { moreVertical, external, plugins } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

export default function ActionsDropdownMenu() {
	return (
		<DropdownMenu
			icon={ moreVertical }
			label="Select a direction"
			controls={ [
				{
					title: __( 'Manage in WooCommerce.com', 'woocommerce' ),
					icon: external,
					onClick: () => {},
				},
				{
					title: __( 'Manage in Pluins', 'woocommerce' ),
					icon: plugins,
					onClick: () => {},
				},
			] }
		/>
	);
}
