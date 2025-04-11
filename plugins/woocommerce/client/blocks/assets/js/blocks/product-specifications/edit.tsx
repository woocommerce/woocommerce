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
import { ProductSpecificationsEditProps } from './types';

const getFormattedDimensions = (
	dimensions: Product[ 'dimensions' ],
	dimensionUnit: string
) => {
	if ( ! dimensions ) return '';

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

	if ( validDimensions.length === 0 ) return '';

	return `${ validDimensions.join( ' × ' ) } ${ dimensionUnit }`;
};

const Edit = ( {
	context: { postId, postType },
	clientId,
}: ProductSpecificationsEditProps ) => {
	const blockProps = useBlockProps( {
		className: 'wc-block-product-specifications',
	} );
	const isSpecificProductContext = !! ( postId && postType === 'product' );

	const { dimensionUnit, weightUnit, isLoadingUnits } = useSelect(
		( select ) => {
			const { getOption } = select( optionsStore );
			return {
				dimensionUnit: getOption(
					'woocommerce_dimension_unit'
				) as string,
				weightUnit: getOption( 'woocommerce_weight_unit' ) as string,
				isLoadingUnits:
					! select( optionsStore ).hasFinishedResolution(
						'getOption',
						[ 'woocommerce_dimension_unit' ]
					) ||
					! select( optionsStore ).hasFinishedResolution(
						'getOption',
						[ 'woocommerce_weight_unit' ]
					),
			};
		},
		[]
	);

	const { product, isLoadingProduct } = useSelect(
		( select ) => {
			const { getProduct } = select( productsStore );
			return {
				product: getProduct( Number( postId ) ),
				isLoadingProduct: ! select(
					productsStore
				).hasFinishedResolution( 'getProduct', [ Number( postId ) ] ),
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
			blockName: __( 'Product Specifications', 'woocommerce' ),
		} );
	if ( hasInvalidContext ) {
		return warningElement;
	}

	/**
	 * Display loading state
	 */
	if ( isLoadingUnits || ( isLoadingProduct && isSpecificProductContext ) ) {
		return (
			<div { ...blockProps }>
				<span className="wc-product-specifications__loading">
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

	const productData: Record< string, { label: string; value: string } > = {
		weight: {
			label: __( 'Weight', 'woocommerce' ),
			value: '',
		},
		dimensions: {
			label: __( 'Dimensions', 'woocommerce' ),
			value: '',
		},
	};

	if ( isSpecificProductContext ) {
		productData.weight.value = product?.weight
			? `${ product.weight } ${ weightUnit }`
			: '';
		productData.dimensions.value = product?.dimensions
			? getFormattedDimensions( product.dimensions, dimensionUnit )
			: '';
		product?.attributes?.forEach( ( attribute ) => {
			productData[ attribute.name.toLowerCase() ] = {
				label: attribute.name,
				value: attribute.options.join( ', ' ),
			};
		} );
	} else {
		productData.weight.value = `10 ${ weightUnit }`;
		productData.dimensions.value = `10 × 10 × 10 ${ dimensionUnit }`;
		productData.test_attribute = {
			label: __( 'Test Attribute', 'woocommerce' ),
			value: __( 'First, Second, Third', 'woocommerce' ),
		};
	}

	return (
		<table { ...blockProps }>
			<tbody>
				{ Object.entries( productData ).map(
					( [ key, data ] ) =>
						data.value && (
							<tr
								key={ key }
								className={ `wc-block-product-specifications-item wc-block-product-specifications-item__${ key }` }
							>
								<th className="wc-block-product-specifications-item__label">
									{ data.label }
								</th>
								<td className="wc-block-product-specifications-item__value">
									{ data.value }
								</td>
							</tr>
						)
				) }
			</tbody>
		</table>
	);
};

export default Edit;
