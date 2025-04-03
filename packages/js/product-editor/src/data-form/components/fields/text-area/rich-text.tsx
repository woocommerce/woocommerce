/**
 * External dependencies
 */
import { createElement, useEffect } from '@wordpress/element';
import { SlotFillProvider } from '@wordpress/components';
import { unregisterBlockType } from '@wordpress/blocks';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet.
	BlockTools,
	BlockEditorProvider,
	BlockList,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { TextAreaBlockEditAttributes } from '../../../../blocks/generic/text-area/types';
import { init as initTextArea } from '../../../../blocks/generic/text-area';

export type RichTextEditorProps = {
	contentId: string;
	label: string;
	value: string;
	onChange: ( value: Record< string, unknown > ) => void;
	id: string;
	allowedFormats?: string[];
	placeholder?: string;
	required?: boolean;
	disabled?: boolean;
	defaultAlign?: TextAreaBlockEditAttributes[ 'align' ];
	defaultDirection?: 'ltr' | 'rtl';
};

export default function RichTextEditorWrapper( props: RichTextEditorProps ) {
	useEffect( () => {
		const textAreaBlock = initTextArea();

		return () => {
			if ( textAreaBlock ) {
				unregisterBlockType( textAreaBlock.name );
			}
		};
	}, [] );
	return (
		<SlotFillProvider>
			<BlockEditorProvider
				useSubRegistry={ true }
				settings={ {
					templateLock: 'all',
					hasFixedToolbar: false,
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore This property was recently added in the block editor data store.
					__experimentalClearBlockSelection: false,
				} }
				value={ [
					{
						name: 'woocommerce/product-text-area-field',
						innerBlocks: [],
						attributes: {
							...props,
							property: props.id,
							lock: { move: true },
						},
						clientId: props.contentId,
						isValid: true,
					},
				] }
			>
				<BlockTools>
					<BlockList />
				</BlockTools>
			</BlockEditorProvider>
		</SlotFillProvider>
	);
}
