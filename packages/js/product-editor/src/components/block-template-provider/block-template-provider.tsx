/**
 * External dependencies
 */
import { createElement, useLayoutEffect, useState } from '@wordpress/element';
import {
	BlockInstance,
	synchronizeBlocksWithTemplate,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { getVisibleBlocks } from './get-visible-blocks';
import { Provider } from './context';
import { Template } from './types';
import { TestTemplateComponent } from './test-template-component';

type BlockTemplateProviderProps = {
	children: JSX.Element;
	initialTemplate: string;
	onChange: (
		blocks: BlockInstance[],
		options: {
			[ key: string ]: unknown;
		}
	) => void;
	templates: Template[];
};

export function BlockTemplateProvider( {
	children,
	initialTemplate,
	onChange,
	templates,
}: BlockTemplateProviderProps ) {
	const [ selectedTemplate, setSelectedTemplate ] =
		useState( initialTemplate );
	const [ hiddenBlocks, setHiddenBlocks ] = useState< string[] >( [] );

	useLayoutEffect( () => {
		const template = templates.find( ( t ) => t.id === selectedTemplate );
		if ( ! template ) {
			return;
		}
		const newBlocks = synchronizeBlocksWithTemplate(
			[],
			template.content.raw
		);
		const visibleBlocks = getVisibleBlocks( newBlocks, hiddenBlocks );

		onChange( visibleBlocks, {} );
	}, [ templates, selectedTemplate, hiddenBlocks ] );

	function hideBlock( blockId: string ) {
		if ( hiddenBlocks.includes( blockId ) ) {
			return;
		}
		setHiddenBlocks( [ ...hiddenBlocks, blockId ] );
	}

	function unhideBlock( blockId: string ) {
		setHiddenBlocks( hiddenBlocks.filter( ( id ) => id !== blockId ) );
	}

	function selectTemplate( templateId: string ) {
		setSelectedTemplate( templateId );
	}

	return (
		<Provider
			value={ {
				hideBlock,
				hiddenBlocks,
				selectedTemplate,
				selectTemplate,
				templates,
				unhideBlock,
			} }
		>
			<TestTemplateComponent />
			{ children }
		</Provider>
	);
}
