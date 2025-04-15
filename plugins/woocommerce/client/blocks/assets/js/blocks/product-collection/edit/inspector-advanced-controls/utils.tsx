/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { getBlockSupport } from '@wordpress/blocks';

const isBlockSupported = ( blockName: string ) => {
	// Client side navigation can be true in two states:
	// - supports.interactivity === true;
	// - supports.interactivity.clientNavigation === true;

	const blockSupportsInteractivity = Object.is(
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore it's a valid supports key
		getBlockSupport( blockName, 'interactivity' ),
		true
	);

	const blockSupportsInteractivityClientNavigation = getBlockSupport(
		blockName,
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore it's a valid supports key
		'interactivity.clientNavigation'
	);

	return (
		blockSupportsInteractivity || blockSupportsInteractivityClientNavigation
	);
};

export const useHasUnsupportedBlocks = ( clientId: string ): boolean =>
	useSelect(
		( select ) => {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore No types for this exist yet
			const { getClientIdsOfDescendants, getBlockName } =
				select( blockEditorStore );

			const hasUnsupportedBlocks =
				getClientIdsOfDescendants( clientId ).find(
					( blockId: string ) => {
						const blockName = getBlockName( blockId );
						const supported = isBlockSupported( blockName );
						return ! supported;
					}
				) || false;

			return hasUnsupportedBlocks;
		},
		[ clientId ]
	);
