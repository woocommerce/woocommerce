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
import { getSetting } from '@woocommerce/settings';
import { formatAddress } from '@woocommerce/blocks/checkout/utils';
import { Button } from '@ariakit/react';
import { decodeEntities } from '@wordpress/html-entities';
import clsx from 'clsx';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './style.scss';

type Props = {
	address: CartShippingAddress | CartBillingAddress;
	onEdit: () => void;
	target: string;
	isExpanded: boolean;
};

const getFormattedAddress = ( address: Props[ 'address' ] ) => {
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

	return formatAddress( address, formatToUse );
};

const AddressCard = ( { address, onEdit, target, isExpanded }: Props ) => {
	const { name: formattedName, address: formattedAddress } =
		getFormattedAddress( address );

	const label =
		target === 'shipping'
			? __( 'Edit shipping address', 'woocommerce' )
			: __( 'Edit billing address', 'woocommerce' );

	const fullAddress = useMemo( () => {
		return [ ...formattedAddress, address.phone ]
			.filter( ( field ) => !! field )
			.map( ( field ) => decodeEntities( field ) )
			.join( ', ' );
	}, [ formattedAddress, address.phone ] );

	return (
		<div className="wc-block-components-address-card">
			<address>
				<span
					className={ clsx(
						'wc-block-components-address-card__address-section',
						'wc-block-components-address-card__address-section--primary'
					) }
				>
					{ decodeEntities( formattedName ) }
				</span>
				<span
					className={ clsx(
						'wc-block-components-address-card__address-section',
						'wc-block-components-address-card__address-section--secondary'
					) }
				>
					{ fullAddress }
				</span>
			</address>
			{ onEdit && (
				<Button
					render={ <span /> }
					className="wc-block-components-address-card__edit"
					aria-controls={ target }
					aria-expanded={ isExpanded }
					aria-label={ label }
					onClick={ ( e ) => {
						e.preventDefault();
						onEdit();
					} }
					type="button"
				>
					{ __( 'Edit', 'woocommerce' ) }
				</Button>
			) }
		</div>
	);
};

export default AddressCard;
