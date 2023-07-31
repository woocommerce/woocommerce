/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { Button, SelectControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Template } from './types';
import { useBlockTemplate } from './';

export function TestTemplateComponent() {
	const { hiddenBlocks, hideBlock, templates, selectTemplate, unhideBlock } =
		useBlockTemplate();

	function toggleBasicDetails() {
		if ( hiddenBlocks.includes( 'section/basic-details' ) ) {
			unhideBlock( 'section/basic-details' );
			return;
		}
		hideBlock( 'section/basic-details' );
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
					selectTemplate( templateId as string )
				}
			/>
			<Button onClick={ toggleBasicDetails }>
				Toggle basic details section
			</Button>
		</>
	);
}
