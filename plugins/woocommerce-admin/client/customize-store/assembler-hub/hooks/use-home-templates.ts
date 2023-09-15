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
const HOME_TEMPLATES: Record< 'SMB' | 'LB', Record< string, string[] > > = {
	SMB: {
		template1: [
			'a8c/cover-image-with-left-aligned-call-to-action',
			'woocommerce-blocks/featured-products-5-item-grid',
			'woocommerce-blocks/shop-by-price',
			'woocommerce-blocks/featured-category-triple',
			'a8c/3-column-testimonials',
			'a8c/quotes2',
			'woocommerce-blocks/social-follow-us-in-social-media',
		],
		template2: [
			'woocommerce-blocks/hero-product-split',
			'woocommerce-blocks/shop-by-price',
			'woocommerce-blocks/featured-category-triple',
			'woocommerce-blocks/shop-by-price',
			'a8c/three-columns-with-images-and-text',
			'woocommerce-blocks/testimonials-3-columns',
			'a8c/subscription',
		],
		template3: [
			'a8c/call-to-action-7',
			'a8c/3-column-testimonials',
			'woocommerce-blocks/shop-by-price',
			'woocommerce-blocks/featured-category-cover-image',
			'woocommerce-blocks/featured-products-5-item-grid',
			'woocommerce-blocks/featured-products-5-item-grid',
			'woocommerce-blocks/social-follow-us-in-social-media',
		],
	},
	LB: {
		template1: [
			'a8c/cover-image-with-left-aligned-call-to-action',
			'woocommerce-blocks/featured-products-5-item-grid',
			'woocommerce-blocks/shop-by-price',
			'woocommerce-blocks/featured-category-triple',
			'a8c/3-column-testimonials',
			'a8c/quotes2',
			'woocommerce-blocks/social-follow-us-in-social-media',
		],
		template2: [
			'woocommerce-blocks/hero-product-split',
			'woocommerce-blocks/shop-by-price',
			'woocommerce-blocks/featured-category-triple',
			'woocommerce-blocks/shop-by-price',
			'a8c/three-columns-with-images-and-text',
			'woocommerce-blocks/testimonials-3-columns',
			'a8c/subscription',
		],
		template3: [
			'a8c/call-to-action-7',
			'a8c/3-column-testimonials',
			'woocommerce-blocks/shop-by-price',
			'woocommerce-blocks/featured-category-cover-image',
			'woocommerce-blocks/featured-products-5-item-grid',
			'woocommerce-blocks/featured-products-5-item-grid',
			'woocommerce-blocks/social-follow-us-in-social-media',
		],
	},
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
	const businessType = 'SMB';
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

		const recommendedTemplates = HOME_TEMPLATES[ businessType ];

		return Object.keys( recommendedTemplates ).reduce(
			( acc: Record< string, PatternWithBlocks[] >, templateName ) => {
				if ( templateName in recommendedTemplates ) {
					acc[ templateName ] = getTemplatePatterns(
						recommendedTemplates[ templateName ],
						patternsByName
					);
				}
				return acc;
			},
			{}
		);
	}, [ patternsByName ] );
	return {
		homeTemplates,
		isLoading,
	};
};
