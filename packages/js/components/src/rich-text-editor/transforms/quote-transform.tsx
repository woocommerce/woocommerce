/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { ToolbarButton } from '@wordpress/components';
import { quote } from '@wordpress/icons';
import { switchToBlockType } from '@wordpress/blocks';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useCursor } from '../utils/replace-selected-blocks';
import { useBlockSelection } from '../hooks/block-selection';
import { PARAGRAPH_BLOCK_ID, QUOTE_BLOCK_ID } from '../utils/register-blocks';

export const QuoteTransform: React.VFC = () => {
	const { blocks, blockClientIds } = useBlockSelection();
	const { selectConvertedBlocks } = useCursor();
	const { replaceBlocks } = useDispatch( 'core/block-editor' );
	const hasQuote = blocks.find( ( block ) => block.name === QUOTE_BLOCK_ID );

	const transformToParagraphs = () => {
		const newBlocks = switchToBlockType( blocks, PARAGRAPH_BLOCK_ID );
		if ( newBlocks ) {
			replaceBlocks( blockClientIds, newBlocks );
			selectConvertedBlocks( newBlocks );
		}
	};

	const transformToQuotes = () => {
		const newBlocks = switchToBlockType( blocks, QUOTE_BLOCK_ID );
		if ( newBlocks ) {
			replaceBlocks( blockClientIds, newBlocks );
			selectConvertedBlocks( newBlocks );
		}
	};

	return (
		<ToolbarButton
			icon={ quote }
			title={ __( 'Quote', 'woocommerce' ) }
			onClick={ () => {
				if ( hasQuote ) {
					transformToParagraphs();
					return;
				}
				transformToQuotes();
			} }
			isActive={ false }
		/>
	);
};
