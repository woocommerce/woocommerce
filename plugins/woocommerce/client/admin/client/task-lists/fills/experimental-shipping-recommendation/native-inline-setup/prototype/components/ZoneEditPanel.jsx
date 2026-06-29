import { Fragment, useState, useEffect } from 'react';
import { isRTL } from '@wordpress/i18n';
import {
	Button,
	CheckboxControl,
	DropdownMenu,
	SelectControl,
	TextControl,
	__experimentalInputControl as InputControl,
	__experimentalInputControlPrefixWrapper as InputControlPrefixWrapper,
} from '@wordpress/components';
import {
	chevronLeft,
	chevronRight,
	close,
	moreVertical,
} from '@wordpress/icons';
import TreeCombo from './TreeCombo.jsx';
import {
	COUNTRY_TREE,
	computeTags,
	findNodeById,
	getAllLeaves,
} from '../data/countryTree.js';

const METHOD_CONFIG = [
	{
		key: 'flat',
		type: 'Flat rate',
		defaultName: 'Standard shipping',
		chooserDescription: 'A single charge for any order in this zone.',
	},
	{
		key: 'free',
		type: 'Free shipping',
		defaultName: 'Free shipping',
		chooserDescription:
			'No charge, usually paired with a minimum order or coupon.',
	},
	{
		key: 'pickup',
		type: 'Local pickup',
		defaultName: 'Local pickup',
		chooserDescription:
			'Customers collect from a store address instead of shipping.',
	},
	{
		key: 'live',
		type: 'Live carrier rates',
		defaultName: 'Live carrier rates',
		chooserDescription:
			'Show real-time rates from connected carriers. Can appear alongside other active options.',
	},
];

const METHOD_LOOKUP = Object.fromEntries(
	METHOD_CONFIG.map( ( config ) => [ config.key, config ] )
);
const STORE_ADDRESS = '456 Hub Avenue, Brooklyn, NY 11201';
const CUSTOM_METHOD_PREFIX = 'custom:';
const REPEATABLE_METHOD_KEYS = new Set( [ 'flat', 'free' ] );
const FLAT_OPTION_NAME_PRESETS = [
	'Express shipping',
	'Priority shipping',
	'Economy shipping',
];
const FREE_OPTION_NAME_PRESETS = [
	'Free shipping over $100',
	'Free shipping with coupon',
	'Free shipping over $150',
];

function makeCustomDetailKey( id ) {
	return `${ CUSTOM_METHOD_PREFIX }${ id }`;
}

function isCustomDetailKey( key ) {
	return `${ key || '' }`.startsWith( CUSTOM_METHOD_PREFIX );
}

function getCustomDetailId( key ) {
	return `${ key || '' }`.slice( CUSTOM_METHOD_PREFIX.length );
}

function getCustomMethods( methods ) {
	return Array.isArray( methods?.custom ) ? methods.custom : [];
}

function getCustomMethodsByType( methods, type ) {
	return getCustomMethods( methods ).filter(
		( method ) => ( method.type || 'flat' ) === type
	);
}

function normalizeCustomMethod( option = {}, index = 0 ) {
	const type = option.type === 'free' ? 'free' : 'flat';

	return {
		id: option.id || `custom-${ type }-${ index + 1 }`,
		type,
		on: true,
		rate: '0.00',
		name: type === 'free' ? 'Free shipping over $100' : 'Express shipping',
		threshold: type === 'free' ? '100' : '',
		trigger: 'threshold',
		coupon: '',
		discountHandling: 'after-discount',
		customerNote: '',
		taxStatus: 'default',
		calculation: 'none',
		formula: '',
		...option,
		productGroupCosts: {
			heavy: '',
			fragile: '',
			none: '',
			...( option.productGroupCosts || {} ),
		},
	};
}

function getDetailConfig( methods, detailKey ) {
	if ( isCustomDetailKey( detailKey ) ) {
		const method = getDetailMethod( methods, detailKey );
		return METHOD_LOOKUP[ method?.type || 'flat' ];
	}

	return METHOD_LOOKUP[ detailKey ];
}

function getDetailMethod( methods, detailKey ) {
	if ( isCustomDetailKey( detailKey ) ) {
		return getCustomMethods( methods ).find(
			( method ) => method.id === getCustomDetailId( detailKey )
		);
	}
	return methods[ detailKey ];
}

function countActiveDeliveryOptions( methods ) {
	const builtInCount = METHOD_CONFIG.filter(
		( config ) => methods[ config.key ]?.on
	).length;
	const customCount = getCustomMethods( methods ).filter(
		( method ) => method.on
	).length;
	return builtInCount + customCount;
}

function nextFlatOptionName( methods ) {
	const customFlatMethods = getCustomMethodsByType( methods, 'flat' );
	const usedNames = new Set(
		[
			methods.flat?.name,
			...customFlatMethods.map( ( method ) => method.name ),
		]
			.filter( Boolean )
			.map( ( name ) => name.trim().toLowerCase() )
	);

	const presetName = FLAT_OPTION_NAME_PRESETS.find(
		( name ) => ! usedNames.has( name.toLowerCase() )
	);
	if ( presetName ) return presetName;

	return `Flat rate ${ customFlatMethods.length + 2 }`;
}

function makeCustomFlatMethod( methods ) {
	const customMethods = getCustomMethodsByType( methods, 'flat' );
	return normalizeCustomMethod(
		{
			id: `custom-flat-${ Date.now() }-${ customMethods.length + 1 }`,
			type: 'flat',
			name: nextFlatOptionName( methods ),
			rate: '18.00',
		},
		customMethods.length
	);
}

function nextFreeOptionName( methods ) {
	const customFreeMethods = getCustomMethodsByType( methods, 'free' );
	const usedNames = new Set(
		[
			methods.free?.name,
			...customFreeMethods.map( ( method ) => method.name ),
		]
			.filter( Boolean )
			.map( ( name ) => name.trim().toLowerCase() )
	);

	const presetName = FREE_OPTION_NAME_PRESETS.find(
		( name ) => ! usedNames.has( name.toLowerCase() )
	);
	if ( presetName ) return presetName;

	return `Free shipping ${ customFreeMethods.length + 2 }`;
}

function makeCustomFreeMethod( methods ) {
	const customMethods = getCustomMethodsByType( methods, 'free' );
	return normalizeCustomMethod(
		{
			id: `custom-free-${ Date.now() }-${ customMethods.length + 1 }`,
			type: 'free',
			name: nextFreeOptionName( methods ),
			threshold: customMethods.length === 0 ? '100' : '150',
		},
		customMethods.length
	);
}

function makeTreeValueFromZone( zone ) {
	if ( zone.destinations ) {
		return {
			selected:
				zone.destinations.selected instanceof Set
					? new Set( zone.destinations.selected )
					: new Set( zone.destinations.selected || [] ),
			anywhereElseSelected: !! zone.destinations.anywhereElseSelected,
			splitOut:
				zone.destinations.splitOut instanceof Set
					? new Set( zone.destinations.splitOut )
					: new Set( zone.destinations.splitOut || [] ),
		};
	}

	const selected = new Set();
	const splitOut = new Set();
	const text = `${ zone.id } ${ zone.name } ${ zone.regions }`.toLowerCase();
	const hasExceptions = text.includes( 'except' );

	function addNodeLeaves( id ) {
		const node = findNodeById( COUNTRY_TREE, id );
		if ( ! node ) return;
		getAllLeaves( node ).forEach( ( leaf ) => selected.add( leaf.id ) );
	}

	function addMatchingLeaves( node ) {
		if ( node.id !== 'all' ) {
			const labelPattern = new RegExp(
				`\\b${ node.label
					.toLowerCase()
					.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) }\\b`
			);
			if ( labelPattern.test( text ) ) {
				addNodeLeaves( node.id );
			}
		}
		( node.children || [] ).forEach( addMatchingLeaves );
	}

	if (
		text.includes( 'domestic' ) ||
		text.includes( 'contiguous us' ) ||
		text.includes( 'united states' )
	)
		addNodeLeaves( 'us' );
	if ( text.includes( 'canada' ) ) addNodeLeaves( 'ca' );
	if ( text.includes( 'european union' ) ) addNodeLeaves( 'european-union' );
	if ( text.includes( 'asia pacific' ) ) addNodeLeaves( 'asia-pacific' );
	if ( text.includes( 'latin america' ) ) addNodeLeaves( 'latin-america' );
	if ( text.includes( 'north america' ) ) addNodeLeaves( 'north-america' );
	if ( hasExceptions && text.includes( 'alaska' ) ) splitOut.add( 'us-ak' );
	if ( hasExceptions && text.includes( 'hawaii' ) ) splitOut.add( 'us-hi' );
	addMatchingLeaves( COUNTRY_TREE );

	return {
		selected,
		anywhereElseSelected:
			text.includes( 'anywhere else' ) ||
			text.includes( 'any country not in another zone' ),
		splitOut,
	};
}

function formatDestinationSummary( value ) {
	const splitOut =
		value.splitOut instanceof Set
			? value.splitOut
			: new Set( value.splitOut || [] );
	const tags = computeTags(
		value.selected,
		value.anywhereElseSelected,
		splitOut
	);
	if ( tags.length === 0 ) return 'No regions yet';

	return tags
		.map( ( tag ) => {
			if ( tag.id === 'anywhere-else' )
				return 'Any country not in another zone';
			if ( tag.splitOut ) return `${ tag.label } separately`;
			if ( tag.excluded?.length === 1 )
				return `${ tag.label } except ${ tag.excluded[ 0 ].label }`;
			if ( tag.excluded?.length === 2 )
				return `${ tag.label } except ${ tag.excluded[ 0 ].label } and ${ tag.excluded[ 1 ].label }`;
			if ( tag.excluded?.length > 2 )
				return `${ tag.label } except ${ tag.excluded.length } countries`;
			return tag.label;
		} )
		.join( ', ' );
}

function shouldOpenDestinations( zone ) {
	return zone.name === 'New zone' || zone.regions === 'No regions yet';
}

function getCarrierOptions( zone ) {
	const zoneText =
		`${ zone.id } ${ zone.name } ${ zone.regions }`.toLowerCase();
	if ( zoneText.includes( 'canada' ) ) {
		return [
			{ key: 'canadaPost', label: 'Canada Post' },
			{ key: 'fedex', label: 'FedEx' },
			{ key: 'ups', label: 'UPS' },
			{ key: 'dhl', label: 'DHL' },
		];
	}
	if (
		zoneText.includes( 'domestic' ) ||
		zoneText.includes( 'united states' ) ||
		zoneText.includes( 'us' )
	) {
		return [
			{ key: 'usps', label: 'USPS' },
			{ key: 'ups', label: 'UPS' },
			{ key: 'fedex', label: 'FedEx' },
			{ key: 'dhl', label: 'DHL' },
		];
	}
	return [
		{ key: 'dhl', label: 'DHL' },
		{ key: 'fedex', label: 'FedEx' },
		{ key: 'ups', label: 'UPS' },
		{ key: 'usps', label: 'USPS' },
	];
}

function getDefaultCarriers( zone ) {
	const options = getCarrierOptions( zone );
	return options.reduce( ( acc, option, index ) => {
		acc[ option.key ] = index === 0;
		return acc;
	}, {} );
}

function normalizeMethods( zone ) {
	const source = zone.methods || {};
	const liveSource = source.live || {};

	return {
		flat: {
			on: false,
			rate: '0.00',
			name: 'Standard shipping',
			taxStatus: 'default',
			calculation: 'none',
			formula: '',
			...( source.flat || {} ),
			productGroupCosts: {
				heavy: '',
				fragile: '',
				none: '',
				...( source.flat?.productGroupCosts || {} ),
			},
		},
		free: {
			on: false,
			threshold: '',
			name: 'Free shipping',
			trigger: 'threshold',
			coupon: '',
			discountHandling: 'after-discount',
			customerNote: '',
			...( source.free || {} ),
		},
		pickup: {
			on: false,
			rate: '0.00',
			name: 'Local pickup',
			hours: '',
			instructions: '',
			taxStatus: 'default',
			...( source.pickup || {} ),
			address: source.pickup?.address || STORE_ADDRESS,
		},
		live: {
			on: false,
			name: 'Live carrier rates',
			serviceLevel: 'all',
			fallback: '',
			overlapBehavior: 'backup',
			packaging: 'store-default',
			rateDisplay: 'separate',
			...( source.live || {} ),
			carriers: {
				...getDefaultCarriers( zone ),
				...( liveSource.carriers || {} ),
			},
		},
		custom: ( Array.isArray( source.custom ) ? source.custom : [] ).map(
			( method, index ) => normalizeCustomMethod( method, index )
		),
	};
}

function selectedCarrierLabels( method, zone ) {
	const options = getCarrierOptions( zone );
	const labelsByKey = Object.fromEntries(
		options.map( ( option ) => [ option.key, option.label ] )
	);
	return Object.entries( method.carriers || {} )
		.filter( ( [ , selected ] ) => selected )
		.map( ( [ key ] ) => labelsByKey[ key ] || key )
		.filter( Boolean );
}

function MethodSummary( {
	methodKey,
	method,
	methods,
	zone,
	backupEligible = false,
} ) {
	if ( ! method.on ) {
		return <>Off - not shown at checkout.</>;
	}

	const name = method.name || METHOD_LOOKUP[ methodKey ].defaultName;

	if ( methodKey === 'flat' ) {
		if (
			backupEligible &&
			methods?.live?.on &&
			( methods.live.overlapBehavior || 'backup' ) === 'backup'
		) {
			return <>Used as backup when live rates are unavailable.</>;
		}
		return (
			<>
				Customers see{ ' ' }
				<strong>
					{ name } - ${ method.rate || '0.00' }
				</strong>{ ' ' }
				at checkout.
			</>
		);
	}

	if ( methodKey === 'free' ) {
		if ( method.trigger === 'coupon' ) {
			return (
				<>
					Customers see <strong>{ name }</strong> when a coupon is
					used.
				</>
			);
		}
		if ( method.trigger === 'either' ) {
			return (
				<>
					Customers see <strong>{ name }</strong> when the order is
					over ${ method.threshold || '0' } or a coupon is used.
				</>
			);
		}
		if ( method.trigger === 'both' ) {
			return (
				<>
					Customers see <strong>{ name }</strong> when the order is
					over ${ method.threshold || '0' } and a coupon is used.
				</>
			);
		}
		if ( method.threshold && method.threshold !== '0' ) {
			return (
				<>
					Customers see <strong>{ name }</strong> when the order is
					over ${ method.threshold }.
				</>
			);
		}
		return (
			<>
				Customers see <strong>{ name }</strong> on every order.
			</>
		);
	}

	if ( methodKey === 'pickup' ) {
		const isFree =
			! method.rate || method.rate === '0' || method.rate === '0.00';
		if ( isFree ) {
			return (
				<>
					Customers see <strong>{ name }</strong> as a checkout
					option.
				</>
			);
		}
		return (
			<>
				Customers see{ ' ' }
				<strong>
					{ name } - ${ method.rate }
				</strong>{ ' ' }
				at checkout.
			</>
		);
	}

	if ( methodKey === 'live' ) {
		const carriers = selectedCarrierLabels( method, zone );
		if ( carriers.length === 0 ) {
			return (
				<>
					Customers see <strong>{ name }</strong>, but no carriers are
					selected yet.
				</>
			);
		}
		if (
			methods?.flat?.on &&
			( method.overlapBehavior || 'backup' ) === 'backup'
		) {
			return (
				<>
					Customers see live rates from{ ' ' }
					<strong>{ carriers.join( ', ' ) }</strong>. Standard
					shipping is the backup.
				</>
			);
		}
		if ( methods?.flat?.on && method.overlapBehavior === 'show-both' ) {
			return (
				<>
					Customers can choose live rates from{ ' ' }
					<strong>{ carriers.join( ', ' ) }</strong> or standard
					shipping.
				</>
			);
		}
		return (
			<>
				Customers see live rates from{ ' ' }
				<strong>{ carriers.join( ', ' ) }</strong> at checkout.
			</>
		);
	}

	return null;
}

function formatBaseRate( rate ) {
	const cleanRate = `${ rate || '' }`.trim();
	return cleanRate ? `$${ cleanRate }` : 'the base rate';
}

function shouldShowMethodType( name, config ) {
	const normalizedName = `${ name || config.defaultName }`
		.trim()
		.toLowerCase();
	const normalizedType = config.type.trim().toLowerCase();
	return normalizedName !== normalizedType;
}

function MoneyInput( {
	id,
	label,
	value,
	onChange,
	help,
	prefix = '$',
	placeholder = '0.00',
	width = '140px',
} ) {
	return (
		<InputControl
			id={ id }
			className="md-money-control"
			label={ label }
			value={ value || '' }
			onChange={ ( nextValue ) => onChange( nextValue || '' ) }
			placeholder={ placeholder }
			prefix={
				<InputControlPrefixWrapper>
					{ prefix }
				</InputControlPrefixWrapper>
			}
			help={ help }
			inputMode="decimal"
			type="text"
			__next40pxDefaultSize
			__unstableInputWidth={ width }
		/>
	);
}

function ProductGroupCostInput( { id, label, value, onChange, help } ) {
	const inputId = `zedit-product-group-${ id }`;
	return (
		<div className="zedit-product-group-field">
			<MoneyInput
				id={ inputId }
				label={ label }
				value={ value }
				onChange={ onChange }
				prefix="+$"
				width="160px"
				help={
					help ||
					'Added to the base rate when this product group is in the cart.'
				}
			/>
		</div>
	);
}

export default function ZoneEditPanel( {
	zone,
	onSave,
	onCancel,
	mode = 'zone',
	productGroups = [],
	embedded = false,
	embeddedBackLabel = 'All shipping zones',
	embeddedEyebrow,
} ) {
	const [ name, setName ] = useState( zone.name );
	const [ destinations, setDestinations ] = useState( () =>
		makeTreeValueFromZone( zone )
	);
	const [ destinationsOpen, setDestinationsOpen ] = useState( () =>
		shouldOpenDestinations( zone )
	);
	const [ methods, setMethods ] = useState( () => normalizeMethods( zone ) );
	const [ view, setView ] = useState( 'overview' );
	const [ detailKey, setDetailKey ] = useState( 'flat' );
	const [ chooserOpen, setChooserOpen ] = useState( false );
	const [ expandedAdv, setExpandedAdv ] = useState( {
		flat: false,
		free: false,
		pickup: false,
		live: false,
	} );

	useEffect( () => {
		setName( zone.name );
		setDestinations( makeTreeValueFromZone( zone ) );
		setDestinationsOpen( shouldOpenDestinations( zone ) );
		setMethods( normalizeMethods( zone ) );
		setView( 'overview' );
		setDetailKey( 'flat' );
		setChooserOpen( false );
		setExpandedAdv( {
			flat: false,
			free: false,
			pickup: false,
			live: false,
		} );
	}, [ zone ] );

	useEffect( () => {
		function handler( e ) {
			if ( e.key === 'Escape' ) onCancel();
		}
		window.addEventListener( 'keydown', handler );
		return () => window.removeEventListener( 'keydown', handler );
	}, [ onCancel ] );

	function updateMethod( key, partial ) {
		setMethods( ( prev ) => ( {
			...prev,
			[ key ]: { ...prev[ key ], ...partial },
		} ) );
	}

	function updateCustomMethod( id, partial ) {
		setMethods( ( prev ) => ( {
			...prev,
			custom: getCustomMethods( prev ).map( ( method ) =>
				method.id === id ? { ...method, ...partial } : method
			),
		} ) );
	}

	function updateDetailMethod( partial ) {
		if ( isCustomDetailKey( detailKey ) ) {
			updateCustomMethod( getCustomDetailId( detailKey ), partial );
			return;
		}
		updateMethod( detailKey, partial );
	}

	function openMethod( key, { activate = false } = {} ) {
		if ( isCustomDetailKey( key ) ) {
			if ( activate ) {
				updateCustomMethod( getCustomDetailId( key ), { on: true } );
			}
			setDetailKey( key );
			setView( 'detail' );
			setChooserOpen( false );
			return;
		}
		if ( activate && ! methods[ key ].on ) {
			updateMethod( key, { on: true } );
		}
		setDetailKey( key );
		setView( 'detail' );
		setChooserOpen( false );
	}

	function removeMethodByKey( key ) {
		const method = getDetailMethod( methods, key );
		if ( method?.on && countActiveDeliveryOptions( methods ) <= 1 ) return;

		if ( isCustomDetailKey( key ) ) {
			const customId = getCustomDetailId( key );
			setMethods( ( prev ) => ( {
				...prev,
				custom: getCustomMethods( prev ).filter(
					( customMethod ) => customMethod.id !== customId
				),
			} ) );
			return;
		}

		updateMethod( key, { on: false } );
	}

	function showOverview() {
		setView( 'overview' );
		setChooserOpen( false );
		setExpandedAdv( {
			flat: false,
			free: false,
			pickup: false,
			live: false,
		} );
	}

	function addCustomFlatOption() {
		const customMethod = makeCustomFlatMethod( methods );
		setMethods( ( prev ) => ( {
			...prev,
			custom: [ ...getCustomMethods( prev ), customMethod ],
		} ) );
		setDetailKey( makeCustomDetailKey( customMethod.id ) );
		setView( 'detail' );
		setChooserOpen( false );
	}

	function addCustomFreeOption() {
		const customMethod = makeCustomFreeMethod( methods );
		setMethods( ( prev ) => ( {
			...prev,
			custom: [ ...getCustomMethods( prev ), customMethod ],
		} ) );
		setDetailKey( makeCustomDetailKey( customMethod.id ) );
		setView( 'detail' );
		setChooserOpen( false );
	}

	function removeDetailMethod() {
		removeMethodByKey( detailKey );
		showOverview();
	}

	function getDeliveryOptionControls(
		key,
		method,
		label,
		{ isCustom = false } = {}
	) {
		const isOn = !! method.on;
		const canRemove = isCustom || isOn;
		const isLastActive = isOn && activeMethodCount <= 1;
		const controls = [
			{
				title: isOn ? 'Edit' : 'Add',
				onClick: () => openMethod( key, { activate: ! isOn } ),
			},
		];

		if ( canRemove ) {
			controls.push( {
				title: 'Remove',
				label: `Remove ${ label }`,
				isDisabled: isLastActive,
				onClick: () => removeMethodByKey( key ),
			} );
		}

		return controls;
	}

	function setLiveCarrier( key, checked ) {
		updateMethod( 'live', {
			carriers: {
				...( methods.live.carriers || {} ),
				[ key ]: checked,
			},
		} );
	}

	function setDetailProductGroupCost( key, value ) {
		const detailMethod = getDetailMethod( methods, detailKey );
		updateDetailMethod( {
			productGroupCosts: {
				...( detailMethod?.productGroupCosts || {} ),
				[ key ]: value,
			},
		} );
	}

	function handleSave() {
		onSave( {
			name,
			regions: formatDestinationSummary( destinations ),
			destinations,
			methods,
		} );
	}

	function handleOverlayClick( e ) {
		if ( e.target === e.currentTarget ) onCancel();
	}

	const activeMethodCount = countActiveDeliveryOptions( methods );
	const detailConfig = getDetailConfig( methods, detailKey );
	const detailMethod = getDetailMethod( methods, detailKey );
	const isFlatDetail = detailConfig?.key === 'flat';
	const isFreeDetail = detailConfig?.key === 'free';
	const isCustomDetail = isCustomDetailKey( detailKey );
	const isLastActiveDetail = !! detailMethod?.on && activeMethodCount <= 1;
	const regionsSummary =
		mode === 'methods'
			? zone.regions
			: formatDestinationSummary( destinations );
	const draftZone = { ...zone, name, regions: regionsSummary };
	const forceDestinationsOpen =
		mode === 'zone' && shouldOpenDestinations( draftZone );
	const liveCarrierOptions = getCarrierOptions( draftZone );
	const liveCarrierLabels = selectedCarrierLabels( methods.live, draftZone );
	const hasFlatRateForLiveBackup = !! methods.flat?.on;
	const liveUsesFlatBackup =
		hasFlatRateForLiveBackup &&
		( methods.live.overlapBehavior || 'backup' ) === 'backup';
	const detailOptionName = detailMethod?.name || detailConfig?.defaultName;
	const showEmbeddedParentBack = embedded && view !== 'detail';
	const headerEyebrow =
		embedded && view === 'detail'
			? ''
			: embeddedEyebrow ||
			  ( mode === 'methods'
					? 'Set up delivery options'
					: 'Editing zone' );
	const detailActionControls =
		detailMethod?.on || isCustomDetail
			? [
					{
						title: 'Remove',
						label: `Remove ${ detailOptionName }`,
						isDisabled: isLastActiveDetail,
						onClick: removeDetailMethod,
					},
			  ]
			: [];

	const panel = (
		<div
			className={ `zedit-panel zedit-panel-v2${
				embedded ? ' zedit-panel-embedded' : ''
			}` }
			role={ embedded ? 'region' : 'dialog' }
			aria-modal={ embedded ? undefined : true }
			aria-label={
				mode === 'methods'
					? `Set up delivery options: ${ zone.name }`
					: `Edit zone: ${ zone.name }`
			}
		>
			<header
				className={ `zedit-header${
					embedded ? ' zedit-header-embedded' : ''
				}` }
			>
				<div>
					{ showEmbeddedParentBack && (
						<Button
							className="drawer-back-link zedit-embedded-back"
							variant="tertiary"
							icon={ isRTL() ? chevronRight : chevronLeft }
							onClick={ onCancel }
							__next40pxDefaultSize
						>
							{ embeddedBackLabel }
						</Button>
					) }
					{ headerEyebrow && (
						<div className="zedit-eyebrow">{ headerEyebrow }</div>
					) }
					<h3 className="zedit-title">{ name || zone.name }</h3>
					<p className="zedit-subtitle">{ regionsSummary }</p>
				</div>
				{ ! embedded && (
					<Button
						icon={ close }
						label="Close"
						onClick={ onCancel }
						__next40pxDefaultSize
					/>
				) }
			</header>

			<div className="zedit-body">
				{ view === 'overview' && (
					<div className="drawer-view is-active">
						{ mode === 'zone' && (
							<section className="zedit-section">
								<div className="zedit-section-title">Zone</div>
								{ forceDestinationsOpen && (
									<p className="zedit-section-desc">
										Pick countries and regions for this
										zone. If you set a custom rate for a
										country, saving creates a separate zone
										for it.
									</p>
								) }
								<TextControl
									label="Zone name"
									value={ name }
									onChange={ setName }
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
								<div className="zedit-destinations-field">
									{ ! forceDestinationsOpen && (
										<div className="zedit-region-action-row">
											<span className="zedit-destinations-label">
												Regions
											</span>
											<Button
												variant="tertiary"
												onClick={ () =>
													setDestinationsOpen(
														( open ) => ! open
													)
												}
												aria-expanded={
													destinationsOpen
												}
												aria-controls="zedit-destinations-tree"
												aria-label={ `${
													destinationsOpen
														? 'Done editing'
														: 'Edit'
												} countries and regions for ${
													name || zone.name
												}` }
												__next40pxDefaultSize
											>
												{ destinationsOpen
													? 'Done'
													: 'Edit regions' }
											</Button>
										</div>
									) }
									{ ( forceDestinationsOpen ||
										destinationsOpen ) && (
										<div id="zedit-destinations-tree">
											<TreeCombo
												label="Countries and regions"
												value={ destinations }
												onChange={ setDestinations }
											/>
										</div>
									) }
								</div>
							</section>
						) }

						<section className="zedit-section">
							<div className="zedit-section-title">
								Delivery options
							</div>
							<p className="zedit-section-desc">
								Choose what customers can select at checkout for
								this zone.
							</p>

							<div className="delivery-options-list">
								{ METHOD_CONFIG.map( ( config ) => {
									const method = methods[ config.key ];
									const isOn = !! method.on;
									const methodName =
										method.name || config.defaultName;
									const showMethodType = shouldShowMethodType(
										methodName,
										config
									);
									const rowControls =
										getDeliveryOptionControls(
											config.key,
											method,
											methodName
										);
									return (
										<Fragment key={ config.key }>
											<div
												className={ `delivery-option-row${
													isOn ? '' : ' is-off'
												}` }
											>
												<button
													type="button"
													className="delivery-option-row-main"
													onClick={ () =>
														openMethod( config.key )
													}
												>
													<span className="dor-main">
														<span className="dor-name-line">
															<span className="dor-name">
																{ methodName }
															</span>
															{ showMethodType && (
																<span className="dor-type">
																	{
																		config.type
																	}
																</span>
															) }
														</span>
														<span className="dor-summary">
															<MethodSummary
																methodKey={
																	config.key
																}
																method={
																	method
																}
																methods={
																	methods
																}
																zone={
																	draftZone
																}
																backupEligible={
																	config.key ===
																	'flat'
																}
															/>
														</span>
													</span>
													<span className="dor-meta">
														<span
															className={ `dor-status${
																isOn
																	? ' dor-status-active'
																	: ' dor-status-off'
															}` }
														>
															{ isOn
																? 'Active'
																: 'Off' }
														</span>
													</span>
												</button>
												<DropdownMenu
													className="delivery-option-menu"
													icon={ moreVertical }
													label={ `More actions for ${ methodName }` }
													controls={ rowControls }
													noIcons
													toggleProps={ {
														className:
															'delivery-option-menu-toggle',
														__next40pxDefaultSize: true,
													} }
													popoverProps={ {
														placement: 'bottom-end',
													} }
												/>
											</div>
											{ REPEATABLE_METHOD_KEYS.has(
												config.key
											) &&
												getCustomMethodsByType(
													methods,
													config.key
												).map( ( customMethod ) => {
													const customName =
														customMethod.name ||
														config.defaultName;
													const customOn =
														!! customMethod.on;
													const customKey =
														makeCustomDetailKey(
															customMethod.id
														);
													const customControls =
														getDeliveryOptionControls(
															customKey,
															customMethod,
															customName,
															{ isCustom: true }
														);
													return (
														<div
															key={
																customMethod.id
															}
															className={ `delivery-option-row delivery-option-row-custom${
																customOn
																	? ''
																	: ' is-off'
															}` }
														>
															<button
																type="button"
																className="delivery-option-row-main"
																onClick={ () =>
																	openMethod(
																		customKey
																	)
																}
															>
																<span className="dor-main">
																	<span className="dor-name-line">
																		<span className="dor-name">
																			{
																				customName
																			}
																		</span>
																		<span className="dor-type">
																			{
																				config.type
																			}
																		</span>
																	</span>
																	<span className="dor-summary">
																		<MethodSummary
																			methodKey={
																				config.key
																			}
																			method={
																				customMethod
																			}
																			methods={
																				methods
																			}
																			zone={
																				draftZone
																			}
																		/>
																	</span>
																</span>
																<span className="dor-meta">
																	<span
																		className={ `dor-status${
																			customOn
																				? ' dor-status-active'
																				: ' dor-status-off'
																		}` }
																	>
																		{ customOn
																			? 'Active'
																			: 'Off' }
																	</span>
																</span>
															</button>
															<DropdownMenu
																className="delivery-option-menu"
																icon={
																	moreVertical
																}
																label={ `More actions for ${ customName }` }
																controls={
																	customControls
																}
																noIcons
																toggleProps={ {
																	className:
																		'delivery-option-menu-toggle',
																	__next40pxDefaultSize: true,
																} }
																popoverProps={ {
																	placement:
																		'bottom-end',
																} }
															/>
														</div>
													);
												} ) }
										</Fragment>
									);
								} ) }
							</div>

							<div className="add-delivery-option-wrap">
								<Button
									variant="secondary"
									onClick={ () =>
										setChooserOpen( ( open ) => ! open )
									}
									__next40pxDefaultSize
								>
									Add delivery option
								</Button>
								{ chooserOpen && (
									<div className="add-delivery-option-chooser">
										<div className="chooser-title">
											Choose a type
										</div>
										<button
											type="button"
											className="chooser-row"
											onClick={ addCustomFlatOption }
										>
											<span>
												<span className="chooser-name">
													Flat rate
												</span>
												<span className="chooser-desc">
													Add another fixed-price
													delivery option, such as
													Express shipping.
												</span>
											</span>
											<span className="chooser-already">
												Can add more
											</span>
										</button>
										{ METHOD_CONFIG.filter(
											( config ) => config.key !== 'flat'
										).map( ( config ) => {
											const alreadyActive =
												!! methods[ config.key ].on;
											const canAddMore =
												REPEATABLE_METHOD_KEYS.has(
													config.key
												);
											return (
												<button
													key={ config.key }
													type="button"
													className="chooser-row"
													disabled={
														alreadyActive &&
														! canAddMore
													}
													onClick={ () => {
														if (
															config.key ===
																'free' &&
															alreadyActive
														) {
															addCustomFreeOption();
															return;
														}

														openMethod(
															config.key,
															{ activate: true }
														);
													} }
												>
													<span>
														<span className="chooser-name">
															{ config.type }
														</span>
														<span className="chooser-desc">
															{
																config.chooserDescription
															}
														</span>
													</span>
													{ ( alreadyActive ||
														canAddMore ) && (
														<span className="chooser-already">
															{ canAddMore
																? 'Can add more'
																: 'Already active' }
														</span>
													) }
												</button>
											);
										} ) }
									</div>
								) }
							</div>

							{ activeMethodCount === 0 && (
								<p
									className="zedit-inline-warning"
									role="status"
								>
									This zone has no active delivery options, so
									customers here will not see shipping at
									checkout.
								</p>
							) }
						</section>
					</div>
				) }

				{ view === 'detail' && (
					<div className="drawer-view is-active">
						<Button
							className="drawer-back-link"
							variant="tertiary"
							icon={ isRTL() ? chevronRight : chevronLeft }
							onClick={ showOverview }
							aria-label={ `Back to delivery options for ${
								name || zone.name
							}` }
							__next40pxDefaultSize
						>
							Delivery options
						</Button>

						<div className="method-detail-header">
							<h4 className="method-detail-title">
								<span>{ detailOptionName }</span>
								{ shouldShowMethodType(
									detailOptionName,
									detailConfig
								) && (
									<span className="method-detail-type">
										{ detailConfig.type }
									</span>
								) }
							</h4>
							{ detailActionControls.length > 0 && (
								<DropdownMenu
									className="method-detail-menu"
									icon={ moreVertical }
									label={ `More actions for ${ detailOptionName }` }
									controls={ detailActionControls }
									noIcons
									toggleProps={ {
										className: 'method-detail-menu-toggle',
										__next40pxDefaultSize: true,
									} }
									popoverProps={ { placement: 'bottom-end' } }
								/>
							) }
						</div>

						<div className="method-detail-active">
							<CheckboxControl
								label="Show this option to customers in this zone"
								checked={ !! detailMethod.on }
								onChange={ ( checked ) => {
									if ( ! checked && isLastActiveDetail )
										return;
									updateDetailMethod( { on: checked } );
								} }
								__nextHasNoMarginBottom
							/>
							{ isLastActiveDetail && (
								<p className="method-detail-help" role="status">
									At least one delivery option needs to stay
									active for checkout.
								</p>
							) }
						</div>

						<section className="method-detail-section">
							<div className="method-detail-section-title">
								Essential settings
							</div>
							{ isFlatDetail && (
								<>
									<TextControl
										label="Delivery option name"
										value={ detailMethod.name }
										onChange={ ( value ) =>
											updateDetailMethod( {
												name: value,
											} )
										}
										help="What customers see at checkout."
										__next40pxDefaultSize
									/>
									<MoneyInput
										id={ `zedit-${
											isCustomDetail
												? getCustomDetailId( detailKey )
												: 'flat'
										}-rate` }
										label="Cost"
										value={ detailMethod.rate }
										onChange={ ( rate ) =>
											updateDetailMethod( { rate } )
										}
										help="Charged once per order."
									/>
								</>
							) }

							{ isFreeDetail && (
								<>
									<TextControl
										label="Delivery option name"
										value={ detailMethod.name }
										onChange={ ( value ) =>
											updateDetailMethod( {
												name: value,
											} )
										}
										__next40pxDefaultSize
									/>
									<SelectControl
										label="When should free shipping appear?"
										value={
											detailMethod.trigger || 'threshold'
										}
										onChange={ ( trigger ) =>
											updateDetailMethod( { trigger } )
										}
										options={ [
											{
												value: 'threshold',
												label: 'Order total reaches a minimum',
											},
											{
												value: 'coupon',
												label: 'A coupon code is used',
											},
											{
												value: 'either',
												label: 'Either a minimum or a coupon',
											},
											{
												value: 'both',
												label: 'Both a minimum and a coupon',
											},
										] }
										__next40pxDefaultSize
									/>
									{ [
										'threshold',
										'either',
										'both',
									].includes(
										detailMethod.trigger || 'threshold'
									) && (
										<MoneyInput
											id={ `zedit-${
												isCustomDetail
													? getCustomDetailId(
															detailKey
													  )
													: 'free'
											}-threshold` }
											label="Minimum order total"
											value={ detailMethod.threshold }
											onChange={ ( threshold ) =>
												updateDetailMethod( {
													threshold,
												} )
											}
											placeholder="0"
											help="Customers see free shipping when their cart reaches this amount."
										/>
									) }
									{ [ 'coupon', 'either', 'both' ].includes(
										detailMethod.trigger || 'threshold'
									) && (
										<TextControl
											label="Coupon requirement"
											value={ detailMethod.coupon || '' }
											onChange={ ( coupon ) =>
												updateDetailMethod( { coupon } )
											}
											placeholder="Any coupon marked for free shipping"
											help="In current Woo, this uses coupons that allow free shipping."
											__next40pxDefaultSize
										/>
									) }
								</>
							) }

							{ detailKey === 'pickup' && (
								<>
									<TextControl
										label="Delivery option name"
										value={ methods.pickup.name }
										onChange={ ( value ) =>
											updateMethod( 'pickup', {
												name: value,
											} )
										}
										__next40pxDefaultSize
									/>
									<MoneyInput
										id="zedit-pickup-rate"
										label="Pickup cost"
										value={ methods.pickup.rate || '0.00' }
										onChange={ ( rate ) =>
											updateMethod( 'pickup', { rate } )
										}
										help="Most stores leave this at $0."
									/>
									<TextControl
										label="Pickup address"
										value={ methods.pickup.address || '' }
										onChange={ ( address ) =>
											updateMethod( 'pickup', {
												address,
											} )
										}
										placeholder="e.g. 123 Main St, New York, NY"
										help="Customers see this in the order confirmation."
										__next40pxDefaultSize
									/>
								</>
							) }

							{ detailKey === 'live' && (
								<>
									<TextControl
										label="Delivery option name"
										value={ methods.live.name }
										onChange={ ( value ) =>
											updateMethod( 'live', {
												name: value,
											} )
										}
										__next40pxDefaultSize
									/>
									<div className="md-field">
										<span className="md-label">
											Carriers shown at checkout
										</span>
										<div className="md-carrier-list">
											{ liveCarrierOptions.map(
												( carrier ) => {
													const isChecked =
														!! methods.live
															.carriers?.[
															carrier.key
														];
													return (
														<div
															key={ carrier.key }
															className={ `md-carrier-check${
																isChecked
																	? ' is-checked'
																	: ''
															}` }
														>
															<CheckboxControl
																label={
																	carrier.label
																}
																checked={
																	isChecked
																}
																onChange={ (
																	checked
																) =>
																	setLiveCarrier(
																		carrier.key,
																		checked
																	)
																}
																__nextHasNoMarginBottom
															/>
														</div>
													);
												}
											) }
										</div>
										{ liveCarrierLabels.length === 0 && (
											<p
												className="zedit-inline-warning"
												role="status"
											>
												Select at least one carrier so
												customers can see live rates.
											</p>
										) }
										<p className="md-field-help">
											Carrier accounts and services are
											managed from the carriers tab.
										</p>
									</div>
									{ hasFlatRateForLiveBackup && (
										<SelectControl
											label="Rate overlap"
											value={
												methods.live.overlapBehavior ||
												'backup'
											}
											onChange={ ( overlapBehavior ) =>
												updateMethod( 'live', {
													overlapBehavior,
												} )
											}
											options={ [
												{
													value: 'backup',
													label: 'Use standard shipping as backup (recommended)',
												},
												{
													value: 'show-both',
													label: 'Show standard shipping and live carrier rates at checkout',
												},
											] }
											help={
												liveUsesFlatBackup
													? 'Customers see live carrier rates. If carriers cannot return a rate, standard shipping appears instead.'
													: 'Customers can choose between standard shipping and live carrier rates at checkout.'
											}
											__next40pxDefaultSize
										/>
									) }
								</>
							) }
						</section>

						<div
							className={ `more-options${
								expandedAdv[ detailKey ] ? ' is-expanded' : ''
							}` }
						>
							<button
								type="button"
								className="more-options-toggle"
								aria-expanded={ expandedAdv[ detailKey ] }
								aria-controls={ `zedit-${ detailKey }-advanced` }
								onClick={ () =>
									setExpandedAdv( ( prev ) => ( {
										...prev,
										[ detailKey ]: ! prev[ detailKey ],
									} ) )
								}
							>
								<span className="caret">
									{ expandedAdv[ detailKey ] ? '▾' : '▸' }
								</span>
								Advanced
							</button>

							{ expandedAdv[ detailKey ] && (
								<div
									className="more-options-panel"
									id={ `zedit-${ detailKey }-advanced` }
								>
									{ isFlatDetail && (
										<>
											<SelectControl
												label="Tax status"
												value={
													detailMethod.taxStatus ||
													'default'
												}
												onChange={ ( taxStatus ) =>
													updateDetailMethod( {
														taxStatus,
													} )
												}
												options={ [
													{
														value: 'default',
														label: 'Use store default',
													},
													{
														value: 'taxable',
														label: 'Charge tax on this rate',
													},
													{
														value: 'none',
														label: 'No tax on this rate',
													},
												] }
												__next40pxDefaultSize
											/>
											<SelectControl
												label="Different costs for certain products"
												value={
													detailMethod.calculation ||
													'none'
												}
												onChange={ ( calculation ) =>
													updateDetailMethod( {
														calculation,
													} )
												}
												options={ [
													{
														value: 'none',
														label: 'Use the same rate for every product',
													},
													{
														value: 'per-class',
														label: 'Add costs for each matching product group',
													},
													{
														value: 'per-order',
														label: 'Add only the highest product group cost',
													},
												] }
												help="Use product groups when some products cost more to ship, such as heavy or fragile items."
												__next40pxDefaultSize
											/>
											{ detailMethod.calculation !==
												'none' && (
												<div className="zedit-product-group-costs">
													<span className="zedit-product-group-title">
														Extra cost by product
														group
													</span>
													<div className="zedit-base-rate-note">
														Base flat rate:{ ' ' }
														<strong>
															{ formatBaseRate(
																detailMethod.rate
															) }
														</strong>
														. Set in cost above.
														Product group amounts
														are added to this rate.
													</div>
													<p className="zedit-cond-note">
														Product groups are
														assigned from each
														product. Add amounts
														here when a group should
														cost more to ship in
														this zone.
													</p>
													{ productGroups.length >
													0 ? (
														productGroups.map(
															( group ) => (
																<ProductGroupCostInput
																	key={
																		group.id
																	}
																	id={
																		group.id
																	}
																	label={ `${ group.name } products` }
																	value={
																		detailMethod
																			.productGroupCosts?.[
																			group
																				.id
																		] || ''
																	}
																	onChange={ (
																		value
																	) =>
																		setDetailProductGroupCost(
																			group.id,
																			value
																		)
																	}
																	help={ `${ group.meta }. Added to the base rate for this zone.` }
																/>
															)
														)
													) : (
														<div className="zedit-product-group-empty">
															<p>
																No product
																groups yet.
																Product groups
																are assigned
																from the product
																editor, then
																priced here per
																zone.
															</p>
															<Button
																variant="secondary"
																__next40pxDefaultSize
															>
																Create product
																group
															</Button>
														</div>
													) }
													<ProductGroupCostInput
														id="none"
														label="Everything else"
														value={
															detailMethod
																.productGroupCosts
																?.none || ''
														}
														onChange={ ( value ) =>
															setDetailProductGroupCost(
																'none',
																value
															)
														}
														help="Usually $0. Use this only if ungrouped products need their own extra cost."
													/>
												</div>
											) }
										</>
									) }

									{ isFreeDetail && (
										<>
											<SelectControl
												label="Discount handling"
												value={
													detailMethod.discountHandling ||
													'after-discount'
												}
												onChange={ (
													discountHandling
												) =>
													updateDetailMethod( {
														discountHandling,
													} )
												}
												options={ [
													{
														value: 'after-discount',
														label: 'Apply discounts before checking the minimum',
													},
													{
														value: 'before-discount',
														label: 'Check the minimum on the pre-discount total',
													},
												] }
												__next40pxDefaultSize
											/>
											<TextControl
												label="Customer note"
												value={
													detailMethod.customerNote ||
													''
												}
												onChange={ ( customerNote ) =>
													updateDetailMethod( {
														customerNote,
													} )
												}
												placeholder="e.g. Arrives in 5-7 business days"
												help="Shown next to the option at checkout."
												__next40pxDefaultSize
											/>
										</>
									) }

									{ detailKey === 'pickup' && (
										<>
											<SelectControl
												label="Tax status"
												value={
													methods.pickup.taxStatus ||
													'default'
												}
												onChange={ ( taxStatus ) =>
													updateMethod( 'pickup', {
														taxStatus,
													} )
												}
												options={ [
													{
														value: 'default',
														label: 'Use store default',
													},
													{
														value: 'taxable',
														label: 'Charge tax',
													},
													{
														value: 'none',
														label: 'No tax',
													},
												] }
												__next40pxDefaultSize
											/>
											<TextControl
												label="Available hours"
												value={
													methods.pickup.hours || ''
												}
												onChange={ ( hours ) =>
													updateMethod( 'pickup', {
														hours,
													} )
												}
												placeholder="e.g. Mon-Fri, 10am-5pm"
												__next40pxDefaultSize
											/>
											<TextControl
												label="Pickup instructions"
												value={
													methods.pickup
														.instructions || ''
												}
												onChange={ ( instructions ) =>
													updateMethod( 'pickup', {
														instructions,
													} )
												}
												placeholder="e.g. Bring photo ID and order number"
												help="Shown in the order confirmation email."
												__next40pxDefaultSize
											/>
										</>
									) }

									{ detailKey === 'live' && (
										<>
											{ liveUsesFlatBackup ? (
												<p className="zedit-cond-note">
													Backup rate comes from
													standard shipping:{ ' ' }
													<strong>
														{ formatBaseRate(
															methods.flat.rate
														) }
													</strong>
													.
												</p>
											) : (
												<MoneyInput
													id="zedit-live-backup-rate"
													label="Backup rate"
													value={
														methods.live.fallback ||
														''
													}
													onChange={ ( fallback ) =>
														updateMethod( 'live', {
															fallback,
														} )
													}
													help="Shown when a carrier cannot return live rates."
												/>
											) }
										</>
									) }
								</div>
							) }
						</div>
					</div>
				) }
			</div>

			<footer className="zedit-footer">
				<Button
					variant="tertiary"
					onClick={ onCancel }
					__next40pxDefaultSize
				>
					Cancel
				</Button>
				<Button
					variant="primary"
					onClick={ handleSave }
					__next40pxDefaultSize
				>
					{ mode === 'methods'
						? 'Save delivery options'
						: 'Save changes' }
				</Button>
			</footer>
		</div>
	);

	if ( embedded ) {
		return panel;
	}

	return (
		<div className="zedit-overlay" onClick={ handleOverlayClick }>
			{ panel }
		</div>
	);
}
