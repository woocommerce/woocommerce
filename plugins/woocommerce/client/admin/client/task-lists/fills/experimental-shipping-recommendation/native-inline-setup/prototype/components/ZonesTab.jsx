import { useState, useMemo } from 'react';
import { Button } from '@wordpress/components';
import { DataViews, filterSortAndPaginate } from '@wordpress/dataviews';
import { summarizeMethods } from '../data/mockData.js';

function getZoneStatus( zone ) {
	const methods = zone.methods || {};
	const customMethods = Array.isArray( methods.custom ) ? methods.custom : [];
	const hasActiveMethod = [
		methods.flat,
		methods.free,
		methods.pickup,
		methods.live,
		...customMethods,
	].some( ( method ) => method?.on );

	return hasActiveMethod ? 'Active' : 'Inactive';
}

function formatDeliveryOption( method ) {
	if (
		method.name === 'Live carrier rates' &&
		method.detail === 'Real-time rates'
	) {
		return 'Real-time rates';
	}

	if (
		method.name === 'Standard shipping' &&
		method.detail?.startsWith( 'Backup $' )
	) {
		return `Standard Shipping as backup · ${ method.detail.replace(
			'Backup ',
			''
		).replace( '.00', '' ) }`;
	}

	if ( method.name === 'Standard shipping' ) {
		return `Standard Shipping · ${ method.detail }`;
	}

	if (
		method.name === 'Free shipping' &&
		method.detail?.startsWith( 'Free over ' )
	) {
		return `Free shipping · Over ${ method.detail.replace(
			'Free over ',
			''
		) }`;
	}

	if ( ! method.detail ) {
		return method.name;
	}

	return `${ method.name } · ${ method.detail }`;
}

function mapZoneToRow( zone ) {
	const isContiguousUs = zone.name === 'Contiguous US';

	return {
		id: zone.id,
		name: zone.name,
		regions: isContiguousUs
			? 'Alabama, Arizona, Arkansas, California, Colorado, and'
			: zone.regions,
		regionMore: isContiguousUs ? '43 more' : undefined,
		status: getZoneStatus( zone ),
		deliveryOptions: summarizeMethods( zone.methods ).map(
			formatDeliveryOption
		),
		sourceZoneId: zone.id,
	};
}

function ZoneCell( { item } ) {
	return (
		<div className="zone-title-cell">
			<span className="zone-title-cell-name">{ item.name }</span>
			<span className="zone-title-cell-regions">
				{ item.regions }
				{ item.regionMore && (
					<>
						{ ' ' }
						<span className="zone-region-more">
							{ item.regionMore }
						</span>
					</>
				) }
			</span>
		</div>
	);
}

function StatusCell( { item } ) {
	return (
		<span
			className={ `zone-status-pill ${
				item.status === 'Active' ? 'is-active' : 'is-inactive'
			}` }
		>
			{ item.status }
		</span>
	);
}

function DeliveryOptionsCell( { item } ) {
	return (
		<div className="zone-delivery-options-cell">
			{ item.deliveryOptions.map( ( option ) => (
				<span key={ option }>{ option }</span>
			) ) }
		</div>
	);
}

// Real WPDS DataViews instance for the Zones list.
// Fields define the columns; actions are the row-level menu.
export default function ZonesTab( {
	zones,
	onAddZone,
	onEditZone,
	onRenameZone,
	onDeleteZone,
} ) {
	// The view describes the active layout, search, filters, sort, pagination.
	// Persisted in component state so the user's interactions stick across renders.
	const [ view, setView ] = useState( {
		type: 'table',
		perPage: 25,
		page: 1,
		search: '',
		fields: [ 'status', 'deliveryOptions' ],
		layout: {
			styles: {
				status: { width: '120px' },
				deliveryOptions: { minWidth: '320px' },
			},
		},
		titleField: 'zone',
		showTitle: true,
	} );

	const rows = useMemo( () => zones.map( mapZoneToRow ), [ zones ] );

	// Field definitions — id, label, render function for non-trivial columns,
	// optional getValue for search/filter/sort.
	const fields = useMemo(
		() => [
			{
				id: 'zone',
				label: 'Zone',
				enableHiding: false,
				enableGlobalSearch: true,
				getValue: ( { item } ) =>
					`${ item.name } ${ item.regions }${
						item.regionMore || ''
					}`,
				render: ZoneCell,
			},
			{
				id: 'status',
				label: 'Status',
				enableHiding: false,
				enableSorting: false,
				getValue: ( { item } ) => item.status,
				render: StatusCell,
			},
			{
				id: 'deliveryOptions',
				label: 'Delivery options',
				enableHiding: false,
				enableSorting: false,
				getValue: ( { item } ) => item.deliveryOptions.join( ' ' ),
				render: DeliveryOptionsCell,
			},
		],
		[]
	);

	// Row-level actions — edit, rename, delete. Show up in the kebab on each row.
	const actions = useMemo(
		() => [
			{
				id: 'edit',
				label: 'Edit',
				callback: ( [ zone ] ) => onEditZone( zone.sourceZoneId ),
			},
			{
				id: 'rename',
				label: 'Rename',
				callback: ( [ zone ] ) => onRenameZone( zone.sourceZoneId ),
			},
			{
				id: 'delete',
				label: 'Delete',
				isDestructive: true,
				callback: ( [ zone ] ) => {
					if (
						window.confirm(
							`Delete the zone "${ zone.name }"? This can't be undone.`
						)
					) {
						onDeleteZone( zone.sourceZoneId );
					}
				},
			},
		],
		[ onEditZone, onRenameZone, onDeleteZone ]
	);

	// Filter, sort, paginate the data based on the current view state.
	// filterSortAndPaginate is provided by @wordpress/dataviews and does the work.
	const { data: shownZones, paginationInfo } = useMemo(
		() => filterSortAndPaginate( rows, view, fields ),
		[ rows, view, fields ]
	);
	const zoneCountLabel = `${ rows.length } ${
		rows.length === 1 ? 'zone' : 'zones'
	}`;

	return (
		<div className="zones-tab">
			<div className="zones-tab-toolbar">
				<h2 className="zones-tab-count">{ zoneCountLabel }</h2>
				<Button
					variant="primary"
					__next40pxDefaultSize
					onClick={ onAddZone }
				>
					Add zone
				</Button>
			</div>
			<div className="dataview-card zones-dataview-card">
				<div className="zones-dataview-scroll">
					<DataViews
						data={ shownZones }
						fields={ fields }
						view={ view }
						onChangeView={ setView }
						actions={ actions }
						search={ false }
						getItemId={ ( item ) => item.id }
						paginationInfo={ paginationInfo }
						defaultLayouts={ { table: {} } }
					/>
				</div>
			</div>
		</div>
	);
}
