/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';
import { Link } from '@woocommerce/components';
import {
	EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME,
	ProductShippingClass,
} from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { useBlockProps } from '@wordpress/block-editor';
import { BaseControl, SelectControl } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import {
	Fragment,
	createElement,
	createInterpolateElement,
	useEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { ShippingFeeBlockAttributes } from './types';
import { useValidation } from '../../hooks/use-validation';
import { RadioField } from '../../components/radio-field';

const FOLLOW_CLASS_OPTION_VALUE = 'follow_class';
const FREE_SHIPPING_OPTION_VALUE = 'free_shipping';

const options = [
	{
		label: __( 'Follow class', 'woocommerce' ),
		value: FOLLOW_CLASS_OPTION_VALUE,
	},
	{
		label: __( 'Free shipping', 'woocommerce' ),
		value: FREE_SHIPPING_OPTION_VALUE,
	},
];

export function Edit( {
	attributes,
}: BlockEditProps< ShippingFeeBlockAttributes > ) {
	const { title } = attributes;

	const blockProps = useBlockProps();

	const [ option, setOption ] = useState< string >(
		FREE_SHIPPING_OPTION_VALUE
	);

	const [ shippingClass, setShippingClass ] = useEntityProp< string >(
		'postType',
		'product',
		'shipping_class'
	);

	const { shippingClasses } = useSelect( ( select ) => {
		const { getProductShippingClasses } = select(
			EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME
		);
		return {
			shippingClasses:
				getProductShippingClasses< ProductShippingClass[] >() ?? [],
		};
	}, [] );

	const shippingClassControlId = useInstanceId( BaseControl ) as string;

	const shippingClassValidationError = useValidation(
		'product/shipping_class',
		function shippingClassValidator() {
			if ( option === FOLLOW_CLASS_OPTION_VALUE && ! shippingClass ) {
				return __( 'The shipping class is required.', 'woocommerce' );
			}
		}
	);

	function handleOptionChange( value: string ) {
		setOption( value );

		if ( value === FOLLOW_CLASS_OPTION_VALUE ) {
			const [ firstShippingClass ] = shippingClasses;
			setShippingClass( firstShippingClass?.slug ?? '' );
		} else {
			setShippingClass( '' );
		}
	}

	useEffect( () => {
		if ( shippingClass === '' ) {
			setOption( FREE_SHIPPING_OPTION_VALUE );
		} else if ( shippingClass ) {
			setOption( FOLLOW_CLASS_OPTION_VALUE );
		}
	}, [ shippingClass ] );

	return (
		<div { ...blockProps }>
			<div className="wp-block-columns">
				<div className="wp-block-column">
					<RadioField
						title={ title }
						selected={ option }
						options={ options }
						onChange={ handleOptionChange }
					/>
				</div>
			</div>

			{ option === FOLLOW_CLASS_OPTION_VALUE && (
				<div className="wp-block-columns">
					<div
						className={ classNames( 'wp-block-column', {
							'has-error': shippingClassValidationError,
						} ) }
					>
						<SelectControl
							id={ shippingClassControlId }
							name="shipping_class"
							value={ shippingClass }
							onChange={ setShippingClass }
							label={ __( 'Shipping class', 'woocommerce' ) }
							help={
								shippingClassValidationError ||
								createInterpolateElement(
									__(
										'Manage shipping classes and rates in <Link>global settings</Link>.',
										'woocommerce'
									),
									{
										Link: (
											<Link
												href={ getNewPath(
													{
														tab: 'shipping',
														section: 'classes',
													},
													'',
													{},
													'wc-settings'
												) }
												target="_blank"
												type="external"
												onClick={ () => {
													recordEvent(
														'product_shipping_global_settings_link_click'
													);
												} }
											>
												<Fragment />
											</Link>
										),
									}
								)
							}
						>
							{ shippingClasses.map( ( { slug, name } ) => (
								<option key={ slug } value={ slug }>
									{ name }
								</option>
							) ) }
						</SelectControl>
					</div>

					<div className="wp-block-column"></div>
				</div>
			) }
		</div>
	);
}
