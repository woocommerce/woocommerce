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
import {
	ADMIN_URL,
	getAdminSetting,
} from '../../../../../utils/admin-settings';

export default function ActionsDropdownMenu( props: {
	subscription: Subscription;
} ) {
	const controls = [
		{
			title: __< string >( 'Manage in Plugins', 'woocommerce' ),
			onClick: () => {
				window.location.href = ADMIN_URL + 'plugins.php';
			},
		},
	];

	const reviewURL = getAdminSetting( 'wccomHelper', {} ).reviewURL;

	if (
		props.subscription.can_review &&
		props.subscription.product_key &&
		reviewURL
	) {
		controls.unshift( {
			title: __( 'Review on WooCommerce.com', 'woocommerce' ),
			onClick: () => {
				const url = new URL( reviewURL );
				url.searchParams.set(
					'wc-helper-product-key',
					props.subscription.product_key
				);
				window.open( url.toString(), '_blank', 'noopener' );
			},
		} );
	}

	if ( ! props.subscription.is_shared ) {
		controls.unshift( {
			title: __( 'Manage on WooCommerce.com', 'woocommerce' ),
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
