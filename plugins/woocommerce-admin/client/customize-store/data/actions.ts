/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { resolveSelect, dispatch } from '@wordpress/data';
// @ts-ignore No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
// @ts-ignore No types for this exist yet.

/**
 * Internal dependencies
 */
import {
	patternsToNameMap,
	getTemplatePatterns,
} from '../assembler-hub/hooks/use-home-templates';
import { setLogoWidth } from '../utils';
import { HOMEPAGE_TEMPLATES } from './homepageTemplates';

// Update the current theme template
export const updateTemplate = async ( {
	homepageTemplateId,
}: {
	homepageTemplateId: keyof typeof HOMEPAGE_TEMPLATES;
} ) => {
	// @ts-ignore No types for this exist yet.
	const { invalidateResolutionForStoreSelector } = dispatch( coreStore );

	// Ensure that the patterns are up to date because we populate images and content in previous step.
	invalidateResolutionForStoreSelector( 'getBlockPatterns' );
	invalidateResolutionForStoreSelector( '__experimentalGetTemplateForLink' );

	const patterns = ( await resolveSelect(
		coreStore
		// @ts-ignore No types for this exist yet.
	).getBlockPatterns() ) as Pattern[];
	const patternsByName = patternsToNameMap( patterns );
	const homepageTemplate = getTemplatePatterns(
		HOMEPAGE_TEMPLATES[ homepageTemplateId ].blocks,
		patternsByName
	);

	let content = [ ...homepageTemplate ]
		.filter( Boolean )
		.map( ( pattern ) => pattern.content )
		.join( '\n\n' );

	// Replace the logo width with the default width.
	content = setLogoWidth( content );

	const currentTemplate = await resolveSelect(
		coreStore
		// @ts-ignore No types for this exist yet.
	).__experimentalGetTemplateForLink( '/' );

	// @ts-ignore No types for this exist yet.
	const { saveEntityRecord } = dispatch( coreStore );

	await saveEntityRecord(
		'postType',
		currentTemplate.type,
		{
			id: currentTemplate.id,
			content,
		},
		{
			throwOnError: true,
		}
	);
};
