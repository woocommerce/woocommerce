/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, info } from '@wordpress/icons';
import ProductsControl from '@woocommerce/editor-components/products-control';
import {
	Placeholder,
	Button,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHStack as HStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text,
} from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import type { ProductCollectionEditComponentProps } from '../types';
import { getCollectionByName } from '../collections';

const HandPickedProductsPicker = ( {
	attributes,
	setAttributes,
}: ProductCollectionEditComponentProps ) => {
	const blockProps = useBlockProps();

	const collection = getCollectionByName( attributes.collection );

	// Convert string IDs to numbers for ProductsControl
	const selectedProductIds = (
		attributes.query?.woocommerceHandPickedProducts || []
	).map( Number );

	const hasSelectedProducts = selectedProductIds.length > 0;

	const handleDone = () => {
		setAttributes( {
			// eslint-disable-next-line @typescript-eslint/naming-convention
			__privateHandPickedProductsPickerDismissed: true,
		} );
	};

	if ( ! collection ) {
		return null;
	}

	return (
		<div { ...blockProps }>
			<Placeholder className="wc-blocks-product-collection__editor-product-picker">
				<VStack spacing={ 4 }>
					<HStack alignment="center">
						{ /* @ts-expect-error Icon types are incomplete */ }
						<Icon
							icon={ info }
							className="wc-blocks-product-collection__info-icon"
						/>
						<Text>
							{ __(
								'Select specific products to recommend to customers.',
								'woocommerce'
							) }
						</Text>
					</HStack>
					<ProductsControl
						selected={ selectedProductIds }
						onChange={ ( value: { id: number }[] = [] ) => {
							const ids = value.map( ( { id } ) => String( id ) );
							setAttributes( {
								query: {
									...attributes.query,
									woocommerceHandPickedProducts: ids,
								},
							} );
						} }
					/>
					{ hasSelectedProducts && (
						<HStack justify="flex-end">
							<Button variant="primary" onClick={ handleDone }>
								{ __( 'Done', 'woocommerce' ) }
							</Button>
						</HStack>
					) }
				</VStack>
			</Placeholder>
		</div>
	);
};

export default HandPickedProductsPicker;
