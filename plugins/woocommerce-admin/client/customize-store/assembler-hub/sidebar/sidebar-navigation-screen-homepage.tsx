/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useCallback,
	useContext,
	useEffect,
} from '@wordpress/element';
import { Link, CollapsibleContent } from '@woocommerce/components';
import { Spinner } from '@wordpress/components';

// @ts-expect-error Missing type in core-data.
import { __experimentalBlockPatternsList as BlockPatternList } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */
import { SidebarNavigationScreen } from './sidebar-navigation-screen';
import { ADMIN_URL } from '~/utils/admin-settings';
import { useEditorBlocks } from '../hooks/use-editor-blocks';
import { usePatternsByCategory, Pattern } from '../hooks/use-pattern';
import { HighlightedBlockContext } from '../context/highlighted-block-context';

function filterPatternsByNames( patterns: Pattern[], namesToFilter: string[] ) {
	return patterns.filter( ( pattern: Pattern ) =>
		namesToFilter.includes( pattern.name )
	);
}

export const SidebarNavigationScreenHomepage = () => {
	const { isLoading, patterns } = usePatternsByCategory( 'woo-commerce' );
	const { setHighlightedBlockIndex, resetHighlightedBlockIndex } = useContext(
		HighlightedBlockContext
	);

	useEffect( () => {
		setHighlightedBlockIndex( 0 );
	}, [ setHighlightedBlockIndex ] );

	const lists = [
		{
			label: __( 'Hero', 'woocommerce' ),
			patterns: filterPatternsByNames( patterns, [
				'woocommerce-blocks/hero-product-3-split',
				'woocommerce-blocks/hero-product-chessboard',
				'woocommerce-blocks/hero-product-split',
				'woocommerce-blocks/just-arrived-full-hero',
				'woocommerce-blocks/product-hero',
				'woocommerce-blocks/product-hero-2-col-2-row',
			] ),
		},
		{
			label: __( 'Featured products', 'woocommerce' ),
			patterns: filterPatternsByNames( patterns, [
				'woocommerce-blocks/featured-category-cover-image',
				'woocommerce-blocks/featured-category-focus',
				'woocommerce-blocks/featured-category-triple',
				'woocommerce-blocks/featured-products-5-item-grid',
				'woocommerce-blocks/product-collections-featured-collection',
				'woocommerce-blocks/product-collections-featured-collections',
				'woocommerce-blocks/product-collections-newest-arrivals',
				'woocommerce-blocks/featured-products-2-cols',
			] ),
		},
		{
			label: __( 'About your store', 'woocommerce' ),
			patterns: filterPatternsByNames( patterns, [
				'woocommerce-blocks/alt-image-and-text',
			] ),
		},
		{
			label: __( 'Testimonials', 'woocommerce' ),
			patterns: filterPatternsByNames( patterns, [
				'woocommerce-blocks/testimonials-3-columns',
				'woocommerce-blocks/testimonials-single',
			] ),
		},
		{
			label: __( 'Social media', 'woocommerce' ),
			patterns: filterPatternsByNames( patterns, [
				'woocommerce-blocks/social-follow-us-in-social-media',
			] ),
		},
	];

	const [ blocks, onChange ] = useEditorBlocks();
	const onClickPattern = useCallback(
		( _pattern, selectedBlocks ) => {
			const newMainBlock = {
				...selectedBlocks[ 0 ],
				attributes: {
					...selectedBlocks[ 0 ].attributes,
					slug: 'homepage',
				},
			};

			onChange(
				[
					...blocks.map( ( block ) => {
						// blocks[1] doesn't have slug attribute
						// Try slug first, then tagName
						if (
							block.attributes?.slug === 'homepage' ||
							block.attributes?.tagName === 'main'
						) {
							return newMainBlock;
						}
						return block;
					} ),
				],
				{ selection: {} }
			);
		},
		[ blocks, onChange ]
	);

	return (
		<SidebarNavigationScreen
			title={ __( 'Change your homepage', 'woocommerce' ) }
			onNavigateBackClick={ resetHighlightedBlockIndex }
			description={ createInterpolateElement(
				__(
					'Based on the most successful stores in your industry and location, our AI tool has recommended this template for your business. Prefer a different layout? Choose from the templates below now, or later via the <EditorLink>Editor</EditorLink>.',
					'woocommerce'
				),
				{
					EditorLink: (
						<Link
							href={ `${ ADMIN_URL }site-editor.php` }
							type="external"
						/>
					),
				}
			) }
			content={
				<>
					<div className="edit-site-sidebar-navigation-screen-patterns__group-homepage">
						{ isLoading && (
							<span className="components-placeholder__preview">
								<Spinner />
							</span>
						) }

						{ ! isLoading &&
							lists.map( ( list ) => {
								if ( list.patterns.length === 0 ) {
									return null;
								}

								return (
									<CollapsibleContent
										toggleText={ list.label }
										key={ list.label }
									>
										<BlockPatternList
											shownPatterns={ list.patterns }
											blockPatterns={ list.patterns }
											onClickPattern={ onClickPattern }
											label={ 'Hompeage' }
											orientation="vertical"
											category={ 'homepage' }
											isDraggable={ false }
											showTitlesAsTooltip={ true }
										/>
									</CollapsibleContent>
								);
							} ) }
					</div>
				</>
			}
		/>
	);
};
