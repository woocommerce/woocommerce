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
	attributes: { categoryId?: number; layout?: string };
	setAttributes: ( attributes: Record< string, unknown > ) => void;
	category?: WP_REST_API_Category;
	isLoading: boolean;
	clientId: string;
}

const withCoverAttributes = ( Component: ComponentType< Props > ) =>
	function CoverAttributes( props: Props ) {
		const { setAttributes } = props;

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
	useEditMode: [ , setEditMode ],
}: Props & {
	useEditMode: [ boolean, Dispatch< SetStateAction< boolean > > ];
} ) {
	useCoverImage(
		clientId,
		category?.id === attributes.categoryId ? category : undefined
	);
	if ( isLoading ) {
		return <Spinner />;
	}
	if ( ! category ) {
		return (
			<Placeholder
				icon={ folderStarred }
				label={ EDIT_MODE_CONFIG.label }
				instructions={ __(
					'No product category is selected.',
					'woocommerce'
				) }
			>
				<Button variant="primary" onClick={ () => setEditMode( true ) }>
					{ __( 'Select a category', 'woocommerce' ) }
				</Button>
			</Placeholder>
		);
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
	withUpdateButtonAttributes,
	withEditMode( EDIT_MODE_CONFIG ),
	withApiError,
] )( FeaturedCategory );
