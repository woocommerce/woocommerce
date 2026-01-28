/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, info } from '@wordpress/icons';
import ProductsControl from '@woocommerce/editor-components/products-control';
import { Placeholder, Button } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import type { ProductCollectionEditComponentProps } from '../types';
import { getCollectionByName } from '../collections';
import { setQueryAttribute } from '../utils';

interface MultiProductPickerProps extends ProductCollectionEditComponentProps {
	onDone: () => void;
}

const MultiProductPicker = ( props: MultiProductPickerProps ) => {
	const { attributes, onDone } = props;
	const blockProps = useBlockProps();

	const collection = getCollectionByName( attributes.collection );

	// Convert string IDs to numbers for ProductsControl.
	const selectedProductIds = (
		attributes.query?.woocommerceHandPickedProducts || []
	).map( Number );

	const hasSelectedProducts = selectedProductIds.length > 0;

	if ( ! collection ) {
		return null;
	}

	return (
		<div { ...blockProps }>
			<Placeholder className="wc-block-editor-product-collection__product-picker">
				<div className="wc-block-editor-product-collection__product-picker-info">
					{ /* @ts-expect-error Icon types are incomplete */ }
					<Icon
						icon={ info }
						className="wc-block-editor-product-collection__info-icon"
					/>
					<span>
						{ __(
							'Select products to display in this collection.',
							'woocommerce'
						) }
					</span>
				</div>
				<div className="wc-block-editor-product-collection__product-picker-selection">
					{ /* @ts-expect-error Props provided by withSearchedProducts HOC */ }
					<ProductsControl
						selected={ selectedProductIds }
						onChange={ ( value = [] ) => {
							const ids = value.map( ( { id }: { id: number } ) =>
								String( id )
							);
							setQueryAttribute( props, {
								woocommerceHandPickedProducts: ids,
							} );
						} }
					/>
					<Button
						variant="primary"
						onClick={ onDone }
						disabled={ ! hasSelectedProducts }
					>
						{ __( 'Done', 'woocommerce' ) }
					</Button>
				</div>
			</Placeholder>
		</div>
	);
};

export default MultiProductPicker;
