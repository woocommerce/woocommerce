/**
 * External dependencies
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useQueryLoopProductContextValidation } from '@woocommerce/base-hooks';
import { useSelect } from '@wordpress/data';
import { optionsStore, Product, productsStore } from '@woocommerce/data';
import { PanelBody, ToggleControl } from '@wordpress/components';

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

const EMPTY_PRODUCT_RESULT = {
	product: null,
	isLoadingProduct: false,
};

const Edit = ( {
	context: { postId, postType },
	clientId,
	attributes,
	setAttributes,
}: ProductSpecificationsEditProps ) => {
	const { showWeight, showDimensions, showAttributes } = attributes;
	const blockProps = useBlockProps( {
		className: 'wp-block-table',
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
			if ( ! postId ) return EMPTY_PRODUCT_RESULT;
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

	const productData: Record< string, { label: string; value: string } > = {};

	if ( showWeight ) {
		productData.weight = {
			label: __( 'Weight', 'woocommerce' ),
			value: '',
		};

		if ( isSpecificProductContext ) {
			productData.weight.value = product?.weight
				? `${ product.weight } ${ weightUnit }`
				: '';
		} else {
			productData.weight.value = `10 ${ weightUnit }`;
		}
	}

	if ( showDimensions ) {
		productData.dimensions = {
			label: __( 'Dimensions', 'woocommerce' ),
			value: '',
		};

		if ( isSpecificProductContext ) {
			productData.dimensions.value = product?.dimensions
				? getFormattedDimensions( product.dimensions, dimensionUnit )
				: '';
		} else {
			productData.dimensions.value = `10 × 10 × 10 ${ dimensionUnit }`;
		}
	}

	if ( showAttributes ) {
		if ( isSpecificProductContext ) {
			if ( product?.attributes ) {
				product.attributes.forEach( ( attribute ) => {
					productData[ attribute.name.toLowerCase() ] = {
						label: attribute.name,
						value: attribute.options.join( ', ' ),
					};
				} );
			}
		} else {
			productData.test_attribute = {
				label: __( 'Test Attribute', 'woocommerce' ),
				value: __( 'First, Second, Third', 'woocommerce' ),
			};
		}
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Display Settings', 'woocommerce' ) }>
					<ToggleControl
						label={ __( 'Show Weight', 'woocommerce' ) }
						checked={ showWeight }
						onChange={ () =>
							setAttributes( { showWeight: ! showWeight } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Dimensions', 'woocommerce' ) }
						checked={ showDimensions }
						onChange={ () =>
							setAttributes( {
								showDimensions: ! showDimensions,
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Show Attributes', 'woocommerce' ) }
						checked={ showAttributes }
						onChange={ () =>
							setAttributes( {
								showAttributes: ! showAttributes,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<figure { ...blockProps }>
				<table>
					<thead className="screen-reader-text">
						<tr>
							<th>{ __( 'Attributes', 'woocommerce' ) }</th>
							<th>{ __( 'Value', 'woocommerce' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ Object.entries( productData ).map(
							( [ key, data ] ) =>
								data.value && (
									<tr
										key={ key }
										className={ `wp-block-product-specifications-item wc-block-product-specifications-item-${ key }` }
									>
										<th
											scope="row"
											className="wp-block-product-specifications-item__label"
										>
											{ data.label }
										</th>
										<td className="wp-block-product-specifications-item__value">
											{ data.value }
										</td>
									</tr>
								)
						) }
					</tbody>
				</table>
			</figure>
		</>
	);
};

export default Edit;
