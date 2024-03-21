/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	ALLOWED_COUNTRIES,
	ADDRESS_FORM_KEYS,
} from '@woocommerce/block-settings';
import type {
	CartShippingAddress,
	CartBillingAddress,
} from '@woocommerce/types';
import { FormFieldsConfig } from '@woocommerce/settings';
import prepareFormFields from '@woocommerce/base-components/cart-checkout/form/prepare-form-fields';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';

const AddressCard = ( {
	address,
	onEdit,
	target,
	fieldConfig,
}: {
	address: CartShippingAddress | CartBillingAddress;
	onEdit: () => void;
	target: string;
	fieldConfig: FormFieldsConfig;
} ): JSX.Element | null => {
	// Use the form preparation logic here to determine display order for the address card.
	const addressFields = useMemo(
		() =>
			prepareFormFields(
				ADDRESS_FORM_KEYS,
				fieldConfig,
				address?.country
			),
		[ address?.country, fieldConfig ]
	);

	const orderedName = addressFields
		.filter(
			( field ) =>
				field?.key === 'last_name' || field?.key === 'first_name'
		)
		.sort( ( field1, field2 ) => field1?.index - field2?.index )
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore - Ignoring because we know the keys are valid address keys (and if they're not we have optional chaining anyway).
		.map( ( field ) => address?.[ field.key ] )
		.join( ' ' );

	return (
		<div className="wc-block-components-address-card">
			<address>
				<span className="wc-block-components-address-card__address-section">
					{ orderedName }
				</span>
				<div className="wc-block-components-address-card__address-section">
					{ [
						address.address_1,
						! fieldConfig.address_2.hidden && address.address_2,
						address.city,
						address.state,
						address.postcode,
						ALLOWED_COUNTRIES[ address.country ]
							? ALLOWED_COUNTRIES[ address.country ]
							: address.country,
					]
						.filter( ( field ) => !! field )
						.map( ( field, index ) => (
							<span key={ `address-` + index }>{ field }</span>
						) ) }
				</div>
				{ address.phone && ! fieldConfig.phone.hidden ? (
					<div
						key={ `address-phone` }
						className="wc-block-components-address-card__address-section"
					>
						{ address.phone }
					</div>
				) : (
					''
				) }
			</address>
			{ onEdit && (
				<a
					role="button"
					href={ '#' + target }
					className="wc-block-components-address-card__edit"
					aria-label={ __( 'Edit address', 'woocommerce' ) }
					onClick={ ( e ) => {
						onEdit();
						e.preventDefault();
					} }
				>
					{ __( 'Edit', 'woocommerce' ) }
				</a>
			) }
		</div>
	);
};

export default AddressCard;
