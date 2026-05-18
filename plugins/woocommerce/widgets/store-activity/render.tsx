// eslint-disable-next-line @wordpress/use-recommended-components -- experimental DataViews integration.
import { DataViews, filterSortAndPaginate } from '@wordpress/dataviews';
import type { Field, View } from '@wordpress/dataviews';
import { Spinner } from '@wordpress/components';
import { useState, useMemo, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Icon } from '@wordpress/icons';
import { StoreActivityEmptyState, ActivitySourcesLoader } from './components';
import { useActivitySources } from './hooks';
import type { StoreActivityEvent, ActivityHookResult } from './types';
import './style.scss';

const LAYOUT_ACTIVITY = 'activity';

const fields: Field< StoreActivityEvent >[] = [
	{
		label: __( 'Icon', 'woocommerce' ),
		id: 'icon',
		type: 'media',
		render: ( { item } ) => <Icon icon={ item.icon } />,
		enableSorting: false,
	},
	{
		label: __( 'Content', 'woocommerce' ),
		id: 'content',
		render: ( { item } ) => item.renderContent(),
		enableSorting: false,
	},
	{
		id: 'time',
		label: __( 'Time', 'woocommerce' ),
		type: 'datetime',
		enableSorting: false,
		getValue: ( { item } ) => item.datetime,
		render: ( { item } ) => (
			<span>
				{ new Date( item.datetime ).toLocaleTimeString( 'en-US', {
					hour: 'numeric',
					minute: '2-digit',
					hour12: true,
				} ) }
			</span>
		),
	},
	{
		id: 'date',
		label: __( 'Date', 'woocommerce' ),
		type: 'date',
		enableSorting: false,
		getValue: ( { item } ) => item.datetime.split( 'T' )[ 0 ],
	},
];

const defaultView: View = {
	type: LAYOUT_ACTIVITY,
	search: '',
	page: 1,
	perPage: 20,
	filters: [],
	fields: [ 'time' ],
	titleField: 'content',
	mediaField: 'icon',
	showMedia: true,
	sort: {
		field: 'datetime',
		direction: 'desc',
	},
	groupBy: {
		field: 'date',
		direction: 'desc',
		showLabel: false,
	},
};

/**
 * Store Activity widget render component.
 * Aggregates events from registered sources and renders them via DataViews
 * activity layout.
 */
export default function StoreActivityRender() {
	const [ view, setView ] = useState< View >( defaultView );
	const sources = useActivitySources();
	const [ results, setResults ] = useState<
		Record< string, ActivityHookResult >
	>( {} );

	const updateResults = useCallback(
		( sourceId: string, result: ActivityHookResult ) => {
			setResults( ( prev ) => ( {
				...prev,
				[ sourceId ]: result,
			} ) );
		},
		[]
	);

	const allResults = useMemo( () => Object.values( results ), [ results ] );

	const events = useMemo(
		() =>
			allResults
				.flatMap( ( result ) =>
					result.state === 'success' ? result.events ?? [] : []
				)
				.sort(
					( a, b ) =>
						new Date( b.datetime ).getTime() -
						new Date( a.datetime ).getTime()
				),
		[ allResults ]
	);

	const isLoading = allResults.some(
		( result ) => result.state === 'loading'
	);

	const waitingForResults =
		sources.length > 0 && allResults.length < sources.length;

	const { data: shownData, paginationInfo } = useMemo( () => {
		return filterSortAndPaginate( events, view, fields );
	}, [ events, view ] );

	return (
		<>
			<ActivitySourcesLoader
				sources={ sources }
				onResult={ updateResults }
			/>

			{ ( isLoading || waitingForResults ) && (
				<div className="store-activity-widget__loading">
					<Spinner />
				</div>
			) }

			{ ! isLoading &&
				! waitingForResults &&
				events.length === 0 && <StoreActivityEmptyState /> }

			{ ! isLoading && ! waitingForResults && events.length > 0 && (
				<div className="store-activity-widget">
					<DataViews
						getItemId={ ( item ) => item.id.toString() }
						paginationInfo={ paginationInfo }
						data={ shownData }
						view={ view }
						fields={ fields }
						onChangeView={ setView }
						defaultLayouts={ {
							[ LAYOUT_ACTIVITY ]: {
								sort: {
									field: 'datetime',
									direction: 'desc',
								},
							},
						} }
					>
						<DataViews.Layout />
					</DataViews>
				</div>
			) }
		</>
	);
}
