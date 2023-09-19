/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { parse } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { usePatterns, Pattern, PatternWithBlocks } from './use-patterns';

// TODO: It might be better to create an API endpoint to get the templates.
const LARGE_BUSINESS_TEMPLATES = {
	template1: [
		'a8c/cover-image-with-left-aligned-call-to-action',
		'woocommerce-blocks/featured-products-5-item-grid',
		'woocommerce-blocks/featured-products-fresh-and-tasty',
		'woocommerce-blocks/featured-category-triple',
		'a8c/3-column-testimonials',
		'a8c/quotes-2',
		'woocommerce-blocks/social-follow-us-in-social-media',
	],
	template2: [
		'woocommerce-blocks/hero-product-split',
		'woocommerce-blocks/featured-products-fresh-and-tasty',
		'woocommerce-blocks/featured-category-triple',
		'woocommerce-blocks/featured-products-fresh-and-tasty',
		'a8c/three-columns-with-images-and-text',
		'woocommerce-blocks/testimonials-3-columns',
		'a8c/subscription',
	],
	template3: [
		'a8c/call-to-action-7',
		'a8c/3-column-testimonials',
		'woocommerce-blocks/featured-products-fresh-and-tasty',
		'woocommerce-blocks/featured-category-cover-image',
		'woocommerce-blocks/featured-products-5-item-grid',
		'woocommerce-blocks/featured-products-5-item-grid',
		'woocommerce-blocks/social-follow-us-in-social-media',
	],
};

const SMALL_MEDIUM_BUSINESS_TEMPLATES = {
	template1: [
		'woocommerce-blocks/featured-products-fresh-and-tasty',
		'woocommerce-blocks/testimonials-single',
		'woocommerce-blocks/hero-product-3-split',
		'a8c/contact-8',
	],
	template2: [
		'a8c/about-me-4',
		'a8c/product-feature-with-buy-button',
		'woocommerce-blocks/featured-products-fresh-and-tasty',
		'a8c/subscription',
		'woocommerce-blocks/testimonials-3-columns',
		'a8c/contact-with-map-on-the-left',
	],
	template3: [
		'a8c/heading-and-video',
		'a8c/3-column-testimonials',
		'woocommerce-blocks/product-hero',
		'a8c/quotes-2',
		'a8c/product-feature-with-buy-button',
		'a8c/simple-two-column-layout',
		'woocommerce-blocks/social-follow-us-in-social-media',
	],
};

const getTemplatePatterns = (
	template: string[],
	patternsByName: Record< string, Pattern >
) =>
	template
		.map( ( patternName: string ) => {
			const pattern = patternsByName[ patternName ];
			if ( pattern && pattern.content ) {
				return {
					...pattern,
					// @ts-ignore - Passing options is valid, but not in the type.
					blocks: parse( pattern.content, {
						__unstableSkipMigrationLogs: true,
					} ),
				};
			}
			return null;
		} )
		.filter( ( pattern ) => pattern !== null ) as PatternWithBlocks[];

export const useHomeTemplates = () => {
	// TODO: Get businessType from option
	const businessType = 'SMB' as string;
	const { blockPatterns, isLoading } = usePatterns();

	const patternsByName = useMemo( () => {
		return blockPatterns.reduce(
			( acc: Record< string, Pattern >, pattern: Pattern ) => {
				acc[ pattern.name ] = pattern;
				return acc;
			},
			{}
		);
	}, [ blockPatterns ] );

	const homeTemplates = useMemo( () => {
		if ( isLoading ) return {};
		const recommendedTemplates =
			businessType === 'SMB'
				? SMALL_MEDIUM_BUSINESS_TEMPLATES
				: LARGE_BUSINESS_TEMPLATES;

		return Object.entries( recommendedTemplates ).reduce(
			(
				acc: Record< string, PatternWithBlocks[] >,
				[ templateName, template ]
			) => {
				if ( templateName in recommendedTemplates ) {
					acc[ templateName ] = getTemplatePatterns(
						template,
						patternsByName
					);
				}
				return acc;
			},
			{}
		);
	}, [ isLoading, patternsByName ] );

	return {
		homeTemplates,
		isLoading,
	};
};
