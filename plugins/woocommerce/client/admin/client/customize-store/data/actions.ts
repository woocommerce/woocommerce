/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { resolveSelect, dispatch } from '@wordpress/data';
// @ts-ignore No types for this exist yet.
import { store as coreStore } from '@wordpress/core-data';
import { BlockInstance, parse, serialize } from '@wordpress/blocks';
import {
	registerCoreBlocks,
	__experimentalGetCoreBlocks,
	// @ts-ignore No types for this exist yet.
} from '@wordpress/block-library';

/**
 * Internal dependencies
 */
import {
	patternsToNameMap,
	getTemplatePatterns,
} from '../assembler-hub/hooks/use-home-templates';
import { setLogoWidth } from '../utils';
import {
	FOOTER_TEMPLATES,
	HEADER_TEMPLATES,
	HOMEPAGE_TEMPLATES,
} from './homepageTemplates';
import { THEME_SLUG } from './constants';
import { Pattern } from '../types/pattern';
import { isFullComposabilityFeatureAndAPIAvailable } from '../assembler-hub/utils/is-full-composability-enabled';

const parsePattern = ( pattern: Pattern ) => {
	const blocks = parse( pattern.content );

	if ( blocks.length > 0 ) {
		// @ts-expect-error No types for this exist yet.
		blocks[ 0 ].attributes = {
			...blocks[ 0 ].attributes,
			metadata: {
				...( blocks[ 0 ].attributes.metadata || {} ),
				categories: pattern.categories,
				patternName: pattern.name,
				name: blocks[ 0 ].attributes.metadata?.name || pattern.title,
			},
		};
	}
	return blocks;
};

/**
 * This function can be removed once the full composability feature is available for all the version of WordPress that we support.
 */
export const updateTemplatePrePTK = async ( {
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

	const headerTemplate = getTemplatePatterns(
		HEADER_TEMPLATES[ homepageTemplateId ].blocks,
		patternsByName
	);

	const footerTemplate = getTemplatePatterns(
		FOOTER_TEMPLATES[ homepageTemplateId ].blocks,
		patternsByName
	);

	const headerTemplateContent = [ ...headerTemplate ]
		.filter( Boolean )
		.map( ( pattern ) => pattern.content )
		.join( '\n\n' );

	const footerTemplateContent = [ ...footerTemplate ]
		.filter( Boolean )
		.map( ( pattern ) => pattern.content )
		.join( '\n\n' );

	// Combine the header, homepage, and footer patterns into a single content string.
	let content = [ ...homepageTemplate ]
		.filter( Boolean )
		.map( ( pattern ) => pattern.content )
		.join( '\n\n' );

	content =
		`<!-- wp:template-part {"slug":"header", "theme": "${ THEME_SLUG }"} /-->` +
		content +
		`<!-- wp:template-part {"slug":"footer", "theme": "${ THEME_SLUG }"} /-->`;

	// Replace the logo width with the default width.
	content = setLogoWidth( content );

	const currentTemplate = await resolveSelect(
		coreStore
		// @ts-ignore No types for this exist yet.
	).__experimentalGetTemplateForLink( '/' );

	// @ts-ignore No types for this exist yet.
	const { saveEntityRecord } = dispatch( coreStore );

	await Promise.all( [
		saveEntityRecord(
			'postType',
			'wp_template_part',
			{
				id: `${ THEME_SLUG }//header`,
				content: headerTemplateContent,
			},
			{
				throwOnError: true,
			}
		),
		saveEntityRecord(
			'postType',
			'wp_template_part',
			{
				id: `${ THEME_SLUG }//footer`,
				content: footerTemplateContent,
			},
			{
				throwOnError: true,
			}
		),
		saveEntityRecord(
			'postType',
			currentTemplate.type,
			{
				id: currentTemplate.id,
				content,
			},
			{
				throwOnError: true,
			}
		),
	] );
};

const updateTemplatePTK = async () => {
	// @ts-ignore No types for this exist yet.
	const { invalidateResolutionForStoreSelector } = dispatch( coreStore );

	// Ensure that the patterns are up to date because we populate images and content in previous step.
	invalidateResolutionForStoreSelector( 'getBlockPatterns' );
	invalidateResolutionForStoreSelector( '__experimentalGetTemplateForLink' );
	registerCoreBlocks( __experimentalGetCoreBlocks() );

	const DEFAULT_PATTERNS = {
		header: 'woocommerce-blocks/header-essential',
		intro: 'woocommerce-blocks/centered-content-with-image-below',
		footer: 'woocommerce-blocks/footer-with-3-menus',
	} as const;

	const allPatterns = ( await resolveSelect(
		coreStore
		// @ts-ignore No types for this exist yet.
	).getBlockPatterns() ) as Pattern[];

	const patterns = Object.entries( DEFAULT_PATTERNS ).reduce(
		( acc, [ category, patternName ] ) => {
			const foundPattern = allPatterns.find(
				( pattern ) => pattern.name === patternName
			);

			const parsedPattern = foundPattern
				? parsePattern( foundPattern )
				: [];
			return {
				...acc,
				[ category ]: parsedPattern,
			};
		},
		{
			footer: [] as BlockInstance[],
			intro: [] as BlockInstance[],
			header: [] as BlockInstance[],
		}
	);

	const headerTemplateContent = serialize( patterns.header );
	const footerTemplateContent = serialize( patterns.footer );

	// Combine the header, homepage, and footer patterns into a single content string.
	let content = serialize( patterns.intro );
	content =
		`<!-- wp:template-part {"slug":"header", "theme": "${ THEME_SLUG }"} /-->` +
		content +
		`<!-- wp:template-part {"slug":"footer", "theme": "${ THEME_SLUG }"} /-->`;

	// Replace the logo width with the default width.
	content = setLogoWidth( content );

	const currentTemplate = await resolveSelect(
		coreStore
		// @ts-ignore No types for this exist yet.
	).__experimentalGetTemplateForLink( '/' );

	// @ts-ignore No types for this exist yet.
	const { saveEntityRecord } = dispatch( coreStore );

	await Promise.all( [
		saveEntityRecord(
			'postType',
			'wp_template_part',
			{
				id: `${ THEME_SLUG }//header`,
				content: headerTemplateContent,
			},
			{
				throwOnError: true,
			}
		),
		saveEntityRecord(
			'postType',
			'wp_template_part',
			{
				id: `${ THEME_SLUG }//footer`,
				content: footerTemplateContent,
			},
			{
				throwOnError: true,
			}
		),
		saveEntityRecord(
			'postType',
			currentTemplate.type,
			{
				id: currentTemplate.id,
				content,
			},
			{
				throwOnError: true,
			}
		),
	] );
};

// Update the current theme template
export const updateTemplate = async ( {
	homepageTemplateId,
}: {
	homepageTemplateId: keyof typeof HOMEPAGE_TEMPLATES;
} ) => {
	if ( isFullComposabilityFeatureAndAPIAvailable() ) {
		await updateTemplatePTK();
	} else {
		await updateTemplatePrePTK( { homepageTemplateId } );
	}
};
