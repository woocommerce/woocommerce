/**
 * External dependencies
 */
import {
	BlockContextProvider,
	BlockControls,
	InnerBlocks,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	Button,
	Placeholder,
	Spinner,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { withCategory } from '@woocommerce/block-hocs';
import { getSetting } from '@woocommerce/settings';
import ProductCategoryControl from '@woocommerce/editor-components/product-category-control';
import type { SearchListItem } from '@woocommerce/editor-components/search-list-control/types';
import type { ProductCategoryResponseItem } from '@woocommerce/types';
import type { WP_REST_API_Category } from 'wp-types';
import { useDispatch, useRegistry } from '@wordpress/data';
import type { BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { FEATURED_CATEGORY_DEFAULT_TEMPLATE } from '../constants';
import { getCategoryImageId, getCategoryImageSrc } from './utils';

interface Props {
	attributes: { categoryId?: number; source?: string; layout?: string };
	setAttributes: ( attributes: Record< string, unknown > ) => void;
	category?: WP_REST_API_Category;
	isLoading: boolean;
	clientId: string;
	context: { termId?: number; taxonomy?: string; termTaxonomy?: string };
}

function FeaturedCategory( {
	attributes,
	setAttributes,
	category,
	isLoading,
	context,
	clientId,
}: Props ) {
	const { updateBlockAttributes } = useDispatch( blockEditorStore );
	const registry = useRegistry();
	const [ editing, setEditing ] = useState( false );
	const [ selected, setSelected ] = useState<
		SearchListItem< ProductCategoryResponseItem >[]
	>( [] );
	const selectCategory = () => {
		const nextCategory = selected[ 0 ];
		const updateLegacyButton = ( children: BlockInstance[] ): boolean =>
			children.some( ( child ) => {
				if (
					child.name === 'core/button' &&
					! child.attributes.metadata?.bindings?.url &&
					category?.permalink &&
					child.attributes.url === category.permalink &&
					nextCategory.details?.permalink
				) {
					void updateBlockAttributes( child.clientId, {
						url: nextCategory.details.permalink,
					} );
					return true;
				}
				return updateLegacyButton( child.innerBlocks );
			} );
		updateLegacyButton(
			registry.select( blockEditorStore ).getBlocks( clientId )
		);
		setAttributes( {
			categoryId: Number( nextCategory.id ),
			source: 'selected',
			layout: 'cover',
			termTaxonomy: 'product_cat',
		} );
		setEditing( false );
	};
	const inherited =
		! attributes.categoryId &&
		attributes.source !== 'selected' &&
		( context.termTaxonomy || context.taxonomy ) === 'product_cat';
	useEffect( () => {
		if ( inherited && attributes.layout !== 'cover' ) {
			setAttributes( {
				layout: 'cover',
				source: 'context',
				termTaxonomy: 'product_cat',
			} );
		}
	}, [ inherited, attributes.layout, setAttributes ] );

	if ( isLoading ) {
		return <Spinner />;
	}

	if ( editing || ! category ) {
		return (
			<Placeholder label={ __( 'Featured Category', 'woocommerce' ) }>
				<ProductCategoryControl
					selected={ selected.map( ( item ) => Number( item.id ) ) }
					onChange={ setSelected }
					isSingle
				/>
				<Button
					variant="primary"
					disabled={ ! selected.length }
					onClick={ selectCategory }
				>
					{ __( 'Done', 'woocommerce' ) }
				</Button>
			</Placeholder>
		);
	}

	const termId = attributes.categoryId || context.termId;
	return (
		<>
			{ ! inherited && (
				<BlockControls>
					<ToolbarGroup>
						<ToolbarButton
							icon="edit"
							label={ __(
								'Edit selected category',
								'woocommerce'
							) }
							onClick={ () => setEditing( true ) }
						/>
					</ToolbarGroup>
				</BlockControls>
			) }
			<BlockContextProvider
				value={ {
					termId,
					termTaxonomy: 'product_cat',
					taxonomy: 'product_cat',
					'woocommerce/featuredCategoryTermId': termId,
					'woocommerce/featuredCategoryTaxonomy': 'product_cat',
					'woocommerce/termImageId': getCategoryImageId( category ),
					'woocommerce/termImageUrl':
						getCategoryImageSrc( category ) ||
						getSetting< string >( 'placeholderImgSrcFullSize', '' ),
				} }
			>
				<InnerBlocks
					allowedBlocks={ [ 'core/cover' ] }
					template={ FEATURED_CATEGORY_DEFAULT_TEMPLATE( category ) }
				/>
			</BlockContextProvider>
		</>
	);
}

export default withCategory( FeaturedCategory );
