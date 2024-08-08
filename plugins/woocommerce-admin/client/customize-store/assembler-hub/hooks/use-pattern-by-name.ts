/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
// @ts-ignore No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { useInsertPattern } from './use-insert-pattern';
import { usePatterns } from './use-patterns';
import { useEditorBlocks } from './use-editor-blocks';

export const usePatternByName = () => {
	const { blockPatterns, isLoading } = usePatterns();
	const { insertPattern } = useInsertPattern();

	const currentTemplate = useSelect(
		( sel ) =>
			// @ts-expect-error No types for this exist yet.
			sel( coreStore ).__experimentalGetTemplateForLink( '/' ),
		[]
	);

	const [ blocks ] = useEditorBlocks(
		'wp_template',
		currentTemplate?.id ?? ''
	);

	const getPatternByName = useCallback(
		( patterName: string ) => {
			return blocks.find( ( block ) => {
				const blockPatternName = block.attributes.metadata?.patternName;
				return blockPatternName === patterName;
			} );
		},
		[ blocks ]
	);

	const insertPatternByName = ( name: string ) => {
		if ( isLoading ) {
			return;
		}

		const pattern = blockPatterns.find( ( p ) => p.name === name );

		if ( ! pattern ) {
			return;
		}

		insertPattern( pattern );
	};

	return {
		getPatternByName,
		insertPatternByName,
		isLoading,
	};
};
