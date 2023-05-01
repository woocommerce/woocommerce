/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';
import { Link } from '@woocommerce/components';
import {
	EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME,
	ProductShippingClass,
	PartialProduct,
} from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { useBlockProps } from '@wordpress/block-editor';
import { BaseControl, SelectControl } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	Fragment,
	createElement,
	createInterpolateElement,
	useEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ShippingFeeBlockAttributes } from './types';
import { useValidation } from '../../hooks/use-validation';
import { RadioField } from '../../components/radio-field';
import { AddNewShippingClassModal } from '../../components';
import { ADD_NEW_SHIPPING_CLASS_OPTION_VALUE } from '../../constants';

type ServerErrorResponse = {
	code: string;
};

const FOLLOW_CLASS_OPTION_VALUE = 'follow_class';
const FREE_SHIPPING_OPTION_VALUE = 'free_shipping';

export const DEFAULT_SHIPPING_CLASS_OPTIONS: SelectControl.Option[] = [
	{ value: '', label: __( 'No shipping class', 'woocommerce' ) },
	{
		value: ADD_NEW_SHIPPING_CLASS_OPTION_VALUE,
		label: __( 'Add new shipping class', 'woocommerce' ),
	},
];

function mapShippingClassToSelectOption(
	shippingClasses: ProductShippingClass[]
): SelectControl.Option[] {
	return shippingClasses.map( ( { slug, name } ) => ( {
		value: slug,
		label: name,
	} ) );
}

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

function extractDefaultShippingClassFromProduct(
	categories?: PartialProduct[ 'categories' ],
	shippingClasses?: ProductShippingClass[]
): Partial< ProductShippingClass > | undefined {
	const category = categories?.find(
		( { slug } ) => slug !== 'uncategorized'
	);
	if (
		category &&
		! shippingClasses?.some( ( { slug } ) => slug === category.slug )
	) {
		return {
			name: category.name,
			slug: category.slug,
		};
	}
}

export function Edit( {
	attributes,
}: BlockEditProps< ShippingFeeBlockAttributes > ) {
	const { title } = attributes;
	const [ showShippingClassModal, setShowShippingClassModal ] =
		useState( false );

	const blockProps = useBlockProps();

	const [ option, setOption ] = useState< string >(
		FREE_SHIPPING_OPTION_VALUE
	);

	const { createProductShippingClass, invalidateResolution } = useDispatch(
		EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME
	);

	const { createErrorNotice } = useDispatch( 'core/notices' );

	const [ categories ] = useEntityProp< PartialProduct[ 'categories' ] >(
		'postType',
		'product',
		'categories'
	);
	const [ shippingClass, setShippingClass ] = useEntityProp< string >(
		'postType',
		'product',
		'shipping_class'
	);

	function handleShippingClassServerError(
		error: ServerErrorResponse
	): Promise< ProductShippingClass > {
		let message = __(
			'We couldn’t add this shipping class. Try again in a few seconds.',
			'woocommerce'
		);

		if ( error.code === 'term_exists' ) {
			message = __(
				'A shipping class with that slug already exists.',
				'woocommerce'
			);
		}

		createErrorNotice( message, {
			explicitDismiss: true,
		} );

		throw error;
	}

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
		if ( shippingClass ) {
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
							onChange={ ( value: string ) => {
								if (
									value ===
									ADD_NEW_SHIPPING_CLASS_OPTION_VALUE
								) {
									setShowShippingClassModal( true );
									return;
								}
								setShippingClass( value );
							} }
							label={ __( 'Shipping class', 'woocommerce' ) }
							options={ [
								...DEFAULT_SHIPPING_CLASS_OPTIONS,
								...mapShippingClassToSelectOption(
									shippingClasses ?? []
								),
							] }
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
						/>
					</div>

					<div className="wp-block-column"></div>
				</div>
			) }
			{ showShippingClassModal && (
				<AddNewShippingClassModal
					shippingClass={ extractDefaultShippingClassFromProduct(
						categories,
						shippingClasses
					) }
					onAdd={ ( shippingClassValues ) =>
						createProductShippingClass<
							Promise< ProductShippingClass >
						>( shippingClassValues )
							.then( ( value ) => {
								recordEvent(
									'product_new_shipping_class_modal_add_button_click'
								);
								invalidateResolution(
									'getProductShippingClasses'
								);
								setShippingClass( value.slug );
								return value;
							} )
							.catch( handleShippingClassServerError )
					}
					onCancel={ () => setShowShippingClassModal( false ) }
				/>
			) }
		</div>
	);
}
