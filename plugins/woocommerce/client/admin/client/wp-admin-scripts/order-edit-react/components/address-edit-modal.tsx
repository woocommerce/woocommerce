/**
 * Address edit modal — shared between Shipping and Billing.
 *
 * Same field set in both cases (name, address lines, city/state, postcode/country,
 * phone). The `type` prop scopes which order address gets updated.
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Modal,
	Button,
	Notice,
	TextControl,
	SelectControl,
	Flex,
	FlexItem,
} from '@wordpress/components';
import { useOrder } from '../data/order-context';
import { updateOrder, describeError } from '../data/api';
import type { OrderAddress } from '../data/types';

interface AddressEditModalProps {
	type: 'shipping' | 'billing';
	onClose: () => void;
}

// Curated short list of common WooCommerce countries. Stores ISO-3166-1 alpha-2
// codes (matches v3 REST). Replace with `/wc/v3/data/countries` lookup when we
// need the full ~250-entry list.
const COUNTRY_OPTIONS: Array< { label: string; value: string } > = [
	{ label: __( '— Select a country —', 'woocommerce' ), value: '' },
	{ label: __( 'Australia', 'woocommerce' ), value: 'AU' },
	{ label: __( 'Brazil', 'woocommerce' ), value: 'BR' },
	{ label: __( 'Canada', 'woocommerce' ), value: 'CA' },
	{ label: __( 'France', 'woocommerce' ), value: 'FR' },
	{ label: __( 'Germany', 'woocommerce' ), value: 'DE' },
	{ label: __( 'India', 'woocommerce' ), value: 'IN' },
	{ label: __( 'Italy', 'woocommerce' ), value: 'IT' },
	{ label: __( 'Japan', 'woocommerce' ), value: 'JP' },
	{ label: __( 'Mexico', 'woocommerce' ), value: 'MX' },
	{ label: __( 'Netherlands', 'woocommerce' ), value: 'NL' },
	{ label: __( 'Portugal', 'woocommerce' ), value: 'PT' },
	{ label: __( 'Spain', 'woocommerce' ), value: 'ES' },
	{ label: __( 'United Kingdom', 'woocommerce' ), value: 'GB' },
	{ label: __( 'United States', 'woocommerce' ), value: 'US' },
];

export function AddressEditModal( { type, onClose }: AddressEditModalProps ) {
	const { order, setOrder } = useOrder();
	const source = order ? order[ type ] : ( {} as OrderAddress );

	// The Figma uses a single "Name" field, but v3 stores first_name / last_name
	// separately. Combine for display, split on the first space when saving.
	const initialName = [ source.first_name, source.last_name ]
		.filter( Boolean )
		.join( ' ' );

	const [ name, setName ] = useState( initialName );
	const [ address1, setAddress1 ] = useState( source.address_1 || '' );
	const [ address2, setAddress2 ] = useState( source.address_2 || '' );
	const [ city, setCity ] = useState( source.city || '' );
	const [ state, setState ] = useState( source.state || '' );
	const [ postcode, setPostcode ] = useState( source.postcode || '' );
	const [ country, setCountry ] = useState( source.country || '' );
	const [ phone, setPhone ] = useState( source.phone || '' );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	if ( ! order ) {
		return null;
	}

	const title =
		type === 'shipping'
			? __( 'Edit shipping information', 'woocommerce' )
			: __( 'Edit billing information', 'woocommerce' );

	const handleSave = async () => {
		setSaving( true );
		setError( null );

		// Split "First Last" into first_name + last_name on the first space.
		const trimmed = name.trim();
		const firstSpace = trimmed.indexOf( ' ' );
		const firstName = firstSpace === -1 ? trimmed : trimmed.slice( 0, firstSpace );
		const lastName = firstSpace === -1 ? '' : trimmed.slice( firstSpace + 1 ).trim();

		const nextAddress: OrderAddress = {
			...source,
			first_name: firstName,
			last_name: lastName,
			address_1: address1,
			address_2: address2,
			city,
			state,
			postcode,
			country,
			phone,
		};

		try {
			const updated = await updateOrder( order.id, { [ type ]: nextAddress } );
			setOrder( updated );
			window.dispatchEvent(
				new CustomEvent( 'wc-react-order-edit:snackbar', {
					detail: {
						message:
							type === 'shipping'
								? __( 'Shipping address updated', 'woocommerce' )
								: __( 'Billing address updated', 'woocommerce' ),
					},
				} )
			);
			onClose();
		} catch ( err ) {
			setError( describeError( err ) );
		} finally {
			setSaving( false );
		}
	};

	return (
		<Modal
			title={ title }
			onRequestClose={ saving ? () => undefined : onClose }
			className="wc-react-order-edit__address-modal"
			shouldCloseOnClickOutside={ ! saving }
			shouldCloseOnEsc={ ! saving }
		>
			<div className="wc-react-order-edit__modal-form">
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }

				<TextControl
					label={ __( 'Name', 'woocommerce' ) }
					value={ name }
					onChange={ setName }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				<TextControl
					label={ __( 'Address line 1', 'woocommerce' ) }
					value={ address1 }
					onChange={ setAddress1 }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				<TextControl
					label={ __( 'Address line 2', 'woocommerce' ) }
					value={ address2 }
					onChange={ setAddress2 }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
				<Flex gap={ 3 } wrap>
				<FlexItem isBlock>
					<TextControl
						label={ __( 'City', 'woocommerce' ) }
						value={ city }
						onChange={ setCity }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
				<FlexItem isBlock>
					<TextControl
						label={ __( 'State', 'woocommerce' ) }
						value={ state }
						onChange={ setState }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
			</Flex>
			<Flex gap={ 3 } wrap>
				<FlexItem isBlock>
					<TextControl
						label={ __( 'Postcode', 'woocommerce' ) }
						value={ postcode }
						onChange={ setPostcode }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
				<FlexItem isBlock>
					<SelectControl
						label={ __( 'Country', 'woocommerce' ) }
						value={ country }
						onChange={ setCountry }
						options={
							// If the order's stored country isn't in our short list,
							// surface it as a one-off option so it doesn't get dropped.
							COUNTRY_OPTIONS.some( ( o ) => o.value === country )
								? COUNTRY_OPTIONS
								: [
										...COUNTRY_OPTIONS,
										{ label: country, value: country },
								  ]
						}
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
			</Flex>
			<TextControl
				label={ __( 'Phone number', 'woocommerce' ) }
				value={ phone }
				onChange={ setPhone }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>

			<div className="wc-react-order-edit__modal-actions">
				<Button
					variant="tertiary"
					size="compact"
					onClick={ onClose }
					disabled={ saving }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					size="compact"
					onClick={ handleSave }
					isBusy={ saving }
					disabled={ saving }
				>
					{ saving ? __( 'Saving…', 'woocommerce' ) : __( 'Save', 'woocommerce' ) }
				</Button>
			</div>
			</div>
		</Modal>
	);
}
