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
					onClick: () => {
						window.location.href =
							'https://woocommerce.com/my-account/my-subscriptions';
					},
				},
				{
					title: __( 'Manage in Pluins', 'woocommerce' ),
					icon: plugins,
					onClick: () => {
						window.location.href = '/wp-admin/plugins.php';
					},
				},
			] }
		/>
	);
}
