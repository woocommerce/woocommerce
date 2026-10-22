/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

const TERMS_BLOCK = 'woocommerce/checkout-terms-block';
const ACTIONS_BLOCK = 'woocommerce/checkout-actions-block';

/**
 * Finds the Terms and Actions blocks that sit alongside the given block in Checkout Fields.
 */
export const useCheckoutTermsBlocks = ( clientId: string ) =>
	useSelect(
		( select ) => {
			const {
				getBlockRootClientId,
				getBlockOrder,
				getBlockName,
				getBlockAttributes,
			} = select( blockEditorStore );
			const siblings: string[] = getBlockOrder(
				getBlockRootClientId( clientId )
			);
			const termsClientId = siblings.find(
				( id ) => getBlockName( id ) === TERMS_BLOCK
			);

			return {
				termsClientId,
				termsAttributes: termsClientId
					? getBlockAttributes( termsClientId )
					: undefined,
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
	const { termsClientId, termsAttributes, actionsClientId } =
		useCheckoutTermsBlocks( clientId );
	const { updateBlockAttributes, removeBlock, selectBlock } =
		useDispatch( blockEditorStore );

	if ( ! termsClientId || ! actionsClientId ) {
		return null;
	}

	const switchTerms = () => {
		updateBlockAttributes( actionsClientId, {
			showTerms: true,
			termsCheckbox: !! termsAttributes?.checkbox,
			termsText: termsAttributes?.text || '',
		} );
		removeBlock( termsClientId, false );
		selectBlock( actionsClientId );
	};

	return (
		<Notice
			className="wc-block-checkout__terms_notice"
			status="warning"
			isDismissible={ false }
			actions={ [
				{
					label: __( 'Switch to Place order terms', 'woocommerce' ),
					onClick: switchTerms,
					variant: 'primary',
				},
			] }
		>
			<p>
				{ __(
					'The Terms and Conditions block is deprecated. We recommend switching to the terms settings on the Place order button, which keep the terms next to the button.',
					'woocommerce'
				) }
			</p>
		</Notice>
	);
};
