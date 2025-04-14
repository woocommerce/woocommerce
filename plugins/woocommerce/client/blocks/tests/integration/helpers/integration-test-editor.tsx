/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { registerCoreBlocks } from '@wordpress/block-library';
import {
	type BlockAttributes,
	type BlockInstance,
	createBlock,
	getBlockTypes,
	unregisterBlockType,
} from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import '@wordpress/format-library';
import {
	store as richTextStore,
	unregisterFormatType,
} from '@wordpress/rich-text';
import {
	type EditorSettings,
	type EditorBlockListSettings,
	BlockEditorProvider,
	BlockInspector,
	// @ts-expect-error privateApis exists but is not typed
	privateApis as blockEditorPrivateApis,
} from '@wordpress/block-editor';
// @ts-expect-error lock-unlock exists but is not typed
import { unlock } from '@wordpress/block-library/build/lock-unlock'; // eslint-disable-line

/**
 * Internal dependencies
 */
import { waitForStoreResolvers } from './wait-for-store-resolvers';

const { ExperimentalBlockCanvas: BlockCanvas } = unlock(
	blockEditorPrivateApis
);

/**
 * Selects the block to be tested by the aria-label on the block wrapper, eg. "Block: Cover".
 *
 * @param name The block name.
 */
export async function selectBlock( name: string | RegExp ) {
	await act( () => userEvent.click( screen.getByLabelText( name ) ) );
}

export function Editor( {
	testBlocks,
	settings = {},
}: {
	testBlocks: BlockInstance< BlockAttributes >[];
	settings?: Partial< EditorSettings & EditorBlockListSettings >;
} ) {
	const [ currentBlocks, updateBlocks ] = useState( testBlocks );
	const { getFormatTypes } = useSelect( richTextStore, [] );

	useEffect( () => {
		return () => {
			getBlockTypes().forEach( ( { name } ) =>
				unregisterBlockType( name )
			);
			getFormatTypes().forEach( ( { name } ) =>
				unregisterFormatType( name )
			);
		};
	}, [ getFormatTypes ] );

	return (
		<BlockEditorProvider
			value={ currentBlocks }
			onInput={ updateBlocks }
			onChange={ updateBlocks }
			settings={ settings }
		>
			<BlockInspector />
			<BlockCanvas height="100%" shouldIframe={ false } />
		</BlockEditorProvider>
	);
}

/**
 * Registers the core block, creates the test block instances, and then instantiates the Editor.
 *
 * @param testBlocks    Block or array of block settings for blocks to be tested.
 * @param useCoreBlocks Defaults to true. If false, core blocks will not be registered.
 * @param settings      Any additional editor settings to be passed to the editor.
 */
export async function initializeEditor(
	testBlocks: BlockAttributes | BlockAttributes[],
	useCoreBlocks = true,
	settings: Partial< EditorSettings & EditorBlockListSettings > = {}
) {
	if ( useCoreBlocks ) {
		registerCoreBlocks();
	}
	const blocks: BlockAttributes[] = Array.isArray( testBlocks )
		? testBlocks
		: [ testBlocks ];
	const newBlocks = blocks.map( ( testBlock ) =>
		createBlock(
			testBlock.name,
			testBlock.attributes,
			testBlock.innerBlocks
		)
	);
	return waitForStoreResolvers( () =>
		render( <Editor testBlocks={ newBlocks } settings={ settings } /> )
	);
}
