/**
 * External dependencies
 */
import { useIsEmailEditor } from '@woocommerce/email-editor';
import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

interface Block {
	clientId: string;
	name: string;
	innerBlocks: Block[];
}

/**
 * Custom hook to remove heading blocks when in the email editor.
 * Headings are included in collection inner block templates but are
 * not needed in the email context.
 *
 * @param {string} clientId - The client ID of the product collection block.
 */
const useEmailHeadingAdjustments = ( clientId: string ) => {
	const actions = useDispatch( blockEditorStore );
	const isEmail = useIsEmailEditor();

	const { productCollectionBlock } = useSelect(
		( select ) => ( {
			productCollectionBlock:
				// @ts-expect-error getBlock is not typed.
				select( blockEditorStore ).getBlock( clientId ) as Block | null,
		} ),
		[ clientId ]
	);

	useEffect( () => {
		if ( ! clientId || ! productCollectionBlock || ! isEmail ) {
			return;
		}

		if (
			! productCollectionBlock.innerBlocks ||
			! Array.isArray( productCollectionBlock.innerBlocks )
		) {
			return;
		}

		const headingBlocks = productCollectionBlock.innerBlocks.filter(
			( block: Block ) => block && block.name === 'core/heading'
		);

		headingBlocks.forEach( ( headingBlock: Block ) => {
			if ( headingBlock && headingBlock.clientId ) {
				try {
					actions.removeBlock( headingBlock.clientId );
				} catch ( error ) {
					// Silently handle cases where block might already be removed
					// or in an inconsistent state during block editor operations
				}
			}
		} );
	}, [ clientId, actions, productCollectionBlock, isEmail ] );
};

export default useEmailHeadingAdjustments;
