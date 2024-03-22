/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	type CartShippingAddress,
	type CartBillingAddress,
	objectHasProp,
} from '@woocommerce/types';
import { FormFieldsConfig, getSetting } from '@woocommerce/settings';
import {
	formatAddress,
	getFormattedCountry,
	getFormattedState,
} from '@woocommerce/blocks/checkout/utils';

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
	const formattedCountry = getFormattedCountry( address );
	const formattedState = getFormattedState( address );
	const addressFormats = getSetting< Record< string, string > >(
		'addressFormats',
		{}
	);
	let formatToUse = 'default';
	if ( objectHasProp( addressFormats, address?.country ) ) {
		formatToUse = addressFormats[ address.country ];
	}
	const { name: formattedName, address: formattedAddress } = formatAddress(
		address,
		formatToUse
	);

	return (
		<div className="wc-block-components-address-card">
			<address>
				<span className="wc-block-components-address-card__address-section">
					{ formattedName }
				</span>
				<div className="wc-block-components-address-card__address-section">
					{ formattedAddress
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
