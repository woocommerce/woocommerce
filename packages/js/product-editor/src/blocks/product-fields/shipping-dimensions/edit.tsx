/**
 * External dependencies
 */
import { useWooBlockProps } from '@woocommerce/block-templates';
import {
	OPTIONS_STORE_NAME,
	Product,
	ProductDimensions,
} from '@woocommerce/data';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import {
	createElement,
	createInterpolateElement,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ShippingDimensionsBlockAttributes } from './types';
import {
	HighlightSides,
	ShippingDimensionsImage,
} from '../../../components/shipping-dimensions-image';
import { useValidation } from '../../../contexts/validation-context';
import { ProductEditorBlockEditProps } from '../../../types';
import { NumberControl } from '../../../components/number-control';

const SHIPPING_AND_WEIGHT_MIN_VALUE = 0;
const SHIPPING_AND_WEIGHT_MAX_VALUE = 100_000_000_000_000;

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
			value: ( dimensions && dimensions[ name ] ) ?? '',
			onChange: ( value: string ) =>
				setDimensions( {
					...( dimensions ?? {} ),
					[ name ]: value,
				} ),
			onFocus: () => setHighlightSide( side ),
			onBlur: () => setHighlightSide( undefined ),
			suffix: dimensionUnit,
			disabled: attributes.disabled || virtual,
			min: SHIPPING_AND_WEIGHT_MIN_VALUE,
			max: SHIPPING_AND_WEIGHT_MAX_VALUE,
		};
	}

	const widthFieldId = `dimensions_width-${ clientId }`;

	const {
		ref: dimensionsWidthRef,
		error: dimensionsWidthValidationError,
		validate: validateDimensionsWidth,
	} = useValidation< Product >(
		widthFieldId,
		async function dimensionsWidthValidator() {
			if ( dimensions?.width && +dimensions.width <= 0 ) {
				return {
					message: __(
						'Width must be greater than zero.',
						'woocommerce'
					),
				};
			}
		},
		[ dimensions?.width ]
	);

	const lengthFieldId = `dimensions_length-${ clientId }`;

	const {
		ref: dimensionsLengthRef,
		error: dimensionsLengthValidationError,
		validate: validateDimensionsLength,
	} = useValidation< Product >(
		lengthFieldId,
		async function dimensionsLengthValidator() {
			if ( dimensions?.length && +dimensions.length <= 0 ) {
				return {
					message: __(
						'Length must be greater than zero.',
						'woocommerce'
					),
				};
			}
		},
		[ dimensions?.length ]
	);

	const heightFieldId = `dimensions_height-${ clientId }`;

	const {
		ref: dimensionsHeightRef,
		error: dimensionsHeightValidationError,
		validate: validateDimensionsHeight,
	} = useValidation< Product >(
		heightFieldId,
		async function dimensionsHeightValidator() {
			if ( dimensions?.height && +dimensions.height <= 0 ) {
				return {
					message: __(
						'Height must be greater than zero.',
						'woocommerce'
					),
				};
			}
		},
		[ dimensions?.height ]
	);

	const weightFieldId = `weight-${ clientId }`;

	const {
		ref: weightRef,
		error: weightValidationError,
		validate: validateWeight,
	} = useValidation< Product >(
		weightFieldId,
		async function weightValidator() {
			if ( weight && +weight <= 0 ) {
				return {
					message: __(
						'Weight must be greater than zero.',
						'woocommerce'
					),
				};
			}
		},
		[ weight ]
	);

	const dimensionsWidthProps = {
		...getDimensionsControlProps( 'width', 'A' ),
		ref: dimensionsWidthRef,
		onBlur: validateDimensionsWidth,
		id: widthFieldId,
	};
	const dimensionsLengthProps = {
		...getDimensionsControlProps( 'length', 'B' ),
		ref: dimensionsLengthRef,
		onBlur: validateDimensionsLength,
		id: lengthFieldId,
	};
	const dimensionsHeightProps = {
		...getDimensionsControlProps( 'height', 'C' ),
		ref: dimensionsHeightRef,
		onBlur: validateDimensionsHeight,
		id: heightFieldId,
	};
	const weightProps = {
		id: weightFieldId,
		name: 'weight',
		value: weight ?? '',
		onChange: setWeight,
		suffix: weightUnit,
		ref: weightRef,
		onBlur: validateWeight,
		disabled: attributes.disabled || virtual,
		min: SHIPPING_AND_WEIGHT_MIN_VALUE,
		max: SHIPPING_AND_WEIGHT_MAX_VALUE,
	};

	return (
		<div { ...blockProps }>
			<h4>{ __( 'Dimensions', 'woocommerce' ) }</h4>

			<div className="wp-block-columns">
				<div className="wp-block-column">
					<NumberControl
						label={ createInterpolateElement(
							__( 'Width <Side />', 'woocommerce' ),
							{ Side: <span>A</span> }
						) }
						error={ dimensionsWidthValidationError }
						{ ...dimensionsWidthProps }
					/>
					<NumberControl
						label={ createInterpolateElement(
							__( 'Length <Side />', 'woocommerce' ),
							{ Side: <span>B</span> }
						) }
						error={ dimensionsLengthValidationError }
						{ ...dimensionsLengthProps }
					/>
					<NumberControl
						label={ createInterpolateElement(
							__( 'Height <Side />', 'woocommerce' ),
							{ Side: <span>C</span> }
						) }
						error={ dimensionsHeightValidationError }
						{ ...dimensionsHeightProps }
					/>
					<NumberControl
						label={ __( 'Weight', 'woocommerce' ) }
						error={ weightValidationError }
						{ ...weightProps }
					/>
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
