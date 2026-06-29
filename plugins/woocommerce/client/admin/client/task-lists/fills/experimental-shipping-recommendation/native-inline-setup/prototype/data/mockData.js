// Mock state for the hub — mirrors the static prototype's ZONE_METHODS_STATE.
// All data lives in memory; refreshes reset it. No backend.

export const initialZones = [
	{
		id: 'domestic-us',
		name: 'Contiguous US',
		regions: 'United States',
		methods: {
			flat: { on: true, rate: '7.00', name: 'Standard shipping' },
			free: {
				on: true,
				threshold: '50',
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
				carriers: { usps: true, ups: true, fedex: false, dhl: false },
				serviceLevel: 'all',
				fallback: '',
				overlapBehavior: 'backup',
				packaging: 'store-default',
				rateDisplay: 'separate',
			},
		},
		taxOnShipping: 'default',
	},
	{
		id: 'canada',
		name: 'Canada',
		regions: 'Canada',
		methods: {
			flat: { on: true, rate: '15.00', name: 'Standard shipping' },
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
					canadaPost: true,
					fedex: false,
					ups: false,
					dhl: false,
				},
				serviceLevel: 'all',
				fallback: '',
				overlapBehavior: 'backup',
				packaging: 'store-default',
				rateDisplay: 'separate',
			},
		},
		taxOnShipping: 'default',
	},
	{
		id: 'anywhere-else',
		name: 'Anywhere else',
		regions: 'Any country not in another zone',
		methods: {
			flat: { on: true, rate: '35.00', name: 'Standard shipping' },
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
				on: true,
				name: 'Live carrier rates',
				carriers: { dhl: true, fedex: false, ups: false, usps: false },
				serviceLevel: 'all',
				fallback: '',
				overlapBehavior: 'backup',
				packaging: 'store-default',
				rateDisplay: 'separate',
			},
		},
		taxOnShipping: 'default',
	},
];

export const initialCarriers = [
	{
		id: 'woocommerce-shipping',
		name: 'WooCommerce Shipping',
		description:
			'First-party label buying and live rates for USPS, UPS, and DHL Express.',
		logo: { initials: 'W', className: 'woo' },
		connected: true,
		meta: 'Live rates and labels available · used in 2 zones',
	},
	{
		id: 'shippo',
		name: 'Shippo',
		description:
			'Multi-carrier rates, labels, and tracking for stores that already use Shippo.',
		logo: { initials: 'S', className: 'shippo' },
		connected: false,
	},
	{
		id: 'shipstation',
		name: 'ShipStation',
		description:
			'Batch label workflows for merchants shipping orders across sales channels.',
		logo: { initials: 'SS', className: 'shipstation' },
		connected: false,
	},
	{
		id: 'fedex',
		name: 'FedEx',
		description:
			'Connect a FedEx account directly for negotiated rates and label printing.',
		logo: { initials: 'F', className: 'fedex' },
		connected: false,
	},
];

export const initialProductGroups = [
	{ id: 'heavy', name: 'Heavy', meta: '12 products · used in 2 zones' },
	{ id: 'fragile', name: 'Fragile', meta: '4 products · used in 1 zone' },
];

function summarizeFreeMethod( method ) {
	const threshold = method.threshold;

	if ( method.trigger === 'coupon' ) return 'With coupon';
	if ( method.trigger === 'either' )
		return threshold && threshold !== '0'
			? `Over $${ threshold } or coupon`
			: 'With coupon';
	if ( method.trigger === 'both' )
		return threshold && threshold !== '0'
			? `Over $${ threshold } and coupon`
			: 'With coupon';

	return threshold && threshold !== '0'
		? `Free over $${ threshold }`
		: 'Free';
}

export function summarizeMethods( methods ) {
	const lines = [];
	const customMethods = Array.isArray( methods.custom ) ? methods.custom : [];
	const liveUsesFlatBackup =
		methods.live?.on &&
		methods.flat?.on &&
		( methods.live.overlapBehavior || 'backup' ) === 'backup';
	if ( methods.live?.on )
		lines.push( { name: methods.live.name, detail: 'Real-time rates' } );
	if ( methods.flat.on ) {
		lines.push( {
			name: methods.flat.name,
			detail: liveUsesFlatBackup
				? `Backup $${ methods.flat.rate || '0.00' }`
				: `$${ methods.flat.rate || '0.00' }`,
		} );
	}
	customMethods
		.filter( ( method ) => method.on )
		.forEach( ( method ) => {
			if ( method.type === 'free' ) {
				lines.push( {
					name: method.name,
					detail: summarizeFreeMethod( method ),
				} );
				return;
			}

			lines.push( {
				name: method.name,
				detail: `$${ method.rate || '0.00' }`,
			} );
		} );
	if ( methods.free.on ) {
		lines.push( {
			name: methods.free.name,
			detail: summarizeFreeMethod( methods.free ),
		} );
	}
	if ( methods.pickup.on )
		lines.push( { name: methods.pickup.name, detail: 'Free' } );
	if ( lines.length === 0 )
		lines.push( { name: 'No delivery options enabled', detail: '' } );
	return lines;
}
