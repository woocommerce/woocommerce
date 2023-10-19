/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import {
	InspectorControls,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { addFilter, hasFilter } from '@wordpress/hooks';
import type { StoreDescriptor } from '@wordpress/data';
import { CartCheckoutSidebarCompatibilityNotice } from '@woocommerce/editor-components/sidebar-compatibility-notice';
import { NoPaymentMethodsNotice } from '@woocommerce/editor-components/no-payment-methods-notice';
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';
import { DefaultNotice } from '@woocommerce/editor-components/default-notice';
import { IncompatibleExtensionsNotice } from '@woocommerce/editor-components/incompatible-extension-notice';
import { useSelect } from '@wordpress/data';
import { CartCheckoutFeedbackPrompt } from '@woocommerce/editor-components/feedback-prompt';
import { useState } from '@wordpress/element';

declare module '@wordpress/editor' {
	let store: StoreDescriptor;
}

declare module '@wordpress/core-data' {
	let store: StoreDescriptor;
}

declare module '@wordpress/block-editor' {
	let store: StoreDescriptor;
}

const withSidebarNotices = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		const {
			clientId,
			name: blockName,
			isSelected: isBlockSelected,
		} = props;

		const [
			isIncompatibleExtensionsNoticeDismissed,
			setIsIncompatibleExtensionsNoticeDismissed,
		] = useState( true );

		const toggleIncompatibleExtensionsNoticeDismissedStatus = (
			isDismissed: boolean
		) => {
			setIsIncompatibleExtensionsNoticeDismissed( isDismissed );
		};

		const {
			isCart,
			isCheckout,
			isPaymentMethodsBlock,
			hasPaymentMethods,
			parentId,
		} = useSelect( ( select ) => {
			const { getBlockParentsByBlockName, getBlockName } =
				select( blockEditorStore );

			const parents = getBlockParentsByBlockName( clientId, [
				'woocommerce/cart',
				'woocommerce/checkout',
			] ).reduce(
				(
					accumulator: Record< string, string >,
					parentClientId: string
				) => {
					const parentName = getBlockName( parentClientId );
					accumulator[ parentName ] = parentClientId;
					return accumulator;
				},
				{}
			);

			const currentBlockName = getBlockName( clientId );
			const parentBlockIsCart =
				Object.keys( parents ).includes( 'woocommerce/cart' );
			const parentBlockIsCheckout = Object.keys( parents ).includes(
				'woocommerce/checkout'
			);
			const currentBlockIsCart =
				currentBlockName === 'woocommerce/cart' || parentBlockIsCart;
			const currentBlockIsCheckout =
				currentBlockName === 'woocommerce/checkout' ||
				parentBlockIsCheckout;
			const targetParentBlock = currentBlockIsCart
				? 'woocommerce/cart'
				: 'woocommerce/checkout';

			return {
				isCart: currentBlockIsCart,
				isCheckout: currentBlockIsCheckout,
				parentId:
					currentBlockName === targetParentBlock
						? clientId
						: parents[ targetParentBlock ],
				isPaymentMethodsBlock:
					currentBlockName === 'woocommerce/checkout-payment-block',
				hasPaymentMethods:
					select( PAYMENT_STORE_KEY ).paymentMethodsInitialized() &&
					Object.keys(
						select( PAYMENT_STORE_KEY ).getAvailablePaymentMethods()
					).length > 0,
			};
		} );

		// Show sidebar notices only when a WooCommerce block is selected.
		if (
			! blockName.startsWith( 'woocommerce/' ) ||
			! isBlockSelected ||
			! ( isCart || isCheckout )
		) {
			return <BlockEdit key="edit" { ...props } />;
		}

		return (
			<>
				<InspectorControls>
					<IncompatibleExtensionsNotice
						toggleDismissedStatus={
							toggleIncompatibleExtensionsNoticeDismissedStatus
						}
						block={
							isCart ? 'woocommerce/cart' : 'woocommerce/checkout'
						}
						clientId={ parentId }
					/>

					<DefaultNotice block={ isCheckout ? 'checkout' : 'cart' } />

					{ isIncompatibleExtensionsNoticeDismissed ? (
						<CartCheckoutSidebarCompatibilityNotice
							block={ isCheckout ? 'checkout' : 'cart' }
						/>
					) : null }

					{ isPaymentMethodsBlock && ! hasPaymentMethods && (
						<NoPaymentMethodsNotice />
					) }

					<CartCheckoutFeedbackPrompt />
				</InspectorControls>
				<BlockEdit key="edit" { ...props } />
			</>
		);
	},
	'withSidebarNotices'
);

if (
	! hasFilter(
		'editor.BlockEdit',
		'woocommerce/add/sidebar-compatibility-notice'
	)
) {
	addFilter(
		'editor.BlockEdit',
		'woocommerce/add/sidebar-compatibility-notice',
		withSidebarNotices,
		11
	);
}
