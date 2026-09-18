/**
 * External dependencies
 */
import {
	BlockContextProvider,
	BlockControls,
	InnerBlocks,
} from '@wordpress/block-editor';
import {
	Button,
	Placeholder,
	Spinner,
	ToolbarButton,
	ToolbarGroup,
	withSpokenMessages,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { useEffect } from '@wordpress/element';
import { folderStarred } from '@woocommerce/icons';
import { __ } from '@wordpress/i18n';
import { withCategory } from '@woocommerce/block-hocs';
import type { WP_REST_API_Category } from 'wp-types';
import type { ComponentType, Dispatch, SetStateAction } from 'react';

/**
 * Internal dependencies
 */
import { FEATURED_CATEGORY_DEFAULT_TEMPLATE } from '../constants';
import { withEditMode } from '../with-edit-mode';
import { withApiError } from '../with-api-error';
import { withUpdateButtonAttributes } from '../with-update-button-attributes';
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
	attributes: { categoryId?: number | 'preview'; layout?: string };
	setAttributes: ( attributes: Record< string, unknown > ) => void;
	category?: WP_REST_API_Category;
	isLoading: boolean;
	clientId: string;
	effectiveCategoryId?: number | 'preview';
	canEditItem?: boolean;
}

const withCoverAttributes = ( Component: ComponentType< Props > ) =>
	function CoverAttributes( props: Props ) {
		const { setAttributes } = props;
		useEffect( () => {
			// Inherited categories skip the selector that normally initializes the layout.
			if ( props.category && props.attributes.layout !== 'cover' ) {
				setAttributes( { layout: 'cover' } );
			}
		}, [ props.category, props.attributes.layout, setAttributes ] );

		return (
			<Component
				{ ...props }
				setAttributes={ ( { categoryId } ) => {
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
	effectiveCategoryId,
	canEditItem = true,
	useEditMode: [ , setEditMode ],
}: Props & {
	useEditMode: [ boolean, Dispatch< SetStateAction< boolean > > ];
} ) {
	useCoverImage(
		clientId,
		category?.id === ( attributes.categoryId || effectiveCategoryId )
			? category
			: undefined
	);
	if ( isLoading ) {
		return canEditItem ? <Spinner /> : null;
	}
	if ( ! category ) {
		return (
			<Placeholder
				icon={ folderStarred }
				label={ EDIT_MODE_CONFIG.label }
				instructions={
					canEditItem
						? __(
								'No product category is selected.',
								'woocommerce'
						  )
						: __(
								'No product category is available.',
								'woocommerce'
						  )
				}
			>
				{ canEditItem && (
					<Button
						variant="primary"
						onClick={ () => setEditMode( true ) }
					>
						{ __( 'Select a category', 'woocommerce' ) }
					</Button>
				) }
			</Placeholder>
		);
	}

	return (
		<>
			{ canEditItem && (
				<BlockControls>
					<ToolbarGroup>
						<ToolbarButton
							icon="edit"
							label={ __(
								'Edit selected category',
								'woocommerce'
							) }
							onClick={ () => setEditMode( true ) }
						/>
					</ToolbarGroup>
				</BlockControls>
			) }
			<BlockContextProvider
				value={ {
					termId:
						attributes.categoryId === 'preview'
							? undefined
							: category.id,
					termTaxonomy: 'product_cat',
					taxonomy: 'product_cat',
				} }
			>
				<InnerBlocks
					allowedBlocks={ [ 'core/cover' ] }
					template={ FEATURED_CATEGORY_DEFAULT_TEMPLATE(
						category,
						! attributes.categoryId
					) }
				/>
			</BlockContextProvider>
		</>
	);
}

export default compose( [
	withCategory,
	withCoverAttributes,
	withSpokenMessages,
	withUpdateButtonAttributes,
	withEditMode( EDIT_MODE_CONFIG ),
	withApiError,
] )( FeaturedCategory );
