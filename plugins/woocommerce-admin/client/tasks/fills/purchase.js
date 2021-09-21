/**
 * External dependencies
 */
import React from 'react';
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTaskListItem } from '@woocommerce/onboarding';
import { useState, useCallback } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';
import { ONBOARDING_STORE_NAME, PLUGINS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import CartModal from '../../dashboard/components/cart-modal';
import { getCategorizedOnboardingProducts } from '../../dashboard/utils';

const PurchaseTaskItem = () => {
	const [ cartModalOpen, setCartModalOpen ] = useState( false );

	const { installedPlugins, profileItems } = useSelect( ( select ) => {
		const { getProfileItems } = select( ONBOARDING_STORE_NAME );
		const { getInstalledPlugins } = select( PLUGINS_STORE_NAME );

		return {
			installedPlugins: getInstalledPlugins(),
			profileItems: getProfileItems(),
		};
	} );

	const toggleCartModal = useCallback( () => {
		if ( ! cartModalOpen ) {
			recordEvent( 'tasklist_purchase_extensions' );
		}

		setCartModalOpen( ! cartModalOpen );
	}, [ cartModalOpen ] );

	const groupedProducts = getCategorizedOnboardingProducts(
		profileItems,
		installedPlugins
	);
	const { remainingProducts } = groupedProducts;

	return (
		<WooOnboardingTaskListItem id="purchase">
			{ ( { defaultTaskItem: DefaultTaskItem } ) => (
				<>
					<DefaultTaskItem
						onClick={ () => {
							if ( remainingProducts.length ) {
								toggleCartModal();
							}
						} }
					/>
					{ cartModalOpen && (
						<CartModal
							onClose={ () => toggleCartModal() }
							onClickPurchaseLater={ () => toggleCartModal() }
						/>
					) }
				</>
			) }
		</WooOnboardingTaskListItem>
	);
};

registerPlugin( 'woocommerce-admin-task-purchase', {
	scope: 'woocommerce-tasks',
	render: PurchaseTaskItem,
} );
