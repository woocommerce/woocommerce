export const COUNTRY_TREE = {
	id: 'all',
	label: 'All regions',
	children: [
		{
			id: 'north-america',
			label: 'North America',
			children: [
				{
					id: 'us',
					label: 'United States',
					children: [
						{ id: 'us-al', label: 'Alabama' },
						{ id: 'us-ak', label: 'Alaska' },
						{ id: 'us-az', label: 'Arizona' },
						{ id: 'us-ar', label: 'Arkansas' },
						{ id: 'us-ca', label: 'California' },
						{ id: 'us-co', label: 'Colorado' },
						{ id: 'us-ct', label: 'Connecticut' },
						{ id: 'us-de', label: 'Delaware' },
						{ id: 'us-fl', label: 'Florida' },
						{ id: 'us-ga', label: 'Georgia' },
						{ id: 'us-hi', label: 'Hawaii' },
						{ id: 'us-id', label: 'Idaho' },
						{ id: 'us-il', label: 'Illinois' },
						{ id: 'us-in', label: 'Indiana' },
						{ id: 'us-ia', label: 'Iowa' },
						{ id: 'us-ks', label: 'Kansas' },
						{ id: 'us-ky', label: 'Kentucky' },
						{ id: 'us-la', label: 'Louisiana' },
						{ id: 'us-me', label: 'Maine' },
						{ id: 'us-md', label: 'Maryland' },
						{ id: 'us-ma', label: 'Massachusetts' },
						{ id: 'us-mi', label: 'Michigan' },
						{ id: 'us-mn', label: 'Minnesota' },
						{ id: 'us-ms', label: 'Mississippi' },
						{ id: 'us-mo', label: 'Missouri' },
						{ id: 'us-mt', label: 'Montana' },
						{ id: 'us-ne', label: 'Nebraska' },
						{ id: 'us-nv', label: 'Nevada' },
						{ id: 'us-nh', label: 'New Hampshire' },
						{ id: 'us-nj', label: 'New Jersey' },
						{ id: 'us-nm', label: 'New Mexico' },
						{ id: 'us-ny', label: 'New York' },
						{ id: 'us-nc', label: 'North Carolina' },
						{ id: 'us-nd', label: 'North Dakota' },
						{ id: 'us-oh', label: 'Ohio' },
						{ id: 'us-ok', label: 'Oklahoma' },
						{ id: 'us-or', label: 'Oregon' },
						{ id: 'us-pa', label: 'Pennsylvania' },
						{ id: 'us-ri', label: 'Rhode Island' },
						{ id: 'us-sc', label: 'South Carolina' },
						{ id: 'us-sd', label: 'South Dakota' },
						{ id: 'us-tn', label: 'Tennessee' },
						{ id: 'us-tx', label: 'Texas' },
						{ id: 'us-ut', label: 'Utah' },
						{ id: 'us-vt', label: 'Vermont' },
						{ id: 'us-va', label: 'Virginia' },
						{ id: 'us-wa', label: 'Washington' },
						{ id: 'us-wv', label: 'West Virginia' },
						{ id: 'us-wi', label: 'Wisconsin' },
						{ id: 'us-wy', label: 'Wyoming' },
						{ id: 'us-dc', label: 'District of Columbia' },
					],
				},
				{
					id: 'ca',
					label: 'Canada',
					children: [
						{ id: 'ca-ab', label: 'Alberta' },
						{ id: 'ca-bc', label: 'British Columbia' },
						{ id: 'ca-mb', label: 'Manitoba' },
						{ id: 'ca-nb', label: 'New Brunswick' },
						{ id: 'ca-nl', label: 'Newfoundland and Labrador' },
						{ id: 'ca-ns', label: 'Nova Scotia' },
						{ id: 'ca-nt', label: 'Northwest Territories' },
						{ id: 'ca-nu', label: 'Nunavut' },
						{ id: 'ca-on', label: 'Ontario' },
						{ id: 'ca-pe', label: 'Prince Edward Island' },
						{ id: 'ca-qc', label: 'Quebec' },
						{ id: 'ca-sk', label: 'Saskatchewan' },
						{ id: 'ca-yt', label: 'Yukon' },
					],
				},
				{ id: 'mx', label: 'Mexico' },
			],
		},
		{
			id: 'europe',
			label: 'Europe',
			children: [
				{
					id: 'european-union',
					label: 'European Union',
					children: [
						{ id: 'at', label: 'Austria' },
						{ id: 'be', label: 'Belgium' },
						{ id: 'bg', label: 'Bulgaria' },
						{ id: 'hr', label: 'Croatia' },
						{ id: 'cy', label: 'Cyprus' },
						{ id: 'cz', label: 'Czech Republic' },
						{ id: 'dk', label: 'Denmark' },
						{ id: 'ee', label: 'Estonia' },
						{ id: 'fi', label: 'Finland' },
						{ id: 'fr', label: 'France' },
						{ id: 'de', label: 'Germany' },
						{ id: 'gr', label: 'Greece' },
						{ id: 'hu', label: 'Hungary' },
						{ id: 'ie', label: 'Ireland' },
						{ id: 'it', label: 'Italy' },
						{ id: 'lv', label: 'Latvia' },
						{ id: 'lt', label: 'Lithuania' },
						{ id: 'lu', label: 'Luxembourg' },
						{ id: 'mt', label: 'Malta' },
						{ id: 'nl', label: 'Netherlands' },
						{ id: 'pl', label: 'Poland' },
						{ id: 'pt', label: 'Portugal' },
						{ id: 'ro', label: 'Romania' },
						{ id: 'sk', label: 'Slovakia' },
						{ id: 'si', label: 'Slovenia' },
						{ id: 'es', label: 'Spain' },
						{ id: 'se', label: 'Sweden' },
					],
				},
				{ id: 'gb', label: 'United Kingdom' },
				{ id: 'no', label: 'Norway' },
				{ id: 'ch', label: 'Switzerland' },
				{ id: 'is', label: 'Iceland' },
			],
		},
		{
			id: 'asia-pacific',
			label: 'Asia Pacific',
			children: [
				{ id: 'au', label: 'Australia' },
				{ id: 'nz', label: 'New Zealand' },
				{ id: 'jp', label: 'Japan' },
				{ id: 'sg', label: 'Singapore' },
				{ id: 'hk', label: 'Hong Kong' },
				{ id: 'kr', label: 'South Korea' },
				{ id: 'tw', label: 'Taiwan' },
			],
		},
		{
			id: 'latin-america',
			label: 'Latin America',
			children: [
				{ id: 'br', label: 'Brazil' },
				{ id: 'ar', label: 'Argentina' },
				{ id: 'cl', label: 'Chile' },
				{ id: 'co', label: 'Colombia' },
			],
		},
		{
			id: 'middle-east-africa',
			label: 'Middle East & Africa',
			children: [
				{ id: 'ae', label: 'United Arab Emirates' },
				{ id: 'sa', label: 'Saudi Arabia' },
				{ id: 'za', label: 'South Africa' },
				{ id: 'eg', label: 'Egypt' },
			],
		},
	],
};

export const QUICK_GROUPS = [
	{ id: 'north-america', label: 'North America', count: 3 },
	{ id: 'european-union', label: 'European Union', count: 27 },
	{ id: 'asia-pacific', label: 'Asia Pacific', count: 7 },
	{ id: 'latin-america', label: 'Latin America', count: 4 },
];

// --- Pure helpers (no DOM, no React) ---

export function findNodeById( node, id ) {
	if ( node.id === id ) return node;
	if ( node.children ) {
		for ( const c of node.children ) {
			const f = findNodeById( c, id );
			if ( f ) return f;
		}
	}
	return null;
}

export function getAllLeaves( node ) {
	if ( ! node.children || node.children.length === 0 ) return [ node ];
	return node.children.flatMap( getAllLeaves );
}

export function getNodeSelectionState( node, selected ) {
	const leaves = getAllLeaves( node );
	if ( leaves.length === 0 ) return 'unchecked';
	const count = leaves.filter( ( l ) => selected.has( l.id ) ).length;
	if ( count === 0 ) return 'unchecked';
	if ( count === leaves.length ) return 'checked';
	return 'indeterminate';
}

function normalizeSearchTerm( value ) {
	return value
		.normalize( 'NFD' )
		.replace( /[\u0300-\u036f]/g, '' )
		.toLowerCase();
}

export function findAllMatches( node, query, pathLabels = [] ) {
	const results = [];
	const normalizedQuery = normalizeSearchTerm( query );
	if (
		node.id !== 'all' &&
		normalizeSearchTerm( node.label ).includes( normalizedQuery )
	) {
		results.push( { node, pathLabels: [ ...pathLabels ] } );
	}
	if ( node.children ) {
		const nextPath =
			node.id === 'all' ? pathLabels : [ ...pathLabels, node.label ];
		node.children.forEach( ( c ) =>
			results.push( ...findAllMatches( c, query, nextPath ) )
		);
	}
	return results;
}

export function findPathToNode( root, nodeId, currentPath = [] ) {
	if ( ! root.children ) return null;
	for ( const child of root.children ) {
		if ( child.id === nodeId ) return [ ...currentPath, child.id ];
		const p = findPathToNode( child, nodeId, [ ...currentPath, child.id ] );
		if ( p ) return p;
	}
	return null;
}

export function getNodeAtPath( path ) {
	let n = COUNTRY_TREE;
	for ( const id of path ) {
		n = ( n.children || [] ).find( ( c ) => c.id === id );
		if ( ! n ) return null;
	}
	return n;
}

// Compute tag list from selected Set + anywhereElseSelected — same logic as the
// static prototype's syncTreeSelectionToTags() but returns data instead of DOM.
export function computeTags(
	selected,
	anywhereElseSelected,
	splitOut = new Set()
) {
	const tags = [];
	function visit( node ) {
		if ( node.id === 'all' ) {
			( node.children || [] ).forEach( visit );
			return;
		}
		const state = getNodeSelectionState( node, selected );
		if ( state === 'unchecked' ) return;
		const isLeaf = ! node.children || node.children.length === 0;
		if ( isLeaf ) {
			tags.push( {
				id: node.id,
				label: node.label,
				type: 'leaf',
				splitOut: splitOut.has( node.id ),
			} );
			return;
		}
		if ( state === 'checked' ) {
			const leaves = getAllLeaves( node );
			const excluded = leaves.filter( ( leaf ) =>
				splitOut.has( leaf.id )
			);
			excluded.forEach( ( leaf ) => {
				tags.push( {
					id: leaf.id,
					label: leaf.label,
					type: 'leaf',
					splitOut: true,
				} );
			} );
			if ( excluded.length < leaves.length ) {
				tags.push( {
					id: node.id,
					label: node.label,
					type: 'group',
					count: leaves.length - excluded.length,
					excluded: excluded.map( ( leaf ) => ( {
						id: leaf.id,
						label: leaf.label,
					} ) ),
				} );
			}
			return;
		}
		const childrenAreAllLeaves = node.children.every(
			( c ) => ! c.children || c.children.length === 0
		);
		if ( childrenAreAllLeaves ) {
			const leaves = getAllLeaves( node );
			const checkedLeaves = leaves.filter( ( l ) =>
				selected.has( l.id )
			);
			const splitLeaves = checkedLeaves.filter( ( l ) =>
				splitOut.has( l.id )
			);
			const standardLeaves = checkedLeaves.filter(
				( l ) => ! splitOut.has( l.id )
			);
			splitLeaves.forEach( ( leaf ) => {
				tags.push( {
					id: leaf.id,
					label: leaf.label,
					type: 'leaf',
					splitOut: true,
				} );
			} );
			if ( standardLeaves.length === 1 ) {
				tags.push( {
					id: standardLeaves[ 0 ].id,
					label: standardLeaves[ 0 ].label,
					type: 'leaf',
				} );
			} else if ( standardLeaves.length > 1 ) {
				tags.push( {
					id: node.id,
					label: node.label,
					type: 'group',
					partial: {
						selected: standardLeaves.length,
						total: leaves.length,
					},
				} );
			}
		} else {
			node.children.forEach( visit );
		}
	}
	visit( COUNTRY_TREE );
	if ( anywhereElseSelected ) {
		tags.push( {
			id: 'anywhere-else',
			label: 'Anywhere else',
			type: 'special',
		} );
	}
	return tags;
}

// Convert a tag list to zone objects (used when wizard completes)
export function tagsToZones( tags ) {
	const COUNTRY_ZONE_NAMES = {
		us: { name: 'United States', regions: 'United States' },
		'us-ak': { name: 'Alaska', regions: 'Alaska' },
		'us-hi': { name: 'Hawaii', regions: 'Hawaii' },
		ca: { name: 'Canada', regions: 'Canada' },
		gb: { name: 'United Kingdom', regions: 'United Kingdom' },
	};
	const standardTags = tags.filter(
		( tag ) => tag.id !== 'anywhere-else' && ! tag.splitOut
	);
	const customRateTags = tags.filter(
		( tag ) => tag.id !== 'anywhere-else' && tag.splitOut
	);
	const orderedTags = [ ...standardTags, ...customRateTags ];
	const zones = [];
	for ( const tag of orderedTags ) {
		if ( tag.id === 'anywhere-else' ) continue;
		const preset = COUNTRY_ZONE_NAMES[ tag.id ];
		const isContiguousUsPreset =
			tag.id === 'us' &&
			tag.excluded?.some( ( item ) => item.id === 'us-ak' ) &&
			tag.excluded?.some( ( item ) => item.id === 'us-hi' );
		const name = isContiguousUsPreset
			? 'Contiguous US'
			: preset
			? preset.name
			: tag.label;
		const node = findNodeById( COUNTRY_TREE, tag.id );
		const regions = tag.excluded?.length
			? `${
					preset ? preset.regions : tag.label
			  } except ${ formatExcludedLabels( tag.excluded ) }`
			: preset
			? preset.regions
			: node
			? tag.label
			: tag.label;
		zones.push( {
			id: `zone-${ tag.id }`,
			name,
			regions,
			methods: defaultMethods( tag.id ),
			taxOnShipping: 'default',
		} );
	}
	if ( tags.some( ( t ) => t.id === 'anywhere-else' ) ) {
		zones.push( {
			id: 'zone-anywhere-else',
			name: 'Anywhere else',
			regions: 'Any country not in another zone',
			methods: defaultMethods( 'anywhere-else' ),
			taxOnShipping: 'default',
		} );
	}
	return zones;
}

function formatExcludedLabels( excluded ) {
	if ( excluded.length === 1 ) return excluded[ 0 ].label;
	if ( excluded.length === 2 )
		return `${ excluded[ 0 ].label } and ${ excluded[ 1 ].label }`;
	return `${ excluded.length } countries`;
}

function defaultMethods( zoneId = 'default' ) {
	const presets = {
		us: {
			flatRate: '7.00',
			freeOn: true,
			freeThreshold: '50',
			carriers: { usps: true, ups: true, fedex: false, dhl: false },
			serviceLevel: 'all',
			fallback: '12',
			overlapBehavior: 'backup',
			packaging: 'store-default',
			rateDisplay: 'separate',
		},
		'us-ak': {
			flatRate: '18.00',
			freeOn: true,
			freeThreshold: '150',
			carriers: { usps: true, ups: false, fedex: false, dhl: false },
			serviceLevel: 'ground-express',
			fallback: '24',
			overlapBehavior: 'backup',
			packaging: 'store-default',
			rateDisplay: 'separate',
		},
		'us-hi': {
			flatRate: '18.00',
			freeOn: true,
			freeThreshold: '150',
			carriers: { usps: true, ups: false, fedex: false, dhl: false },
			serviceLevel: 'ground-express',
			fallback: '26',
			overlapBehavior: 'backup',
			packaging: 'store-default',
			rateDisplay: 'separate',
		},
		ca: {
			flatRate: '15.00',
			freeOn: true,
			freeThreshold: '100',
			carriers: {
				canadaPost: true,
				fedex: false,
				ups: false,
				dhl: false,
			},
			serviceLevel: 'ground-express',
			fallback: '24',
			overlapBehavior: 'backup',
			packaging: 'store-default',
			rateDisplay: 'separate',
		},
		'european-union': {
			flatRate: '22.00',
			freeOn: true,
			freeThreshold: '150',
			carriers: { dhl: true, fedex: true, ups: false, usps: false },
			serviceLevel: 'ground-express',
			fallback: '30',
			overlapBehavior: 'backup',
			packaging: 'product-dimensions',
			rateDisplay: 'cheapest',
		},
		'asia-pacific': {
			flatRate: '28.00',
			freeOn: true,
			freeThreshold: '180',
			carriers: { dhl: true, fedex: false, ups: false, usps: false },
			serviceLevel: 'express',
			fallback: '38',
			overlapBehavior: 'backup',
			packaging: 'product-dimensions',
			rateDisplay: 'cheapest',
		},
		'latin-america': {
			flatRate: '24.00',
			freeOn: true,
			freeThreshold: '160',
			carriers: { dhl: true, fedex: true, ups: false, usps: false },
			serviceLevel: 'ground-express',
			fallback: '34',
			overlapBehavior: 'backup',
			packaging: 'product-dimensions',
			rateDisplay: 'cheapest',
		},
		'anywhere-else': {
			flatRate: '35.00',
			freeOn: false,
			freeThreshold: '',
			liveOn: true,
			carriers: { dhl: true, fedex: false, ups: false, usps: false },
			serviceLevel: 'all',
			fallback: '45',
			overlapBehavior: 'backup',
			packaging: 'store-default',
			rateDisplay: 'cheapest',
		},
		default: {
			flatRate: '18.00',
			freeOn: true,
			freeThreshold: '120',
			carriers: { dhl: true, fedex: false, ups: false, usps: false },
			serviceLevel: 'all',
			fallback: '28',
			overlapBehavior: 'backup',
			packaging: 'store-default',
			rateDisplay: 'separate',
		},
	};
	const preset = presets[ zoneId ] || presets.default;

	return {
		flat: { on: true, rate: preset.flatRate, name: 'Standard shipping' },
		free: {
			on: preset.freeOn,
			threshold: preset.freeThreshold,
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
			on: !! preset.liveOn,
			name: 'Live carrier rates',
			carriers: preset.carriers,
			serviceLevel: preset.serviceLevel,
			fallback: preset.fallback,
			overlapBehavior: preset.overlapBehavior,
			packaging: preset.packaging,
			rateDisplay: preset.rateDisplay,
		},
	};
}
