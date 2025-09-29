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
import { DefaultNotice } from '@woocommerce/editor-components/default-notice';
import { IncompatibleExtensionsNotice } from '@woocommerce/editor-components/incompatible-extension-notice';
import { useSelect } from '@wordpress/data';
import { CartCheckoutFeedbackPrompt } from '@woocommerce/editor-components/feedback-prompt';

declare module '@wordpress/editor' {
	let store: StoreDescriptor;
}

declare module '@wordpress/core-data' {
	let store: StoreDescriptor;
}

declare module '@wordpress/block-editor' {
	let store: StoreDescriptor;
}

const SidebarNotices = ( { clientId } ) => {
	const { isCart, isCheckout, parentId } = useSelect(
		( select ) => {
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
			};
		},
		[ clientId ]
	);

	return (
		( isCart || isCheckout ) && (
			<InspectorControls>
				<IncompatibleExtensionsNotice
					block={
						isCart ? 'woocommerce/cart' : 'woocommerce/checkout'
					}
					clientId={ parentId }
				/>

				<DefaultNotice block={ isCheckout ? 'checkout' : 'cart' } />
				<CartCheckoutFeedbackPrompt />
			</InspectorControls>
		)
	);
};

const withSidebarNotices = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		const {
			clientId,
			name: blockName,
			isSelected: isBlockSelected,
		} = props;

		return (
			<>
				{
					// Show sidebar notices only when a WooCommerce block is selected.
					// This early check helps prevent expensive and unnecessary work
					// in the block editor store.
					blockName.startsWith( 'woocommerce/' ) &&
						isBlockSelected && (
							<SidebarNotices clientId={ clientId } />
						)
				}
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
