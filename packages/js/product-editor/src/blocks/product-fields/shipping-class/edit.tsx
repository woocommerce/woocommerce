/**
 * External dependencies
 */
import { useWooBlockProps } from '@woocommerce/block-templates';
import { Link } from '@woocommerce/components';
import {
	experimentalProductShippingClassesStore,
	ProductShippingClass,
	PartialProduct,
} from '@woocommerce/data';
import { getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';
import { BaseControl, SelectControl } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	Fragment,
	createElement,
	createInterpolateElement,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { ShippingClassBlockAttributes } from './types';
import { AddNewShippingClassModal } from '../../../components';
import { ADD_NEW_SHIPPING_CLASS_OPTION_VALUE } from '../../../constants';
import { ProductEditorBlockEditProps } from '../../../types';

type ServerErrorResponse = {
	code: string;
};

type Select = {
	label: string;
	value: string;
};

export const DEFAULT_SHIPPING_CLASS_OPTIONS: Array< Select > = [
	{ value: '', label: __( 'No shipping class', 'woocommerce' ) },
	{
		value: ADD_NEW_SHIPPING_CLASS_OPTION_VALUE,
		label: __( 'Add new shipping class', 'woocommerce' ),
	},
];

function mapShippingClassToSelectOption(
	shippingClasses: ProductShippingClass[]
): Array< Select > {
	return shippingClasses.map( ( { slug, name } ) => ( {
		value: slug,
		label: name,
	} ) );
}

/*
 * Query to fetch shipping classes.
 */
const shippingClassRequestQuery: Partial< ProductShippingClass > = {};

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
	context: { postType, isInSelectedTab },
}: ProductEditorBlockEditProps< ShippingClassBlockAttributes > ) {
	const [ showShippingClassModal, setShowShippingClassModal ] =
		useState( false );

	const blockProps = useWooBlockProps( attributes );

	const { createProductShippingClass } = useDispatch(
		experimentalProductShippingClassesStore
	);

	const { createErrorNotice } = useDispatch( 'core/notices' );

	const [ categories ] = useEntityProp< PartialProduct[ 'categories' ] >(
		'postType',
		postType,
		'categories'
	);
	const [ shippingClass, setShippingClass ] = useEntityProp< string >(
		'postType',
		postType,
		'shipping_class'
	);
	const [ virtual ] = useEntityProp< boolean >(
		'postType',
		postType,
		'virtual'
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

	const { shippingClasses } = useSelect(
		( select ) => {
			const { getProductShippingClasses } = select(
				experimentalProductShippingClassesStore
			);
			return {
				shippingClasses:
					( isInSelectedTab &&
						getProductShippingClasses(
							shippingClassRequestQuery
						) ) ||
					[],
			};
		},
		[ isInSelectedTab ]
	);

	const shippingClassControlId = useInstanceId(
		BaseControl,
		'wp-block-woocommerce-product-shipping-class-field'
	) as string;

	return (
		<div { ...blockProps }>
			<div className="wp-block-columns">
				<div className="wp-block-column">
					<SelectControl
						id={ shippingClassControlId }
						name="shipping_class"
						value={ shippingClass }
						onChange={ ( value: string ) => {
							if (
								value === ADD_NEW_SHIPPING_CLASS_OPTION_VALUE
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
						disabled={ attributes.disabled || virtual }
						help={ createInterpolateElement(
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
						) }
					/>
				</div>

				<div className="wp-block-column"></div>
			</div>

			{ showShippingClassModal && (
				<AddNewShippingClassModal
					shippingClass={ extractDefaultShippingClassFromProduct(
						categories,
						shippingClasses
					) }
					onAdd={ ( shippingClassValues ) =>
						createProductShippingClass( shippingClassValues, {
							optimisticQueryUpdate: shippingClassRequestQuery,
						} )
							.then( ( value: ProductShippingClass ) => {
								recordEvent(
									'product_new_shipping_class_modal_add_button_click'
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
