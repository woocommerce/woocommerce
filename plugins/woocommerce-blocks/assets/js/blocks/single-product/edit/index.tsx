/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { Placeholder, Button, PanelBody } from '@wordpress/components';
import { withProduct } from '@woocommerce/block-hocs';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';
import EditProductLink from '@woocommerce/editor-components/edit-product-link';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { ProductResponseItem } from '@woocommerce/types';
import ErrorPlaceholder, {
	ErrorObject,
} from '@woocommerce/editor-components/error-placeholder';

import { PRODUCTS_STORE_NAME, Product } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
/**
 * Internal dependencies
 */
import './editor.scss';
import SharedProductControl from './shared-product-control';
import EditorBlockControls from './editor-block-controls';
import LayoutEditor from './layout-editor';
import { BLOCK_ICON } from '../constants';
import metadata from '../block.json';
import { Attributes } from '../types';

interface EditorProps {
	className: string;
	attributes: {
		productId: number;
		isPreview: boolean;
	};
	setAttributes: ( attributes: Attributes ) => void;
	error: string | ErrorObject;
	getProduct: () => void;
	product: ProductResponseItem;
	isLoading: boolean;
	clientId: string;
}

const Editor = ( {
	attributes,
	setAttributes,
	error,
	getProduct,
	product,
	isLoading,
	clientId,
}: EditorProps ) => {
	const { productId, isPreview } = attributes;
	const [ isEditing, setIsEditing ] = useState( ! productId );
	const blockProps = useBlockProps();

	const productPreview = useSelect( ( select ) => {
		if ( ! isPreview ) {
			return null;
		}
		return select( PRODUCTS_STORE_NAME ).getProducts< Array< Product > >( {
			per_page: 1,
		} );
	} );

	useEffect( () => {
		const productPreviewId = productPreview
			? productPreview[ 0 ]?.id
			: null;
		if ( ! productPreviewId ) {
			return;
		}

		setAttributes( {
			...attributes,
			productId: productPreviewId,
		} );
		setIsEditing( false );
	}, [ attributes, productPreview, setAttributes ] );

	if ( error ) {
		return (
			<ErrorPlaceholder
				className="wc-block-editor-single-product-error"
				error={ error as ErrorObject }
				isLoading={ isLoading }
				onRetry={ getProduct }
			/>
		);
	}

	return (
		<div { ...blockProps }>
			{ /* eslint-disable-next-line @typescript-eslint/ban-ts-comment */ }
			{ /* @ts-ignore */ }
			<BlockErrorBoundary
				header={ __(
					'Single Product Block Error',
					'woo-gutenberg-products-block'
				) }
			>
				<EditorBlockControls
					setIsEditing={ setIsEditing }
					isEditing={ isEditing }
				/>
				{ isEditing ? (
					<Placeholder
						icon={ BLOCK_ICON }
						label={ metadata.title }
						className="wc-block-editor-single-product"
					>
						{ metadata.description }
						<div className="wc-block-editor-single-product__selection">
							<SharedProductControl
								attributes={ attributes }
								setAttributes={ setAttributes }
							/>
							<Button
								variant="secondary"
								onClick={ () => {
									setIsEditing( false );
								} }
							>
								{ __( 'Done', 'woo-gutenberg-products-block' ) }
							</Button>
						</div>
					</Placeholder>
				) : (
					<div>
						<InspectorControls>
							<PanelBody
								title={ __(
									'Product',
									'woo-gutenberg-products-block'
								) }
								initialOpen={ false }
							>
								<SharedProductControl
									attributes={ attributes }
									setAttributes={ setAttributes }
								/>
							</PanelBody>
						</InspectorControls>

						<EditProductLink productId={ productId } />
						<LayoutEditor
							clientId={ clientId }
							product={ product }
							isLoading={ isLoading }
						/>
					</div>
				) }
			</BlockErrorBoundary>
		</div>
	);
};

export default withProduct( Editor );
