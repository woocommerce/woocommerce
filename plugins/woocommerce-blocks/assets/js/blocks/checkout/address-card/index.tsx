/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	type CartShippingAddress,
	type CartBillingAddress,
	type CountryData,
	objectHasProp,
	isString,
} from '@woocommerce/types';
import { FormFieldsConfig, getSetting } from '@woocommerce/settings';
import { formatAddress } from '@woocommerce/blocks/checkout/utils';

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
	const countryData = getSetting< Record< string, CountryData > >(
		'countryData',
		{}
	);

	let formatToUse = getSetting< string >(
		'defaultAddressFormat',
		'{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{postcode}\n{country}'
	);

	if (
		objectHasProp( countryData, address?.country ) &&
		objectHasProp( countryData[ address.country ], 'format' ) &&
		isString( countryData[ address.country ].format )
	) {
		// `as string` is fine here because we check if it's a string above.
		formatToUse = countryData[ address.country ].format as string;
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
