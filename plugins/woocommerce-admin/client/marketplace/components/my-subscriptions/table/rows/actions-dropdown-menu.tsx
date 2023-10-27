/**
 * External dependencies
 */
import { DropdownMenu } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Subscription } from '../../types';

export default function ActionsDropdownMenu( props: {
	subscription: Subscription;
} ) {
	const controls = [
		{
			title: __( 'Manage in WooCommerce.com', 'woocommerce' ),
			icon: <></>,
			onClick: () => {
				window.open(
					'https://woocommerce.com/my-account/my-subscriptions',
					'_blank'
				);
			},
		},
		{
			title: __( 'Manage in Plugins', 'woocommerce' ),
			icon: <></>,
			onClick: () => {
				window.location.href = '/wp-admin/plugins.php';
			},
		},
	];

	if ( props.subscription.documentation_url ) {
		controls.unshift( {
			title: __( 'View documentation', 'woocommerce' ),
			icon: <></>,
			onClick: () => {
				window.open( props.subscription.documentation_url, '_blank' );
			},
		} );
	}

	return (
		<DropdownMenu
			icon={ moreVertical }
			label={ __(
				'See more things you can do with this subscription',
				'woocommerce'
			) }
			controls={ controls }
		/>
	);
}
