/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ALLOWED_COUNTRIES } from '@woocommerce/block-settings';
import type {
	CartShippingAddress,
	CartBillingAddress,
} from '@woocommerce/types';
import { FormFieldsConfig } from '@woocommerce/settings';

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
	return (
		<div className="wc-block-components-address-card">
			<address>
				<span className="wc-block-components-address-card__address-section">
					{ address.first_name + ' ' + address.last_name }
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
