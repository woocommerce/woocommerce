/**
 * External dependencies
 */
import { useWooBlockProps } from '@woocommerce/block-templates';
import {
	OPTIONS_STORE_NAME,
	Product,
	ProductDimensions,
} from '@woocommerce/data';
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import {
	createElement,
	createInterpolateElement,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ShippingDimensionsBlockAttributes } from './types';
import { useProductHelper } from '../../../hooks/use-product-helper';
import {
	HighlightSides,
	ShippingDimensionsImage,
} from '../../../components/shipping-dimensions-image';
import { useValidation } from '../../../contexts/validation-context';
import { ProductEditorBlockEditProps } from '../../../types';

export function Edit( {
	attributes,
	clientId,
	context,
}: ProductEditorBlockEditProps< ShippingDimensionsBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	const [ dimensions, setDimensions ] =
		useEntityProp< Partial< ProductDimensions > | null >(
			'postType',
			context.postType,
			'dimensions'
		);

	const [ weight, setWeight ] = useEntityProp< string | null >(
		'postType',
		context.postType,
		'weight'
	);

	const [ virtual ] = useEntityProp< boolean >(
		'postType',
		context.postType,
		'virtual'
	);

	const [ highlightSide, setHighlightSide ] = useState< HighlightSides >();

	const { formatNumber, parseNumber } = useProductHelper();

	const { dimensionUnit, weightUnit } = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );
		return {
			dimensionUnit: getOption( 'woocommerce_dimension_unit' ),
			weightUnit: getOption( 'woocommerce_weight_unit' ),
		};
	}, [] );

	function getDimensionsControlProps(
		name: keyof ProductDimensions,
		side: HighlightSides
	) {
		return {
			name: `dimensions.${ name }`,
			value: dimensions
				? formatNumber( String( dimensions[ name ] ) )
				: undefined,
			onChange: ( value: string ) =>
				setDimensions( {
					...( dimensions ?? {} ),
					[ name ]: parseNumber( value ),
				} ),
			onFocus: () => setHighlightSide( side ),
			onBlur: () => setHighlightSide( undefined ),
			suffix: dimensionUnit,
			disabled: virtual,
		};
	}

	const {
		ref: dimensionsWidthRef,
		error: dimensionsWidthValidationError,
		validate: validateDimensionsWidth,
	} = useValidation< Product >(
		`dimensions_width-${ clientId }`,
		async function dimensionsWidthValidator() {
			if ( dimensions?.width && +dimensions.width <= 0 ) {
				return __( 'Width must be greater than zero.', 'woocommerce' );
			}
		},
		[ dimensions?.width ]
	);

	const {
		ref: dimensionsLengthRef,
		error: dimensionsLengthValidationError,
		validate: validateDimensionsLength,
	} = useValidation< Product >(
		`dimensions_length-${ clientId }`,
		async function dimensionsLengthValidator() {
			if ( dimensions?.length && +dimensions.length <= 0 ) {
				return __( 'Length must be greater than zero.', 'woocommerce' );
			}
		},
		[ dimensions?.length ]
	);

	const {
		ref: dimensionsHeightRef,
		error: dimensionsHeightValidationError,
		validate: validateDimensionsHeight,
	} = useValidation< Product >(
		`dimensions_height-${ clientId }`,
		async function dimensionsHeightValidator() {
			if ( dimensions?.height && +dimensions.height <= 0 ) {
				return __( 'Height must be greater than zero.', 'woocommerce' );
			}
		},
		[ dimensions?.height ]
	);

	const {
		ref: weightRef,
		error: weightValidationError,
		validate: validateWeight,
	} = useValidation< Product >(
		`weight-${ clientId }`,
		async function weightValidator() {
			if ( weight && +weight <= 0 ) {
				return __( 'Weight must be greater than zero.', 'woocommerce' );
			}
		},
		[ weight ]
	);

	const dimensionsWidthProps = {
		...getDimensionsControlProps( 'width', 'A' ),
		id: useInstanceId(
			BaseControl,
			`product_shipping_dimensions_width`
		) as string,
		ref: dimensionsWidthRef,
		onBlur: validateDimensionsWidth,
	};
	const dimensionsLengthProps = {
		...getDimensionsControlProps( 'length', 'B' ),
		id: useInstanceId(
			BaseControl,
			`product_shipping_dimensions_length`
		) as string,
		ref: dimensionsLengthRef,
		onBlur: validateDimensionsLength,
	};
	const dimensionsHeightProps = {
		...getDimensionsControlProps( 'height', 'C' ),
		id: useInstanceId(
			BaseControl,
			`product_shipping_dimensions_height`
		) as string,
		ref: dimensionsHeightRef,
		onBlur: validateDimensionsHeight,
	};
	const weightProps = {
		id: useInstanceId( BaseControl, `product_shipping_weight` ) as string,
		name: 'weight',
		value: formatNumber( String( weight ) ),
		onChange: ( value: string ) => setWeight( parseNumber( value ) ),
		suffix: weightUnit,
		ref: weightRef,
		onBlur: validateWeight,
		disabled: virtual,
	};

	return (
		<div { ...blockProps }>
			<h4>{ __( 'Dimensions', 'woocommerce' ) }</h4>

			<div className="wp-block-columns">
				<div className="wp-block-column">
					<BaseControl
						id={ dimensionsWidthProps.id }
						label={ createInterpolateElement(
							__( 'Width <Side />', 'woocommerce' ),
							{ Side: <span>A</span> }
						) }
						className={ classNames( {
							'has-error': dimensionsWidthValidationError,
						} ) }
						help={ dimensionsWidthValidationError }
					>
						<InputControl { ...dimensionsWidthProps } />
					</BaseControl>

					<BaseControl
						id={ dimensionsLengthProps.id }
						label={ createInterpolateElement(
							__( 'Length <Side />', 'woocommerce' ),
							{ Side: <span>B</span> }
						) }
						className={ classNames( {
							'has-error': dimensionsLengthValidationError,
						} ) }
						help={ dimensionsLengthValidationError }
					>
						<InputControl { ...dimensionsLengthProps } />
					</BaseControl>

					<BaseControl
						id={ dimensionsHeightProps.id }
						label={ createInterpolateElement(
							__( 'Height <Side />', 'woocommerce' ),
							{ Side: <span>C</span> }
						) }
						className={ classNames( {
							'has-error': dimensionsHeightValidationError,
						} ) }
						help={ dimensionsHeightValidationError }
					>
						<InputControl { ...dimensionsHeightProps } />
					</BaseControl>

					<BaseControl
						id={ weightProps.id }
						label={ __( 'Weight', 'woocommerce' ) }
						className={ classNames( {
							'has-error': weightValidationError,
						} ) }
						help={ weightValidationError }
					>
						<InputControl { ...weightProps } />
					</BaseControl>
				</div>

				<div className="wp-block-column">
					<ShippingDimensionsImage
						highlight={ highlightSide }
						className="wp-block-woocommerce-product-shipping-dimensions-fields__dimensions-image"
						labels={ {
							A: dimensionsWidthProps.value?.length
								? dimensionsWidthProps.value
								: undefined,
							B: dimensionsLengthProps.value?.length
								? dimensionsLengthProps.value
								: undefined,
							C: dimensionsHeightProps.value?.length
								? dimensionsHeightProps.value
								: undefined,
						} }
					/>
				</div>
			</div>
		</div>
	);
}
