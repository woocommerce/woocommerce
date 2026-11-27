/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { UpgradeDowngradeNotice } from '@woocommerce/editor-components/upgrade-downgrade-notice';
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
 * Recommends moving the Terms and Conditions block's settings onto the Place order button, and does it in one click.
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

	// Replace the inner blocks in one change, so a single undo restores the Terms and Conditions block.
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
		recordEvent( 'blocks_checkout_terms_migration', {
			transform_to: 'actions_block',
		} );
	};

	return (
		<UpgradeDowngradeNotice
			className="wc-block-checkout__terms_notice"
			isDismissible={ false }
			actionLabel={ __( 'Switch to the Actions block', 'woocommerce' ) }
			onActionClick={ switchTerms }
		>
			{ __(
				'Show Terms and Conditions next to the Place order button. Your text and checkbox setting move to the Actions block.',
				'woocommerce'
			) }
		</UpgradeDowngradeNotice>
	);
};
