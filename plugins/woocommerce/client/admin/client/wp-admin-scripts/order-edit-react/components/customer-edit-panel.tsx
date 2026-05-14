/**
 * Customer edit drawer — slide-in side panel that combines email, shipping
 * address, and billing address into a single form. Replaces the per-section
 * modals (EmailEditModal, AddressEditModal).
 *
 * Visual reference: Figma node TyljjRqRSniHQl4TyCXoMV / 22-29840.
 *
 * Pattern notes:
 *  - Fixed-position drawer pinned to the right edge of the viewport.
 *  - No backdrop overlay — the page stays visible behind the drawer. Closes
 *    only via the X button, the Cancel button, or the Esc key (no
 *    click-outside-to-close, matching the WP block editor sidebar pattern).
 *  - Sticky header (title + close) and footer (Cancel + Save) with the form
 *    body scrolling between them.
 */

import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Notice,
	TextControl,
	SelectControl,
	ComboboxControl,
	Flex,
	FlexItem,
	__experimentalHeading as Heading,
} from '@wordpress/components';
import { close as closeIcon } from '@wordpress/icons';
import { useOrder } from '../data/order-context';
import {
	updateOrder,
	describeError,
	searchCustomers,
	type CustomerSearchResult,
} from '../data/api';
import type { OrderAddress } from '../data/types';

/** Value used to represent "guest checkout" in the ComboboxControl. WC
 * uses customer_id=0 for guests; we serialize that as the string "0" in
 * the combobox value space. */
const GUEST_CUSTOMER_VALUE = '0';

interface CustomerEditPanelProps {
	onClose: () => void;
}

// Curated short list of common WooCommerce countries (ISO-3166-1 alpha-2).
// Future: fetch the full ~250-entry list from `/wc/v3/data/countries`.
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

/** Build country options including the address's stored country as a one-off
 * entry if it's outside the curated list. Prevents a stored country from
 * silently disappearing on edit. */
function countryOptionsFor( country: string ) {
	if ( COUNTRY_OPTIONS.some( ( o ) => o.value === country ) ) {
		return COUNTRY_OPTIONS;
	}
	return [ ...COUNTRY_OPTIONS, { label: country, value: country } ];
}

export function CustomerEditPanel( { onClose }: CustomerEditPanelProps ) {
	const { order, setOrder } = useOrder();

	// Email lives on billing.email in v3 — kept in its own piece of state so
	// the UI can present it as a top-level field above shipping/billing.
	const [ email, setEmail ] = useState( order?.billing.email || '' );

	// Shipping & billing addresses as whole objects, with a field-level setter.
	const [ shipping, setShipping ] = useState< OrderAddress >( {
		...( order?.shipping || {} ),
	} );
	const [ billing, setBilling ] = useState< OrderAddress >( {
		...( order?.billing || {} ),
	} );

	// Customer picker state. The dropdown is wired to the order's
	// `customer_id` (0 = guest checkout). We seed the options list with
	// the current customer + a Guest option so the dropdown is meaningful
	// before the user types anything.
	const initialCustomerId = String( order?.customer_id ?? 0 );
	const initialCustomerLabel =
		order?.customer_id && order.customer_id > 0
			? labelForOrderCustomer( order )
			: __( 'Guest checkout', 'woocommerce' );

	const [ customerId, setCustomerId ] = useState( initialCustomerId );
	const [ customerSearch, setCustomerSearch ] = useState( '' );
	const [ customerOptions, setCustomerOptions ] = useState<
		Array< { value: string; label: string } >
	>( () => {
		const opts = [
			{ value: GUEST_CUSTOMER_VALUE, label: __( 'Guest checkout', 'woocommerce' ) },
		];
		if ( initialCustomerId !== GUEST_CUSTOMER_VALUE ) {
			opts.push( { value: initialCustomerId, label: initialCustomerLabel } );
		}
		return opts;
	} );
	// Most recent REST results, keyed by customer id, so we can pull a
	// selected customer's full record (billing/shipping) for the
	// "Apply customer's saved address" button without an extra round trip.
	const [ knownCustomers, setKnownCustomers ] = useState<
		Record< string, CustomerSearchResult >
	>( {} );
	const [ applyingAddress, setApplyingAddress ] = useState( false );

	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	// Esc closes the drawer (only when not mid-save so we don't lose work).
	useEffect( () => {
		const onKey = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' && ! saving ) {
				onClose();
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ onClose, saving ] );

	// Lock background page scroll while the drawer is open so the user
	// only scrolls inside the drawer body. Restored on unmount.
	useEffect( () => {
		document.body.classList.add( 'wc-react-order-edit__body-scroll-locked' );
		return () => {
			document.body.classList.remove(
				'wc-react-order-edit__body-scroll-locked'
			);
		};
	}, [] );

	// Debounced REST search for the customer picker. Cancels in-flight
	// updates if the user keeps typing, and merges results into the
	// known-customers cache so a later "Apply address" doesn't need a
	// second fetch.
	useEffect( () => {
		const query = customerSearch.trim();
		if ( query.length < 2 ) {
			return;
		}
		let cancelled = false;
		const timer = window.setTimeout( async () => {
			try {
				const results = await searchCustomers( query );
				if ( cancelled ) {
					return;
				}
				setKnownCustomers( ( prev ) => {
					const next = { ...prev };
					results.forEach( ( c ) => {
						next[ String( c.id ) ] = c;
					} );
					return next;
				} );
				setCustomerOptions( ( prev ) => {
					const base = [
						{ value: GUEST_CUSTOMER_VALUE, label: __( 'Guest checkout', 'woocommerce' ) },
					];
					// Keep the current selection visible even if it's not
					// in the latest results, so the dropdown never appears
					// to clear its own selection.
					if (
						customerId !== GUEST_CUSTOMER_VALUE &&
						! results.some( ( c ) => String( c.id ) === customerId )
					) {
						const existing = prev.find( ( o ) => o.value === customerId );
						if ( existing ) {
							base.push( existing );
						}
					}
					results.forEach( ( c ) => {
						base.push( {
							value: String( c.id ),
							label: labelForCustomerResult( c ),
						} );
					} );
					return base;
				} );
			} catch {
				// Silent — surface only when the user tries to Save.
			}
		}, 300 );
		return () => {
			cancelled = true;
			window.clearTimeout( timer );
		};
	}, [ customerSearch, customerId ] );

	const handleApplyCustomerAddress = () => {
		const c = knownCustomers[ customerId ];
		if ( ! c ) {
			return;
		}
		setApplyingAddress( true );
		if ( c.billing ) {
			setBilling( { ...c.billing } as OrderAddress );
			if ( c.billing.email ) {
				setEmail( c.billing.email );
			}
		}
		if ( c.shipping ) {
			setShipping( { ...c.shipping } as OrderAddress );
		}
		setApplyingAddress( false );
	};

	if ( ! order ) {
		return null;
	}

	const updateShipping = ( field: keyof OrderAddress ) => ( value: string ) =>
		setShipping( ( prev ) => ( { ...prev, [ field ]: value } ) );

	const updateBilling = ( field: keyof OrderAddress ) => ( value: string ) =>
		setBilling( ( prev ) => ( { ...prev, [ field ]: value } ) );

	const copyShippingToBilling = () => {
		setBilling( ( prev ) => ( {
			...prev,
			first_name: shipping.first_name,
			last_name: shipping.last_name,
			company: shipping.company,
			address_1: shipping.address_1,
			address_2: shipping.address_2,
			city: shipping.city,
			state: shipping.state,
			postcode: shipping.postcode,
			country: shipping.country,
			phone: shipping.phone,
		} ) );
	};

	const handleSave = async () => {
		setSaving( true );
		setError( null );
		try {
			const updated = await updateOrder( order.id, {
				customer_id: Number( customerId ),
				billing: { ...billing, email },
				shipping,
			} );
			setOrder( updated );
			window.dispatchEvent(
				new CustomEvent( 'wc-react-order-edit:snackbar', {
					detail: {
						message: __( 'Customer details updated', 'woocommerce' ),
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
		<>
			<div
				className="wc-react-order-edit__drawer-backdrop"
				onClick={ saving ? undefined : onClose }
				aria-hidden="true"
			/>
			<aside
				className="wc-react-order-edit__drawer"
				role="dialog"
				aria-modal="true"
				aria-labelledby="wc-react-order-edit-drawer-title"
			>
			<header className="wc-react-order-edit__drawer-header">
				<h2
					id="wc-react-order-edit-drawer-title"
					className="wc-react-order-edit__drawer-title"
				>
					{ __( 'Edit customer details', 'woocommerce' ) }
				</h2>
				<Button
					icon={ closeIcon }
					label={ __( 'Close', 'woocommerce' ) }
					onClick={ onClose }
					disabled={ saving }
				/>
			</header>

			<div className="wc-react-order-edit__drawer-body">
				{ error && (
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				) }

				<section className="wc-react-order-edit__drawer-section">
					<div className="wc-react-order-edit__drawer-section-header">
						<Heading
							level={ 5 }
							className="wc-react-order-edit__drawer-section-title"
						>
							{ __( 'Customer', 'woocommerce' ) }
						</Heading>
					</div>
					<ComboboxControl
						label={ __( 'Customer', 'woocommerce' ) }
						hideLabelFromVision
						value={ customerId }
						options={ customerOptions }
						onChange={ ( v ) => setCustomerId( v ?? GUEST_CUSTOMER_VALUE ) }
						onFilterValueChange={ setCustomerSearch }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<div className="wc-react-order-edit__customer-picker-actions">
						{ customerId !== GUEST_CUSTOMER_VALUE &&
							customerId !== initialCustomerId &&
							knownCustomers[ customerId ] && (
								<Button
									variant="link"
									onClick={ handleApplyCustomerAddress }
									disabled={ applyingAddress }
								>
									{ __(
										"Apply customer's saved address",
										'woocommerce'
									) }
								</Button>
							) }
						{ customerId !== GUEST_CUSTOMER_VALUE && (
							<a
								className="wc-react-order-edit__text-link"
								href={ `/wp-admin/admin.php?page=wc-orders&_customer_user=${ customerId }` }
							>
								{ __( 'View other orders →', 'woocommerce' ) }
							</a>
						) }
					</div>
				</section>

				<section className="wc-react-order-edit__drawer-section">
					<TextControl
						label={ __( 'Email', 'woocommerce' ) }
						type="email"
						value={ email }
						onChange={ setEmail }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</section>

				<AddressFieldset
					heading={ __( 'Shipping', 'woocommerce' ) }
					address={ shipping }
					onFieldChange={ updateShipping }
				/>

				<AddressFieldset
					heading={ __( 'Billing', 'woocommerce' ) }
					address={ billing }
					onFieldChange={ updateBilling }
					action={
						<Button
							variant="link"
							onClick={ copyShippingToBilling }
							disabled={ saving }
						>
							{ __( 'Copy from shipping', 'woocommerce' ) }
						</Button>
					}
				/>
			</div>

			<footer className="wc-react-order-edit__drawer-footer">
				<Button
					variant="tertiary"
					onClick={ onClose }
					disabled={ saving }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ handleSave }
					isBusy={ saving }
					disabled={ saving }
				>
					{ saving
						? __( 'Saving…', 'woocommerce' )
						: __( 'Save', 'woocommerce' ) }
				</Button>
			</footer>
			</aside>
		</>
	);
}

interface AddressFieldsetProps {
	heading: string;
	address: OrderAddress;
	onFieldChange: ( field: keyof OrderAddress ) => ( value: string ) => void;
	action?: React.ReactNode;
}

function AddressFieldset( {
	heading,
	address,
	onFieldChange,
	action,
}: AddressFieldsetProps ) {
	return (
		<section className="wc-react-order-edit__drawer-section">
			<div className="wc-react-order-edit__drawer-section-header">
				<Heading level={ 5 } className="wc-react-order-edit__drawer-section-title">
					{ heading }
				</Heading>
				{ action }
			</div>

			<Flex gap={ 3 } wrap>
				<FlexItem isBlock>
					<TextControl
						label={ __( 'First name', 'woocommerce' ) }
						value={ address.first_name || '' }
						onChange={ onFieldChange( 'first_name' ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
				<FlexItem isBlock>
					<TextControl
						label={ __( 'Last name', 'woocommerce' ) }
						value={ address.last_name || '' }
						onChange={ onFieldChange( 'last_name' ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
			</Flex>
			<TextControl
				label={ __( 'Company', 'woocommerce' ) }
				value={ address.company || '' }
				onChange={ onFieldChange( 'company' ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<TextControl
				label={ __( 'Address line 1', 'woocommerce' ) }
				value={ address.address_1 || '' }
				onChange={ onFieldChange( 'address_1' ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<TextControl
				label={ __( 'Address line 2', 'woocommerce' ) }
				value={ address.address_2 || '' }
				onChange={ onFieldChange( 'address_2' ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<Flex gap={ 3 } wrap>
				<FlexItem isBlock>
					<TextControl
						label={ __( 'City', 'woocommerce' ) }
						value={ address.city || '' }
						onChange={ onFieldChange( 'city' ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
				<FlexItem isBlock>
					<TextControl
						label={ __( 'State', 'woocommerce' ) }
						value={ address.state || '' }
						onChange={ onFieldChange( 'state' ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
			</Flex>
			<Flex gap={ 3 } wrap>
				<FlexItem isBlock>
					<TextControl
						label={ __( 'Postcode', 'woocommerce' ) }
						value={ address.postcode || '' }
						onChange={ onFieldChange( 'postcode' ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
				<FlexItem isBlock>
					<SelectControl
						label={ __( 'Country', 'woocommerce' ) }
						value={ address.country || '' }
						onChange={ onFieldChange( 'country' ) }
						options={ countryOptionsFor( address.country || '' ) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</FlexItem>
			</Flex>
			<TextControl
				label={ __( 'Phone', 'woocommerce' ) }
				value={ address.phone || '' }
				onChange={ onFieldChange( 'phone' ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
		</section>
	);
}

/** Format a customer record from the REST `/customers` search response
 * into a single-line label for the ComboboxControl. */
function labelForCustomerResult( c: CustomerSearchResult ): string {
	const name = [ c.first_name, c.last_name ].filter( Boolean ).join( ' ' ).trim();
	const email = c.email || c.billing?.email || '';
	if ( name && email ) {
		return `${ name } (${ email })`;
	}
	return name || email || c.username || `#${ c.id }`;
}

/** Build a label for the order's CURRENT customer (used as the dropdown's
 * initial selection before the user searches). Pulls from billing fields
 * since we may not have hit the REST endpoint yet. */
function labelForOrderCustomer( order: {
	customer_id?: number;
	billing: { first_name?: string; last_name?: string; email?: string };
} ): string {
	const name = [ order.billing.first_name, order.billing.last_name ]
		.filter( Boolean )
		.join( ' ' )
		.trim();
	const email = order.billing.email || '';
	if ( name && email ) {
		return `${ name } (${ email })`;
	}
	return name || email || `#${ order.customer_id }`;
}
