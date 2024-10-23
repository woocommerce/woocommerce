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
import { ADMIN_URL } from '../../../../../utils/admin-settings';

export default function ActionsDropdownMenu( props: {
	subscription: Subscription;
} ) {
	const controls = [
		{
			title: __( 'Manage in Plugins', 'woocommerce' ),
			icon: <></>,
			onClick: () => {
				window.location.href = ADMIN_URL + 'plugins.php';
			},
		},
	];

	if ( ! props.subscription.is_shared ) {
		controls.unshift( {
			title: __( 'Manage on WooCommerce.com', 'woocommerce' ),
			icon: <></>,
			onClick: () => {
				window.open(
					'https://woocommerce.com/my-account/my-subscriptions',
					'_blank'
				);
			},
		} );
	}

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
			label={ __( 'Actions', 'woocommerce' ) }
			controls={ controls }
		/>
	);
}
