/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { useDispatch, useRegistry, useSelect } from '@wordpress/data';
import type { BlockInstance } from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';

const TERMS_BLOCK = 'woocommerce/checkout-terms-block';
const ACTIONS_BLOCK = 'woocommerce/checkout-actions-block';

/**
 * Finds the Terms and Actions blocks that sit alongside the given block in Checkout Fields.
 */
export const useCheckoutTermsBlocks = ( clientId: string ) =>
	useSelect(
		( select ) => {
			const { getBlockRootClientId, getBlockOrder, getBlockName } =
				select( blockEditorStore );
			const siblings: string[] = getBlockOrder(
				getBlockRootClientId( clientId )
			);

			return {
				termsClientId: siblings.find(
					( id ) => getBlockName( id ) === TERMS_BLOCK
				),
				actionsClientId: siblings.find(
					( id ) => getBlockName( id ) === ACTIONS_BLOCK
				),
			};
		},
		[ clientId ]
	);

/**
 * Recommends moving the deprecated Terms block's settings onto the Place order button, and does it in one click.
 */
export const SwitchToActionsTermsNotice = ( {
	clientId,
}: {
	clientId: string;
} ) => {
	const { termsClientId, actionsClientId } =
		useCheckoutTermsBlocks( clientId );
	const registry = useRegistry();
	const { replaceInnerBlocks, selectBlock } = useDispatch( blockEditorStore );

	if ( ! termsClientId || ! actionsClientId ) {
		return null;
	}

	// Replace the inner blocks in one change, so a single undo restores the Terms block.
	const switchTerms = () => {
		const { getBlockRootClientId, getBlocks } =
			registry.select( blockEditorStore );
		const rootClientId = getBlockRootClientId( termsClientId );
		const blocks = getBlocks( rootClientId ) as BlockInstance[];
		const termsAttributes = blocks.find(
			( block ) => block.clientId === termsClientId
		)?.attributes;
		const innerBlocks = blocks
			.filter( ( block ) => block.clientId !== termsClientId )
			.map( ( block ) =>
				block.clientId === actionsClientId
					? {
							...block,
							attributes: {
								...block.attributes,
								showTerms: true,
								termsCheckbox: !! termsAttributes?.checkbox,
								termsText: termsAttributes?.text || '',
							},
						}
					: block
			);

		replaceInnerBlocks( rootClientId, innerBlocks, false );
		selectBlock( actionsClientId );
	};

	return (
		<Notice
			className="wc-block-checkout__terms_notice"
			status="warning"
			isDismissible={ false }
			actions={ [
				{
					label: __( 'Switch', 'woocommerce' ),
					onClick: switchTerms,
					variant: 'primary',
				},
			] }
		>
			{ __(
				'This block is deprecated. Terms and Conditions can now be shown next to the Place Order button in the Actions block.',
				'woocommerce'
			) }
		</Notice>
	);
};
