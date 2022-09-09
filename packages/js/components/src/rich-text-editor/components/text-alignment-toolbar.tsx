/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { alignLeft, alignRight, alignCenter } from '@wordpress/icons';
import { BlockInstance } from '@wordpress/blocks';
import { createElement, Fragment } from '@wordpress/element';
import { find } from 'lodash';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { ToolbarButton } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useBlockSelection } from '../hooks/block-selection';
import {
	HEADING_BLOCK_ID,
	PARAGRAPH_BLOCK_ID,
	QUOTE_BLOCK_ID,
} from '../utils/register-blocks';

const ALIGNMENT_CONTROLS = [
	{
		icon: alignLeft,
		title: __( 'Align text left', 'woocommerce' ),
		align: 'left',
	},
	{
		icon: alignCenter,
		title: __( 'Align text center', 'woocommerce' ),
		align: 'center',
	},
	{
		icon: alignRight,
		title: __( 'Align text right', 'woocommerce' ),
		align: 'right',
	},
];

const ALIGNMENT_PROPERTIES = {
	[ HEADING_BLOCK_ID ]: 'textAlign',
	[ PARAGRAPH_BLOCK_ID ]: 'align',
	[ QUOTE_BLOCK_ID ]: 'align',
} as { [ key: string ]: string };

export const TextAlignmentToolbar = () => {
	const { blocks, blockClientIds } = useBlockSelection();
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore Types do not exist on this data store yet.
	const { updateBlockAttributes } = useDispatch( blockEditorStore );

	const getBlockAlignment = ( block: BlockInstance ) => {
		if ( ! block?.attributes ) {
			return null;
		}
		const alignmentProperty = ALIGNMENT_PROPERTIES[ block.name ];
		return block.attributes[ alignmentProperty ];
	};

	const canAlign = blocks.every( ( block: BlockInstance ) => {
		return !! ALIGNMENT_PROPERTIES[ block.name ];
	} );

	const allAlignmentsEqual = blocks.every( ( block: BlockInstance ) => {
		return getBlockAlignment( block ) === getBlockAlignment( blocks[ 0 ] );
	} );

	const value = allAlignmentsEqual && getBlockAlignment( blocks[ 0 ] );

	function applyOrUnset( align: string ) {
		if ( value === align ) {
			blocks.forEach( ( block: BlockInstance ) => {
				updateBlockAttributes( block.clientId, {
					[ ALIGNMENT_PROPERTIES[ block.name ] ]: null,
				} );
			} );
			return;
		}
		blocks.forEach( ( block: BlockInstance ) => {
			updateBlockAttributes( block.clientId, {
				[ ALIGNMENT_PROPERTIES[ block.name ] ]: align,
			} );
		} );
	}

	const activeAlignment = find(
		ALIGNMENT_CONTROLS,
		( control ) => control.align === value
	);

	return (
		<>
			{ ALIGNMENT_CONTROLS.map( ( control ) => {
				const { align } = control;
				const isActive = activeAlignment?.align === align;
				const props = {
					...control,
					isActive,
					onClick: () => applyOrUnset( align ),
					isDisabled: ! canAlign,
				};

				return <ToolbarButton key={ align } { ...props } />;
			} ) }
		</>
	);
};
