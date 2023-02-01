/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Link, useFormContext2, Spinner } from '@woocommerce/components';
import {
	EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME,
	PartialProduct,
	ProductShippingClass,
} from '@woocommerce/data';
import interpolateComponents from '@automattic/interpolate-components';
import { recordEvent } from '@woocommerce/tracks';
import { SelectControl } from '@wordpress/components';
import { useController } from 'react-hook-form';

/**
 * Internal dependencies
 */
import {
	ADD_NEW_SHIPPING_CLASS_OPTION_VALUE,
	UNCATEGORIZED_CATEGORY_SLUG,
} from '../../constants';
import { ADMIN_URL } from '~/utils/admin-settings';
import { AddNewShippingClassModal } from '../../shared/add-new-shipping-class-modal';
import { ProductShippingSectionPropsType } from './index';

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

type ServerErrorResponse = {
	code: string;
};

// eslint-disable-next-line jsdoc/check-line-alignment
/**
 * This extracts a shipping class from the product categories. Using
 * the first category different to `Uncategorized` and check if the
 * category was not added to the shipping class list
 *
 * @see https://github.com/woocommerce/woocommerce/issues/34657
 * @see https://github.com/woocommerce/woocommerce/issues/35037
 * @param product The product
 * @param shippingClasses The shipping classes
 * @return The default shipping class
 */
function extractDefaultShippingClassFromProduct(
	product?: PartialProduct,
	shippingClasses?: ProductShippingClass[]
): Partial< ProductShippingClass > | undefined {
	const category = product?.categories?.find(
		( { slug } ) => slug !== UNCATEGORIZED_CATEGORY_SLUG
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

export const ShippingClassField: React.FC<
	ProductShippingSectionPropsType
> = ( { product } ) => {
	const { setValue, control } = useFormContext2< PartialProduct >();
	const [ showShippingClassModal, setShowShippingClassModal ] =
		useState( false );

	const { field } = useController( { name: 'shipping_class', control } );

	const { shippingClasses, hasResolvedShippingClasses } = useSelect(
		( select ) => {
			const { getProductShippingClasses, hasFinishedResolution } = select(
				EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME
			);
			return {
				hasResolvedShippingClasses: hasFinishedResolution(
					'getProductShippingClasses'
				),
				shippingClasses:
					getProductShippingClasses< ProductShippingClass[] >(),
			};
		},
		[]
	);

	const { createProductShippingClass, invalidateResolution } = useDispatch(
		EXPERIMENTAL_PRODUCT_SHIPPING_CLASSES_STORE_NAME
	);
	const { createErrorNotice } = useDispatch( 'core/notices' );

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

	return (
		<>
			{ hasResolvedShippingClasses ? (
				<>
					<SelectControl
						label={ __( 'Shipping class', 'woocommerce' ) }
						value={ field.value }
						className="half-width-field"
						onChange={ ( value: string ) => {
							if (
								value === ADD_NEW_SHIPPING_CLASS_OPTION_VALUE
							) {
								setShowShippingClassModal( true );
								return;
							}
							field.onChange( value );
						} }
						options={ [
							...DEFAULT_SHIPPING_CLASS_OPTIONS,
							...mapShippingClassToSelectOption(
								shippingClasses ?? []
							),
						] }
					/>
					<span className="woocommerce-product-form__secondary-text">
						{ interpolateComponents( {
							mixedString: __(
								'Manage shipping classes and rates in {{link}}global settings{{/link}}.',
								'woocommerce'
							),
							components: {
								link: (
									<Link
										href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=shipping&section=classes` }
										target="_blank"
										type="external"
										onClick={ () => {
											recordEvent(
												'product_shipping_global_settings_link_click'
											);
										} }
									>
										<></>
									</Link>
								),
							},
						} ) }
					</span>
				</>
			) : (
				<div className="product-shipping-section__spinner-wrapper">
					<Spinner />
				</div>
			) }
			{ showShippingClassModal && (
				<AddNewShippingClassModal
					shippingClass={ extractDefaultShippingClassFromProduct(
						product,
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
								setValue( 'shipping_class', value.slug );
								return value;
							} )
							.catch( handleShippingClassServerError )
					}
					onCancel={ () => setShowShippingClassModal( false ) }
				/>
			) }
		</>
	);
};
