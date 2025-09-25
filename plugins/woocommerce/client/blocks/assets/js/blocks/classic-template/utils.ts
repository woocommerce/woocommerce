/**
 * External dependencies
 */
import {
	type BlockInstance,
	getBlockType,
	createBlock,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { TemplateDetails, InheritedAttributes } from './types';

// Finds the most appropriate template details object for specific template keys such as single-product-hoodie.
export function getTemplateDetailsBySlug(
	parsedTemplate: string | null,
	templates: TemplateDetails
) {
	if ( ! parsedTemplate ) {
		return null;
	}

	const templateKeys = Object.keys( templates );
	let templateDetails = null;

	for ( let i = 0; templateKeys.length > i; i++ ) {
		const keyToMatch = parsedTemplate.substr( 0, templateKeys[ i ].length );
		const maybeTemplate = templates[ keyToMatch ];
		if ( maybeTemplate ) {
			templateDetails = maybeTemplate;
			break;
		}
	}

	return templateDetails;
}

export const createArchiveTitleBlock = (
	variationName: string,
	inheritedAttributes: InheritedAttributes
) => {
	const queryTitleBlockName = 'core/query-title';
	const queryTitleBlockVariations =
		getBlockType( queryTitleBlockName )?.variations || [];
	const archiveTitleVariation = queryTitleBlockVariations.find(
		( { name }: { name: string } ) => name === variationName
	);

	if ( ! archiveTitleVariation ) {
		return null;
	}

	const { attributes } = archiveTitleVariation;
	const extendedAttributes = {
		...attributes,
		...inheritedAttributes,
		showPrefix: false,
	};

	return createBlock( queryTitleBlockName, extendedAttributes );
};

export const createRowBlock = (
	innerBlocks: Array< BlockInstance >,
	inheritedAttributes: InheritedAttributes
) => {
	const groupBlockName = 'core/group';
	const rowVariationName = `group-row`;
	const groupBlockVariations =
		getBlockType( groupBlockName )?.variations || [];
	const rowVariation = groupBlockVariations.find(
		( { name }: { name: string } ) => name === rowVariationName
	);

	if ( ! rowVariation ) {
		return null;
	}

	const { attributes } = rowVariation;
	const extendedAttributes = {
		...attributes,
		...inheritedAttributes,
		layout: {
			...attributes.layout,
			justifyContent: 'space-between',
		},
	};

	return createBlock( groupBlockName, extendedAttributes, innerBlocks );
};
