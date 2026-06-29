/**
 * External dependencies
 */
import { Button, SnackbarList } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';
import { useEffect, useMemo, useReducer, useState } from '@wordpress/element';
import type { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import Hub from './prototype/components/Hub.jsx';
import RenameModal from './prototype/components/RenameModal.jsx';
import ShippingSetupFullPage from './prototype/components/ShippingSetupFullPage.jsx';
import {
	makeInitialTreeValue,
	zonesFromTreeValue,
} from './prototype/components/ShippingSetupShared.jsx';
import ZoneEditPanel from './prototype/components/ZoneEditPanel.jsx';
import { ShippingProviderChoice } from './prototype/components/EmptyState.jsx';
import {
	initialCarriers,
	initialProductGroups,
} from './prototype/data/mockData.js';
import './style.scss';
import './prototype/source-styles.css';
import './source-host-overrides.scss';

type ShippingScreen = 'providers' | 'setup' | 'hub';
type ShippingSection = 'zones' | 'live' | 'packages' | 'settings';

type ShippingZone = {
	id: string;
	name: string;
	regions: string;
	methods: Record< string, unknown >;
};

type ZonesAction =
	| { type: 'SET_ZONES'; zones: ShippingZone[] }
	| { type: 'UPDATE_ZONE'; id: string; updates: Partial< ShippingZone > }
	| { type: 'RENAME_ZONE'; id: string; name: string }
	| { type: 'DELETE_ZONE'; id: string }
	| { type: 'ADD_ZONE'; zone: ShippingZone };

const shippingSections: Record< ShippingSection, string > = {
	zones: 'Zones & rates',
	live: 'Live rate settings',
	packages: 'Package management',
	settings: 'Advanced settings',
};

const shippingHeaderDescriptions: Record< ShippingSection, string > = {
	zones: 'Manage zones, rates, labels, packages, and your Woo Shipping connection.',
	live: 'Manage live rate services and how they appear inside delivery options.',
	packages:
		'Manage ship-from addresses, label defaults, and package templates.',
	settings:
		'Manage advanced shipping behavior, checkout defaults, and product groups.',
};

const settingsTabs = [
	'General',
	'Products',
	'Shipping',
	'Payments',
	'Accounts & Privacy',
	'Emails',
	'Integration',
	'Advanced',
	'Multi-currency',
];

const SHIPPING_NATIVE_STORAGE_KEY = 'woocommerce-shipping-native-spike-zones';

const PrototypeShippingProviderChoice =
	ShippingProviderChoice as unknown as ComponentType<
		Record< string, unknown >
	>;
const PrototypeShippingSetupFullPage =
	ShippingSetupFullPage as unknown as ComponentType<
		Record< string, unknown >
	>;
const PrototypeHub = Hub as unknown as ComponentType<
	Record< string, unknown >
>;
const PrototypeZoneEditPanel = ZoneEditPanel as unknown as ComponentType<
	Record< string, unknown >
>;
const PrototypeRenameModal = RenameModal as unknown as ComponentType<
	Record< string, unknown >
>;

function zonesReducer(
	state: ShippingZone[],
	action: ZonesAction
): ShippingZone[] {
	switch ( action.type ) {
		case 'SET_ZONES':
			return action.zones;
		case 'UPDATE_ZONE':
			return state.map( ( zone ) =>
				zone.id === action.id ? { ...zone, ...action.updates } : zone
			);
		case 'RENAME_ZONE':
			return state.map( ( zone ) =>
				zone.id === action.id ? { ...zone, name: action.name } : zone
			);
		case 'DELETE_ZONE':
			return state.filter( ( zone ) => zone.id !== action.id );
		case 'ADD_ZONE':
			return [ ...state, action.zone ];
		default:
			return state;
	}
}

function ShippingPrototypeHeader( {
	activeSection,
	isSetup,
	onExitSetup,
	onProviderChoice,
	onSectionChange,
}: {
	activeSection: ShippingSection;
	isSetup: boolean;
	onExitSetup: () => void;
	onProviderChoice: () => void;
	onSectionChange: ( section: ShippingSection ) => void;
} ) {
	const description = isSetup
		? shippingHeaderDescriptions.zones
		: shippingHeaderDescriptions[ activeSection ];

	return (
		<header
			className={ `shipping-native-prototype-header${
				isSetup ? ' is-setup' : ''
			}` }
		>
			<div className="shipping-native-prototype-header__main">
				<div className="wc-breadcrumb" aria-label="Breadcrumb">
					<button
						type="button"
						className="wc-breadcrumb-link wc-breadcrumb-button"
						onClick={ onProviderChoice }
					>
						Shipping
					</button>
					<span className="wc-breadcrumb-separator">/</span>
					<span className="wc-breadcrumb-current">Woo Shipping</span>
					<span
						className={
							isSetup
								? 'page-status-pill page-status-pill-warning'
								: 'page-status-pill'
						}
					>
						{ isSetup ? 'Not set up' : 'Active' }
					</span>
				</div>
				<p className="shipping-native-prototype-header__description">
					{ description }
				</p>
			</div>
			<div className="shipping-native-prototype-header__actions">
				{ isSetup ? (
					<Button
						variant="tertiary"
						onClick={ onExitSetup }
						__next40pxDefaultSize
					>
						Exit setup
					</Button>
				) : (
					<Button
						variant="tertiary"
						icon={ moreVertical }
						label="More Woo Shipping actions"
						__next40pxDefaultSize
					/>
				) }
			</div>
			{ ! isSetup && (
				<nav
					className="shipping-section-tabs"
					aria-label="Shipping sections"
				>
					{ Object.entries( shippingSections ).map(
						( [ key, label ] ) => (
							<button
								key={ key }
								type="button"
								className={ `shipping-section-tab${
									activeSection === key ? ' is-active' : ''
								}` }
								onClick={ () =>
									onSectionChange( key as ShippingSection )
								}
							>
								{ label }
							</button>
						)
					) }
				</nav>
			) }
		</header>
	);
}

function ShippingSettingsHeader() {
	return (
		<header className="shipping-native-settings-header">
			<div className="shipping-native-settings-header__title">
				<h1>Settings</h1>
			</div>
			<nav
				className="shipping-native-settings-tabs"
				aria-label="WooCommerce settings sections"
			>
				{ settingsTabs.map( ( tab ) => (
					<button
						key={ tab }
						type="button"
						className={ `shipping-native-settings-tab${
							tab === 'Shipping' ? ' is-active' : ''
						}` }
					>
						{ tab }
					</button>
				) ) }
			</nav>
		</header>
	);
}

function getShippingNativeStateParam() {
	if ( typeof window === 'undefined' ) {
		return null;
	}

	return new URLSearchParams( window.location.search ).get(
		'_shipping_native_state'
	);
}

function getDemoSetupZones(): ShippingZone[] {
	return zonesFromTreeValue( makeInitialTreeValue() ) as ShippingZone[];
}

function readStoredZones(): ShippingZone[] {
	if ( typeof window === 'undefined' ) {
		return [];
	}

	const state = getShippingNativeStateParam();

	if ( state === 'reset' ) {
		window.sessionStorage.removeItem( SHIPPING_NATIVE_STORAGE_KEY );
		return [];
	}

	if ( state === 'post-provider' || state === 'zones' ) {
		return getDemoSetupZones();
	}

	const storedZones = window.sessionStorage.getItem(
		SHIPPING_NATIVE_STORAGE_KEY
	);

	if ( ! storedZones ) {
		return [];
	}

	try {
		const parsedZones = JSON.parse( storedZones );
		return Array.isArray( parsedZones ) ? parsedZones : [];
	} catch {
		return [];
	}
}

function getInitialScreen(): ShippingScreen {
	return getShippingNativeStateParam() === 'zones' ? 'hub' : 'providers';
}

export const ShippingNativeInlineSetup = () => {
	const [ screen, setScreen ] =
		useState< ShippingScreen >( getInitialScreen );
	const [ activeSection, setActiveSection ] =
		useState< ShippingSection >( 'zones' );
	const [ zones, dispatch ] = useReducer( zonesReducer, readStoredZones() );
	const [ editingZoneId, setEditingZoneId ] = useState< string | null >(
		null
	);
	const [ renamingZoneId, setRenamingZoneId ] = useState< string | null >(
		null
	);
	const [ flash, setFlash ] = useState< {
		id?: string;
		message: string;
	} | null >( null );

	const editingZone = useMemo(
		() => zones.find( ( zone ) => zone.id === editingZoneId ) ?? null,
		[ zones, editingZoneId ]
	);
	const renamingZone = useMemo(
		() => zones.find( ( zone ) => zone.id === renamingZoneId ) ?? null,
		[ zones, renamingZoneId ]
	);
	const hasWooShippingSetup = zones.length > 0;
	const isSetup = screen === 'setup';

	useEffect( () => {
		const screenClasses = [
			'woocommerce-shipping-native-screen-providers',
			'woocommerce-shipping-native-screen-setup',
			'woocommerce-shipping-native-screen-hub',
		];
		const activeScreenClass = `woocommerce-shipping-native-screen-${ screen }`;

		document.body.classList.remove( ...screenClasses );
		document.body.classList.add( activeScreenClass );

		return () => {
			document.body.classList.remove( activeScreenClass );
		};
	}, [ screen ] );

	useEffect( () => {
		if ( typeof window === 'undefined' || zones.length === 0 ) {
			return;
		}

		window.sessionStorage.setItem(
			SHIPPING_NATIVE_STORAGE_KEY,
			JSON.stringify( zones )
		);
	}, [ zones ] );

	function openProviderChoice() {
		setScreen( 'providers' );
		setActiveSection( 'zones' );
		setEditingZoneId( null );
		setRenamingZoneId( null );
	}

	function openWooShippingHub( section: ShippingSection = 'zones' ) {
		setScreen( 'hub' );
		setActiveSection( section );
		setEditingZoneId( null );
		setRenamingZoneId( null );
	}

	function completeSetup(
		finishedZones: ShippingZone[],
		destination: 'zones' | 'providers' = 'zones'
	) {
		dispatch( { type: 'SET_ZONES', zones: finishedZones } );

		if ( destination === 'providers' ) {
			openProviderChoice();
			setFlash( {
				id: 'shipping-ready-provider',
				message:
					'Woo Shipping is connected. Manage zones and rates when you are ready.',
			} );
			return;
		}

		openWooShippingHub( 'zones' );
		setFlash( {
			id: 'shipping-ready-zones',
			message:
				'Shipping is ready. Customers can now see delivery options at checkout.',
		} );
	}

	function useFreeShippingEverywhere() {
		const freeShippingZone: ShippingZone = {
			id: 'zone-worldwide',
			name: 'Worldwide',
			regions: 'All countries',
			methods: {
				flat: {
					on: false,
					rate: '',
					name: 'Standard shipping',
				},
				free: {
					on: true,
					threshold: '',
					name: 'Free shipping',
					trigger: 'threshold',
					coupon: '',
				},
				pickup: {
					on: false,
					name: 'Local pickup',
					address: '456 Hub Avenue, Brooklyn, NY 11201',
					hours: '',
					instructions: '',
				},
				live: {
					on: false,
					name: 'Live carrier rates',
					carriers: {
						usps: true,
						ups: true,
						fedex: false,
						dhl: false,
					},
				},
			},
		};

		completeSetup( [ freeShippingZone ], 'zones' );
	}

	function addZone() {
		const newZone: ShippingZone = {
			id: `zone-${ Date.now() }`,
			name: 'New zone',
			regions: 'No regions yet',
			methods: {
				flat: {
					on: true,
					rate: '',
					name: 'Standard shipping',
				},
				free: {
					on: false,
					threshold: '',
					name: 'Free shipping',
					trigger: 'threshold',
					coupon: '',
				},
				pickup: {
					on: false,
					name: 'Local pickup',
					address: '456 Hub Avenue, Brooklyn, NY 11201',
					hours: '',
					instructions: '',
				},
				live: {
					on: false,
					name: 'Live carrier rates',
					carriers: {
						usps: true,
						ups: true,
						fedex: false,
						dhl: false,
					},
				},
			},
		};

		dispatch( { type: 'ADD_ZONE', zone: newZone } );
		setEditingZoneId( newZone.id );
	}

	return (
		<div
			className={ `woocommerce-shipping-native shipping-native-prototype-flow is-${ screen }` }
		>
			{ screen === 'providers' && <ShippingSettingsHeader /> }

			{ screen !== 'providers' && (
				<ShippingPrototypeHeader
					activeSection={ activeSection }
					isSetup={ isSetup }
					onExitSetup={ openProviderChoice }
					onProviderChoice={ openProviderChoice }
					onSectionChange={ setActiveSection }
				/>
			) }

			{ flash && (
				<SnackbarList
					className="shipping-snackbar-list"
					notices={ [
						{
							id: flash.id ?? 'shipping-native-flash',
							content: flash.message,
						},
					] }
					onRemove={ () => setFlash( null ) }
				/>
			) }

			{ screen === 'providers' && (
				<PrototypeShippingProviderChoice
					isWooShippingConnected={ hasWooShippingSetup }
					isSettingsSurface
					onStartSetup={ () => setScreen( 'setup' ) }
					onManageWooShipping={ () => openWooShippingHub( 'zones' ) }
					onBack={ openProviderChoice }
				/>
			) }

			{ screen === 'setup' && (
				<PrototypeShippingSetupFullPage
					productGroups={ initialProductGroups }
					onBack={ openProviderChoice }
					onFinish={ completeSetup }
					onSkip={ useFreeShippingEverywhere }
				/>
			) }

			{ screen === 'hub' && (
				<PrototypeHub
					zones={ zones }
					carriers={ initialCarriers }
					productGroups={ initialProductGroups }
					activeTab={ activeSection }
					onAddZone={ addZone }
					onEditZone={ setEditingZoneId }
					onRenameZone={ setRenamingZoneId }
					onDeleteZone={ ( id: string ) => {
						dispatch( { type: 'DELETE_ZONE', id } );
						setFlash( {
							id: `deleted-${ id }`,
							message: 'Zone deleted.',
						} );
					} }
				/>
			) }

			{ editingZone && (
				<PrototypeZoneEditPanel
					zone={ editingZone }
					productGroups={ initialProductGroups }
					onSave={ ( updates: Partial< ShippingZone > ) => {
						dispatch( {
							type: 'UPDATE_ZONE',
							id: editingZone.id,
							updates,
						} );
						setEditingZoneId( null );
					} }
					onCancel={ () => setEditingZoneId( null ) }
				/>
			) }

			{ renamingZone && (
				<PrototypeRenameModal
					zone={ renamingZone }
					onSave={ ( name: string ) => {
						dispatch( {
							type: 'RENAME_ZONE',
							id: renamingZone.id,
							name,
						} );
						setRenamingZoneId( null );
					} }
					onCancel={ () => setRenamingZoneId( null ) }
				/>
			) }
		</div>
	);
};
