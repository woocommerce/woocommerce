/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { act, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { registerCoreBlocks } from '@wordpress/block-library';
import {
	type BlockAttributes,
	type BlockInstance,
	createBlock,
} from '@wordpress/blocks';
import '@wordpress/format-library';
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

let areCoreBlocksRegistered = false;

/**
 * Registers the core block, creates the test block instances, and then instantiates the Editor.
 *
 * @param testBlocks Block or array of block settings for blocks to be tested.
 * @param settings   Any additional editor settings to be passed to the editor.
 */
export async function initializeEditor(
	testBlocks: BlockAttributes | BlockAttributes[],
	settings: Partial< EditorSettings & EditorBlockListSettings > = {}
) {
	if ( ! areCoreBlocksRegistered ) {
		registerCoreBlocks();
		areCoreBlocksRegistered = true;
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
