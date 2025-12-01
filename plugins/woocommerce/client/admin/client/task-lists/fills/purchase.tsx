/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { WooOnboardingTaskListItem } from '@woocommerce/onboarding';
import { useState, useCallback } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';
import { onboardingStore, pluginsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import CartModal from '../../dashboard/components/cart-modal';
import { getCategorizedOnboardingProducts } from '../../dashboard/utils';

type PurchaseTaskItemProps = {
	defaultTaskItem: React.ComponentType< {
		onClick: () => void;
	} >;
};

const PurchaseTaskItem = ( { defaultTaskItem }: PurchaseTaskItemProps ) => {
	const [ cartModalOpen, setCartModalOpen ] = useState( false );

	const { installedPlugins, productTypes, profileItems } = useSelect(
		( select ) => {
			const { getProductTypes, getProfileItems } =
				select( onboardingStore );
			const { getInstalledPlugins } = select( pluginsStore );

			return {
				installedPlugins: getInstalledPlugins(),
				productTypes: getProductTypes(),
				profileItems: getProfileItems(),
			};
		},
		[]
	);

	const toggleCartModal = useCallback( () => {
		if ( ! cartModalOpen ) {
			recordEvent( 'tasklist_purchase_extensions' );
		}

		setCartModalOpen( ! cartModalOpen );
	}, [ cartModalOpen ] );

	const groupedProducts = getCategorizedOnboardingProducts(
		productTypes,
		profileItems,
		installedPlugins
	);
	const { remainingProducts } = groupedProducts;
	const DefaultTaskItem = defaultTaskItem;

	return (
		<>
			<DefaultTaskItem
				onClick={ () => {
					if ( remainingProducts.length ) {
						toggleCartModal();
					}
				} }
			/>
			{ cartModalOpen && (
				// @ts-expect-error Todo: convert CartModal to TS
				<CartModal
					onClose={ () => toggleCartModal() }
					onClickPurchaseLater={ () => toggleCartModal() }
				/>
			) }
		</>
	);
};

const PurchaseTaskItemFill = () => {
	return (
		<WooOnboardingTaskListItem id="purchase">
			{ ( { defaultTaskItem } ) => (
				<PurchaseTaskItem defaultTaskItem={ defaultTaskItem } />
			) }
		</WooOnboardingTaskListItem>
	);
};

registerPlugin( 'woocommerce-admin-task-purchase', {
	scope: 'woocommerce-tasks',
	render: PurchaseTaskItemFill,
} );
