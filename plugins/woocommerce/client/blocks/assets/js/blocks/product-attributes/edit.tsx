/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useQueryLoopProductContextValidation } from '@woocommerce/base-hooks';
import { useSelect } from '@wordpress/data';
import { optionsStore, Product, productsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductAttributesEditProps } from './types';

function Placeholder() {
	const blockProps = useBlockProps();
	return (
		<div { ...blockProps }>
			<p>
				{ __(
					'This block displays product attributes including dimensions and weight. When viewing a product page, the attributes will automatically appear here.',
					'woocommerce'
				) }
			</p>
		</div>
	);
}

const Edit = ( {
	context: { postId, postType },
	clientId,
}: ProductAttributesEditProps ) => {
	const blockProps = useBlockProps();
	const isSpecificProductContext = !! ( postId && postType === 'product' );

	const { dimensionUnit, weightUnit } = useSelect( ( select ) => {
		const { getOption } = select( optionsStore );
		return {
			dimensionUnit: getOption( 'woocommerce_dimension_unit' ) as string,
			weightUnit: getOption( 'woocommerce_weight_unit' ) as string,
		};
	}, [] );

	const { product, isLoading } = useSelect(
		( select ) => {
			const { getProduct } = select( productsStore );
			return {
				product: getProduct( Number( postId ) ),
				isLoading: select( productsStore ).isResolving( 'getProduct', [
					Number( postId ),
				] ),
			};
		},
		[ postId ]
	);

	/**
	 * Validate Query Loop block context
	 */
	const { hasInvalidContext, warningElement } =
		useQueryLoopProductContextValidation( {
			clientId,
			postType,
			blockName: __( 'Product Attributes', 'woocommerce' ),
		} );
	if ( hasInvalidContext ) {
		return warningElement;
	}

	/**
	 * Display loading state
	 */
	if ( isLoading && isSpecificProductContext ) {
		return (
			<div { ...blockProps }>
				<span className="wc-product-attributes__loading">
					{ __( 'Loading…', 'woocommerce' ) }
				</span>
			</div>
		);
	}

	/**
	 * Display no product found message
	 */
	if ( postId && ! product ) {
		return (
			<div { ...blockProps }>
				<p>{ __( 'No product found', 'woocommerce' ) }</p>
			</div>
		);
	}

	const getFormattedDimensions = ( dimensions: Product[ 'dimensions' ] ) => {
		if ( ! dimensions ) return null;

		const dimensionKeys = [
			'length',
			'width',
			'height',
		] as ( keyof Product[ 'dimensions' ] )[];

		const validDimensions = dimensionKeys
			.map( ( key ) => dimensions[ key ] )
			.filter(
				( value ): value is string =>
					typeof value === 'string' && value.length > 0
			);

		if ( validDimensions.length === 0 ) return null;

		return `${ validDimensions.join( ' × ' ) } ${ dimensionUnit }`;
	};

	const formattedDimensions = product?.dimensions
		? getFormattedDimensions( product.dimensions )
		: null;

	return isSpecificProductContext ? (
		/**
		 * Display product attributes
		 */
		<div { ...blockProps }>
			<table className="wc-block-product-attributes">
				<tbody>
					{ /* Display Weight if available */ }
					{ product?.weight && (
						<tr className="wc-block-product-attributes-item wc-block-product-attributes-item__weight">
							<th className="wc-block-product-attributes-item__label">
								{ __( 'Weight', 'woocommerce' ) }
							</th>
							<td className="wc-block-product-attributes-item__value">
								{ `${ product.weight } ${ weightUnit }` }
							</td>
						</tr>
					) }

					{ /* Display Dimensions if available */ }
					{ formattedDimensions && (
						<tr className="wc-block-product-attributes-item wc-block-product-attributes-item__dimensions">
							<th className="wc-block-product-attributes-item__label">
								{ __( 'Dimensions', 'woocommerce' ) }
							</th>
							<td className="wc-block-product-attributes-item__value">
								{ formattedDimensions }
							</td>
						</tr>
					) }

					{ /* Display Product Attributes */ }
					{ product?.attributes?.map( ( attribute ) => (
						<tr
							key={ attribute.id }
							className={ `wc-block-product-attributes-item wc-block-product-attributes-item__${ attribute.name.toLowerCase() }` }
						>
							<th className="wc-block-product-attributes-item__label">
								{ attribute.name }
							</th>
							<td className="wc-block-product-attributes-item__value">
								{ attribute.options.join( ', ' ) }
							</td>
						</tr>
					) ) }
				</tbody>
			</table>
		</div>
	) : (
		<Placeholder />
	);
};

export default Edit;
