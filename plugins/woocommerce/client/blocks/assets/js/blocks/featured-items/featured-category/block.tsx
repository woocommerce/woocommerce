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
	Spinner,
	ToolbarButton,
	ToolbarGroup,
	withSpokenMessages,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { folderStarred } from '@woocommerce/icons';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { withCategory } from '@woocommerce/block-hocs';
import type { WP_REST_API_Category } from 'wp-types';
import { useDispatch, useRegistry } from '@wordpress/data';
import type { BlockInstance } from '@wordpress/blocks';
import type { ComponentType, Dispatch, SetStateAction } from 'react';

/**
 * Internal dependencies
 */
import { FEATURED_CATEGORY_DEFAULT_TEMPLATE } from '../constants';
import { withEditMode } from '../with-edit-mode';
import { withApiError } from '../with-api-error';
import { useCoverImage } from './use-cover-image';

const EDIT_MODE_CONFIG = {
	icon: folderStarred,
	label: __( 'Featured Category', 'woocommerce' ),
	description: __(
		'Visually highlight a product category and encourage prompt action.',
		'woocommerce'
	),
	editLabel: __( 'Showing Featured Category block preview.', 'woocommerce' ),
};

interface Props {
	attributes: { categoryId?: number; layout?: string };
	setAttributes: ( attributes: Record< string, unknown > ) => void;
	category?: WP_REST_API_Category;
	isLoading: boolean;
	clientId: string;
}

const withCoverAttributes = ( Component: ComponentType< Props > ) =>
	function CoverAttributes( props: Props ) {
		const { attributes, category, clientId, setAttributes } = props;
		const { updateBlockAttributes } = useDispatch( blockEditorStore );
		const registry = useRegistry();
		const previousUrl = useRef< string >();

		useEffect( () => {
			if (
				! previousUrl.current ||
				! category ||
				category.id !== attributes.categoryId
			) {
				return;
			}
			const oldUrl = previousUrl.current;
			previousUrl.current = undefined;
			const updateCategoryButton = (
				children: BlockInstance[]
			): boolean =>
				children.some( ( child ) => {
					if (
						child.name === 'core/button' &&
						! child.attributes.metadata?.bindings?.url &&
						child.attributes.url === oldUrl &&
						category.permalink
					) {
						void updateBlockAttributes( child.clientId, {
							url: category.permalink,
						} );
						return true;
					}
					return updateCategoryButton( child.innerBlocks );
				} );
			updateCategoryButton(
				registry.select( blockEditorStore ).getBlocks( clientId )
			);
		}, [
			attributes.categoryId,
			category,
			clientId,
			registry,
			updateBlockAttributes,
		] );

		return (
			<Component
				{ ...props }
				setAttributes={ ( { categoryId } ) => {
					previousUrl.current = category?.permalink;
					setAttributes( {
						categoryId: Number( categoryId ),
						layout: 'cover',
						termTaxonomy: 'product_cat',
					} );
				} }
			/>
		);
	};

function FeaturedCategory( {
	attributes,
	category,
	isLoading,
	clientId,
	useEditMode: [ , setEditMode ],
}: Props & {
	useEditMode: [ boolean, Dispatch< SetStateAction< boolean > > ];
} ) {
	useCoverImage(
		clientId,
		category?.id === attributes.categoryId ? category : undefined
	);
	if ( isLoading || ! category ) {
		return <Spinner />;
	}

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon="edit"
						label={ __( 'Edit selected category', 'woocommerce' ) }
						onClick={ () => setEditMode( true ) }
					/>
				</ToolbarGroup>
			</BlockControls>
			<BlockContextProvider
				value={ {
					termId: attributes.categoryId,
					termTaxonomy: 'product_cat',
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

export default compose( [
	withCategory,
	withCoverAttributes,
	withSpokenMessages,
	withEditMode( EDIT_MODE_CONFIG ),
	withApiError,
] )( FeaturedCategory );
