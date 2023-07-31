/**
 * External dependencies
 */
import {
	createElement,
	Fragment,
	useLayoutEffect,
	useState,
} from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import {
	BlockInstance,
	synchronizeBlocksWithTemplate,
	TemplateArray,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { getVisibleBlocks } from './get-visible-blocks';

type Template = {
	id: string;
	title: {
		raw: string;
		rendered: string;
	};
	content: {
		raw: TemplateArray;
	};
};

type BlockTemplateProviderProps = {
	children: JSX.Element;
	initialTemplateId: string;
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
	initialTemplateId,
	onChange,
	templates,
}: BlockTemplateProviderProps ) {
	const [ selectedTemplateId, setSelectedTemplateId ] =
		useState( initialTemplateId );
	const [ hiddenBlockIds ] = useState( [ 'section/basic-details' ] );

	useLayoutEffect( () => {
		const template = templates.find( ( t ) => t.id === selectedTemplateId );
		if ( ! template ) {
			return;
		}
		const newBlocks = synchronizeBlocksWithTemplate(
			[],
			template.content.raw
		);
		const visibleBlocks = getVisibleBlocks( newBlocks, hiddenBlockIds );

		onChange( visibleBlocks, {} );
	}, [ templates, selectedTemplateId, hiddenBlockIds ] );

	if ( ! templates ) {
		return null;
	}

	return (
		<>
			<SelectControl
				className="woocommerce-template-switcher"
				options={ templates.map( ( template: Template ) => ( {
					label: template.title.rendered,
					value: template.id,
				} ) ) }
				onChange={ ( templateId ) =>
					setSelectedTemplateId( templateId as string )
				}
			/>
			{ children }
		</>
	);
}
