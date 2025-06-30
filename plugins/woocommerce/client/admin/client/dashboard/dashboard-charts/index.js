/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { Fragment, useState } from '@wordpress/element';
import LineGraphIcon from 'gridicons/dist/line-graph';
import StatsAltIcon from 'gridicons/dist/stats-alt';
import PropTypes from 'prop-types';
import { Button, NavigableMenu, SelectControl } from '@wordpress/components';

import {
	EllipsisMenu,
	MenuItem,
	MenuTitle,
	SectionHeader,
} from '@woocommerce/components';
import { useUserPreferences } from '@woocommerce/data';
import { getAllowedIntervalsForQuery } from '@woocommerce/date';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import ChartBlock from './block';
import { uniqCharts } from './config';
import './style.scss';

const renderChartToggles = ( { hiddenBlocks, onToggleHiddenBlock } ) => {
	return uniqCharts.map( ( chart ) => {
		const key = chart.endpoint + '_' + chart.key;
		const checked = ! hiddenBlocks.includes( key );
		return (
			<MenuItem
				checked={ checked }
				isCheckbox
				isClickable
				key={ chart.endpoint + '_' + chart.key }
				onInvoke={ () => {
					onToggleHiddenBlock( key )();
					recordEvent( 'dash_charts_chart_toggle', {
						status: checked ? 'off' : 'on',
						key,
					} );
				} }
			>
				{ chart.label }
			</MenuItem>
		);
	} );
};

const renderIntervalSelector = ( {
	chartInterval,
	setInterval,
	query,
	defaultDateRange,
} ) => {
	const allowedIntervals = getAllowedIntervalsForQuery(
		query,
		defaultDateRange
	);
	if ( ! allowedIntervals || allowedIntervals.length < 1 ) {
		return null;
	}

	const intervalLabels = {
		hour: __( 'By hour', 'woocommerce' ),
		day: __( 'By day', 'woocommerce' ),
		week: __( 'By week', 'woocommerce' ),
		month: __( 'By month', 'woocommerce' ),
		quarter: __( 'By quarter', 'woocommerce' ),
		year: __( 'By year', 'woocommerce' ),
	};

	return (
		<SelectControl
			className="woocommerce-chart__interval-select"
			value={ chartInterval }
			options={ allowedIntervals.map( ( allowedInterval ) => ( {
				value: allowedInterval,
				label: intervalLabels[ allowedInterval ],
			} ) ) }
			aria-label="Chart period"
			onChange={ setInterval }
		/>
	);
};

const renderChartBlocks = ( { hiddenBlocks, path, query, filters } ) => {
	// Reduce the API response to only the necessary stat fields
	// by supplying all charts common to each endpoint.
	const chartsByEndpoint = uniqCharts.reduce( ( byEndpoint, chart ) => {
		if ( typeof byEndpoint[ chart.endpoint ] === 'undefined' ) {
			byEndpoint[ chart.endpoint ] = [];
		}
		byEndpoint[ chart.endpoint ].push( chart );

		return byEndpoint;
	}, {} );

	return (
		<div className="woocommerce-dashboard__columns">
			{ uniqCharts.map( ( chart ) => {
				return hiddenBlocks.includes(
					chart.endpoint + '_' + chart.key
				) ? null : (
					<ChartBlock
						charts={ chartsByEndpoint[ chart.endpoint ] }
						endpoint={ chart.endpoint }
						key={ chart.endpoint + '_' + chart.key }
						path={ path }
						query={ query }
						selectedChart={ chart }
						filters={ filters }
					/>
				);
			} ) }
		</div>
	);
};

const DashboardCharts = ( props ) => {
	const {
		controls: Controls,
		hiddenBlocks,
		isFirst,
		isLast,
		onMove,
		onRemove,
		onTitleBlur,
		onTitleChange,
		onToggleHiddenBlock,
		path,
		title,
		titleInput,
		filters,
		defaultDateRange,
	} = props;
	const { updateUserPreferences, ...userPrefs } = useUserPreferences();
	const [ chartType, setChartType ] = useState(
		userPrefs.dashboard_chart_type || 'line'
	);
	const [ chartInterval, setChartInterval ] = useState(
		userPrefs.dashboard_chart_interval || 'day'
	);
	const query = { ...props.query, chartType, interval: chartInterval };

	const handleTypeToggle = ( type ) => {
		return () => {
			setChartType( type );
			const userDataFields = {
				dashboard_chart_type: type,
			};
			updateUserPreferences( userDataFields );
			recordEvent( 'dash_charts_type_toggle', { chart_type: type } );
		};
	};

	const renderMenu = () => (
		<EllipsisMenu
			label={ __( 'Choose which charts to display', 'woocommerce' ) }
			placement={ 'bottom-end' }
			renderContent={ ( { onToggle } ) => (
				<Fragment>
					<MenuTitle>{ __( 'Charts', 'woocommerce' ) }</MenuTitle>
					{ renderChartToggles( {
						hiddenBlocks,
						onToggleHiddenBlock,
					} ) }
					<Controls
						onToggle={ onToggle }
						onMove={ onMove }
						onRemove={ onRemove }
						isFirst={ isFirst }
						isLast={ isLast }
						onTitleBlur={ onTitleBlur }
						onTitleChange={ onTitleChange }
						titleInput={ titleInput }
					/>
				</Fragment>
			) }
		/>
	);

	const setInterval = ( interval ) => {
		setChartInterval( interval );
		const userDataFields = {
			dashboard_chart_interval: interval,
		};
		updateUserPreferences( userDataFields );
		recordEvent( 'dash_charts_interval', { interval } );
	};

	return (
		<div className="woocommerce-dashboard__dashboard-charts">
			<SectionHeader
				title={ title || __( 'Charts', 'woocommerce' ) }
				menu={ renderMenu() }
				className={ 'has-interval-select' }
			>
				{ renderIntervalSelector( {
					chartInterval,
					setInterval,
					query,
					defaultDateRange,
				} ) }
				<NavigableMenu
					className="woocommerce-chart__types"
					orientation="horizontal"
					role="menubar"
				>
					<Button
						className={ clsx( 'woocommerce-chart__type-button', {
							'woocommerce-chart__type-button-selected':
								! query.chartType || query.chartType === 'line',
						} ) }
						title={ __( 'Line chart', 'woocommerce' ) }
						aria-checked={ query.chartType === 'line' }
						role="menuitemradio"
						tabIndex={ query.chartType === 'line' ? 0 : -1 }
						onClick={ handleTypeToggle( 'line' ) }
					>
						<LineGraphIcon />
					</Button>
					<Button
						className={ clsx( 'woocommerce-chart__type-button', {
							'woocommerce-chart__type-button-selected':
								query.chartType === 'bar',
						} ) }
						title={ __( 'Bar chart', 'woocommerce' ) }
						aria-checked={ query.chartType === 'bar' }
						role="menuitemradio"
						tabIndex={ query.chartType === 'bar' ? 0 : -1 }
						onClick={ handleTypeToggle( 'bar' ) }
					>
						<StatsAltIcon />
					</Button>
				</NavigableMenu>
			</SectionHeader>
			{ renderChartBlocks( { hiddenBlocks, path, query, filters } ) }
		</div>
	);
};

DashboardCharts.propTypes = {
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
	defaultDateRange: PropTypes.string.isRequired,
};

export default DashboardCharts;
