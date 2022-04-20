/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';
import {
	createElement,
	Component,
	createRef,
	Fragment,
} from '@wordpress/element';
import { formatDefaultLocale as d3FormatDefaultLocale } from 'd3-format';
import { isEqual, partial, without } from 'lodash';
import LineGraphIcon from 'gridicons/dist/line-graph';
import StatsAltIcon from 'gridicons/dist/stats-alt';
import { Button, NavigableMenu, SelectControl } from '@wordpress/components';
import { interpolateViridis as d3InterpolateViridis } from 'd3-scale-chromatic';
import memoize from 'memoize-one';
import PropTypes from 'prop-types';
import { withViewportMatch } from '@wordpress/viewport';
import { sanitize } from 'dompurify';
import { getIdsFromQuery, updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ChartPlaceholder from './placeholder';
import { H, Section } from '../section';
import { D3Chart, D3Legend } from './d3chart';
import { getUniqueKeys } from './d3chart/utils/index';
import { selectionLimit } from './constants';

function getD3CurrencyFormat( symbol, position ) {
	switch ( position ) {
		case 'left_space':
			return [ symbol + ' ', '' ];
		case 'right':
			return [ '', symbol ];
		case 'right_space':
			return [ '', ' ' + symbol ];
		case 'left':
		default:
			return [ symbol, '' ];
	}
}

/**
 * A chart container using d3, to display timeseries data with an interactive legend.
 */
class Chart extends Component {
	constructor( props ) {
		super( props );
		this.chartBodyRef = createRef();
		const dataKeys = this.getDataKeys();
		this.state = {
			focusedKeys: [],
			visibleKeys: dataKeys.slice( 0, selectionLimit ),
			width: 0,
		};
		this.prevDataKeys = dataKeys.sort();
		this.handleTypeToggle = this.handleTypeToggle.bind( this );
		this.handleLegendToggle = this.handleLegendToggle.bind( this );
		this.handleLegendHover = this.handleLegendHover.bind( this );
		this.updateDimensions = this.updateDimensions.bind( this );
		this.getVisibleData = memoize( this.getVisibleData );
		this.getOrderedKeys = memoize( this.getOrderedKeys );
		this.setInterval = this.setInterval.bind( this );
	}

	getDataKeys() {
		const { data, filterParam, mode, query } = this.props;
		if ( mode === 'item-comparison' ) {
			const selectedIds = filterParam
				? getIdsFromQuery( query[ filterParam ] )
				: [];
			return this.getOrderedKeys( [], [], selectedIds ).map(
				( orderedItem ) => orderedItem.key
			);
		}
		return getUniqueKeys( data );
	}

	componentDidUpdate() {
		const { data } = this.props;
		if ( ! data || ! data.length ) {
			return;
		}
		const uniqueKeys = getUniqueKeys( data ).sort();

		if ( ! isEqual( uniqueKeys, this.prevDataKeys ) ) {
			const dataKeys = this.getDataKeys();
			this.prevDataKeys = uniqueKeys;
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				visibleKeys: dataKeys.slice( 0, selectionLimit ),
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	componentDidMount() {
		this.updateDimensions();
		this.setD3DefaultFormat();
		window.addEventListener( 'resize', this.updateDimensions );
	}

	componentWillUnmount() {
		window.removeEventListener( 'resize', this.updateDimensions );
	}

	setD3DefaultFormat() {
		const {
			symbol: currencySymbol,
			symbolPosition,
			decimalSeparator: decimal,
			thousandSeparator: thousands,
		} = this.props.currency;
		d3FormatDefaultLocale( {
			decimal,
			thousands,
			grouping: [ 3 ],
			currency: getD3CurrencyFormat( currencySymbol, symbolPosition ),
		} );
	}

	getOrderedKeys( focusedKeys, visibleKeys, selectedIds = [] ) {
		const { data, legendTotals, mode } = this.props;
		if ( ! data || data.length === 0 ) {
			return [];
		}

		const uniqueKeys = data.reduce( ( accum, curr ) => {
			Object.entries( curr ).forEach( ( [ key, value ] ) => {
				if ( key !== 'date' && ! accum[ key ] ) {
					accum[ key ] = value.label;
				}
			} );
			return accum;
		}, {} );

		const updatedKeys = Object.entries( uniqueKeys ).map(
			( [ key, label ] ) => {
				label = sanitize( label, { ALLOWED_TAGS: [] } );
				return {
					focus:
						focusedKeys.length === 0 || focusedKeys.includes( key ),
					key,
					label,
					total:
						legendTotals &&
						typeof legendTotals[ key ] !== 'undefined'
							? legendTotals[ key ]
							: data.reduce( ( a, c ) => a + c[ key ].value, 0 ),
					visible: visibleKeys.includes( key ),
				};
			}
		);

		if ( mode === 'item-comparison' ) {
			return updatedKeys
				.sort( ( a, b ) => b.total - a.total )
				.filter(
					( key ) =>
						key.total > 0 ||
						selectedIds.includes( parseInt( key.key, 10 ) )
				);
		}

		return updatedKeys;
	}

	handleTypeToggle( chartType ) {
		if ( this.props.chartType !== chartType ) {
			const { path, query } = this.props;
			updateQueryString( { chartType }, path, query );
		}
	}

	handleLegendToggle( event ) {
		const { interactiveLegend } = this.props;
		if ( ! interactiveLegend ) {
			return;
		}
		const key = event.currentTarget.id.split( '_' ).pop();
		const { focusedKeys, visibleKeys } = this.state;
		if ( visibleKeys.includes( key ) ) {
			this.setState( {
				focusedKeys: without( focusedKeys, key ),
				visibleKeys: without( visibleKeys, key ),
			} );
		} else {
			this.setState( {
				focusedKeys: focusedKeys.concat( [ key ] ),
				visibleKeys: visibleKeys.concat( [ key ] ),
			} );
		}
	}

	handleLegendHover( event ) {
		if ( event.type === 'mouseleave' || event.type === 'blur' ) {
			this.setState( {
				focusedKeys: [],
			} );
		} else if ( event.type === 'mouseenter' || event.type === 'focus' ) {
			const key = event.currentTarget.id.split( '__' ).pop();
			this.setState( {
				focusedKeys: [ key ],
			} );
		}
	}

	updateDimensions() {
		this.setState( {
			width: this.chartBodyRef.current.offsetWidth,
		} );
	}

	getVisibleData( data, orderedKeys ) {
		const visibleKeys = orderedKeys.filter( ( d ) => d.visible );
		return data.map( ( d ) => {
			const newRow = { date: d.date };
			visibleKeys.forEach( ( row ) => {
				newRow[ row.key ] = d[ row.key ];
			} );
			return newRow;
		} );
	}

	setInterval( interval ) {
		const { path, query } = this.props;
		updateQueryString( { interval }, path, query );
	}

	renderIntervalSelector() {
		const { interval, allowedIntervals } = this.props;
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
			<div className="woocommerce-chart__interval-select">
				<SelectControl
					value={ interval }
					options={ allowedIntervals.map( ( allowedInterval ) => ( {
						value: allowedInterval,
						label: intervalLabels[ allowedInterval ],
					} ) ) }
					onChange={ this.setInterval }
				/>
			</div>
		);
	}

	getChartHeight() {
		const { isViewportLarge, isViewportMobile } = this.props;

		if ( isViewportMobile ) {
			return 180;
		}

		if ( isViewportLarge ) {
			return 300;
		}

		return 220;
	}

	getLegendPosition() {
		const { legendPosition, mode, isViewportWide } = this.props;
		if ( legendPosition ) {
			return legendPosition;
		}
		if ( isViewportWide && mode === 'time-comparison' ) {
			return 'top';
		}
		if ( isViewportWide && mode === 'item-comparison' ) {
			return 'side';
		}
		return 'bottom';
	}

	render() {
		const { focusedKeys, visibleKeys, width } = this.state;
		const {
			baseValue,
			chartType,
			data,
			dateParser,
			emptyMessage,
			filterParam,
			interactiveLegend,
			interval,
			isRequesting,
			isViewportLarge,
			itemsLabel,
			mode,
			query,
			screenReaderFormat,
			showHeaderControls,
			title,
			tooltipLabelFormat,
			tooltipValueFormat,
			tooltipTitle,
			valueType,
			xFormat,
			x2Format,
			yBelow1Format,
			yFormat,
		} = this.props;
		const selectedIds = filterParam
			? getIdsFromQuery( query[ filterParam ] )
			: [];
		const orderedKeys = this.getOrderedKeys(
			focusedKeys,
			visibleKeys,
			selectedIds
		);

		const visibleData = isRequesting
			? null
			: this.getVisibleData( data, orderedKeys );

		const legendPosition = this.getLegendPosition();
		const legendDirection = legendPosition === 'top' ? 'row' : 'column';
		const chartDirection = legendPosition === 'side' ? 'row' : 'column';

		const chartHeight = this.getChartHeight();
		const legend =
			legendPosition !== 'hidden' && isRequesting ? null : (
				<D3Legend
					colorScheme={ d3InterpolateViridis }
					data={ orderedKeys }
					handleLegendHover={ this.handleLegendHover }
					handleLegendToggle={ this.handleLegendToggle }
					interactive={ interactiveLegend }
					legendDirection={ legendDirection }
					legendValueFormat={ tooltipValueFormat }
					totalLabel={ sprintf( itemsLabel, orderedKeys.length ) }
				/>
			);
		const margin = {
			bottom: 50,
			left: 80,
			right: 30,
			top: 0,
		};

		let d3chartYFormat = yFormat;
		let d3chartYBelow1Format = yBelow1Format;
		if ( ! yFormat ) {
			switch ( valueType ) {
				case 'average':
					d3chartYFormat = ',.0f';
					break;
				case 'currency':
					d3chartYFormat = '$.3~s';
					d3chartYBelow1Format = '$.3~f';
					break;
				case 'number':
					d3chartYFormat = ',.0f';
					break;
			}
		}
		return (
			<div className="woocommerce-chart">
				{ showHeaderControls && (
					<div className="woocommerce-chart__header">
						<H className="woocommerce-chart__title">{ title }</H>
						{ legendPosition === 'top' && legend }
						{ this.renderIntervalSelector() }
						<NavigableMenu
							className="woocommerce-chart__types"
							orientation="horizontal"
							role="menubar"
						>
							<Button
								className={ classNames(
									'woocommerce-chart__type-button',
									{
										'woocommerce-chart__type-button-selected':
											chartType === 'line',
									}
								) }
								title={ __( 'Line chart', 'woocommerce' ) }
								aria-checked={ chartType === 'line' }
								role="menuitemradio"
								tabIndex={ chartType === 'line' ? 0 : -1 }
								onClick={ partial(
									this.handleTypeToggle,
									'line'
								) }
							>
								<LineGraphIcon />
							</Button>
							<Button
								className={ classNames(
									'woocommerce-chart__type-button',
									{
										'woocommerce-chart__type-button-selected':
											chartType === 'bar',
									}
								) }
								title={ __( 'Bar chart', 'woocommerce' ) }
								aria-checked={ chartType === 'bar' }
								role="menuitemradio"
								tabIndex={ chartType === 'bar' ? 0 : -1 }
								onClick={ partial(
									this.handleTypeToggle,
									'bar'
								) }
							>
								<StatsAltIcon />
							</Button>
						</NavigableMenu>
					</div>
				) }
				<Section component={ false }>
					<div
						className={ classNames(
							'woocommerce-chart__body',
							`woocommerce-chart__body-${ chartDirection }`
						) }
						ref={ this.chartBodyRef }
					>
						{ legendPosition === 'side' && legend }
						{ isRequesting && (
							<Fragment>
								<span className="screen-reader-text">
									{ __(
										'Your requested data is loading',
										'woocommerce'
									) }
								</span>
								<ChartPlaceholder height={ chartHeight } />
							</Fragment>
						) }
						{ ! isRequesting && width > 0 && (
							<D3Chart
								baseValue={ baseValue }
								chartType={ chartType }
								colorScheme={ d3InterpolateViridis }
								data={ visibleData }
								dateParser={ dateParser }
								height={ chartHeight }
								emptyMessage={ emptyMessage }
								interval={ interval }
								margin={ margin }
								mode={ mode }
								orderedKeys={ orderedKeys }
								screenReaderFormat={ screenReaderFormat }
								tooltipLabelFormat={ tooltipLabelFormat }
								tooltipValueFormat={ tooltipValueFormat }
								tooltipPosition={
									isViewportLarge ? 'over' : 'below'
								}
								tooltipTitle={ tooltipTitle }
								valueType={ valueType }
								width={
									chartDirection === 'row'
										? width - 320
										: width
								}
								xFormat={ xFormat }
								x2Format={ x2Format }
								yBelow1Format={ d3chartYBelow1Format }
								yFormat={ d3chartYFormat }
							/>
						) }
					</div>
					{ legendPosition === 'bottom' && (
						<div className="woocommerce-chart__footer">
							{ legend }
						</div>
					) }
				</Section>
			</div>
		);
	}
}

Chart.propTypes = {
	/**
	 * Allowed intervals to show in a dropdown.
	 */
	allowedIntervals: PropTypes.array,
	/**
	 * Base chart value. If no data value is different than the baseValue, the
	 * `emptyMessage` will be displayed if provided.
	 */
	baseValue: PropTypes.number,
	/**
	 * Chart type of either `line` or `bar`.
	 */
	chartType: PropTypes.oneOf( [ 'bar', 'line' ] ),
	/**
	 * An array of data.
	 */
	data: PropTypes.array.isRequired,
	/**
	 * Format to parse dates into d3 time format
	 */
	dateParser: PropTypes.string.isRequired,
	/**
	 * The message to be displayed if there is no data to render. If no message is provided,
	 * nothing will be displayed.
	 */
	emptyMessage: PropTypes.string,
	/**
	 * Name of the param used to filter items. If specified, it will be used, in combination
	 * with query, to detect which elements are being used by the current filter and must be
	 * displayed even if their value is 0.
	 */
	filterParam: PropTypes.string,
	/**
	 * Label describing the legend items.
	 */
	itemsLabel: PropTypes.string,
	/**
	 * `item-comparison` (default) or `time-comparison`, this is used to generate correct
	 * ARIA properties.
	 */
	mode: PropTypes.oneOf( [ 'item-comparison', 'time-comparison' ] ),
	/**
	 * Current path
	 */
	path: PropTypes.string,
	/**
	 * The query string represented in object form
	 */
	query: PropTypes.object,
	/**
	 * Whether the legend items can be activated/deactivated.
	 */
	interactiveLegend: PropTypes.bool,
	/**
	 * Interval specification (hourly, daily, weekly etc).
	 */
	interval: PropTypes.oneOf( [
		'hour',
		'day',
		'week',
		'month',
		'quarter',
		'year',
	] ),
	/**
	 * Information about the currently selected interval, and set of allowed intervals for the chart. See `getIntervalsForQuery`.
	 */
	intervalData: PropTypes.object,
	/**
	 * Render a chart placeholder to signify an in-flight data request.
	 */
	isRequesting: PropTypes.bool,
	/**
	 * Position the legend must be displayed in. If it's not defined, it's calculated
	 * depending on the viewport width and the mode.
	 */
	legendPosition: PropTypes.oneOf( [ 'bottom', 'side', 'top', 'hidden' ] ),
	/**
	 * Values to overwrite the legend totals. If not defined, the sum of all line values will be used.
	 */
	legendTotals: PropTypes.object,
	/**
	 * A datetime formatting string or overriding function to format the screen reader labels.
	 */
	screenReaderFormat: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.func,
	] ),
	/**
	 * Wether header UI controls must be displayed.
	 */
	showHeaderControls: PropTypes.bool,
	/**
	 * A title describing this chart.
	 */
	title: PropTypes.string,
	/**
	 * A datetime formatting string or overriding function to format the tooltip label.
	 */
	tooltipLabelFormat: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.func,
	] ),
	/**
	 * A number formatting string or function to format the value displayed in the tooltips.
	 */
	tooltipValueFormat: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.func,
	] ),
	/**
	 * A string to use as a title for the tooltip. Takes preference over `tooltipLabelFormat`.
	 */
	tooltipTitle: PropTypes.string,
	/**
	 * What type of data is to be displayed? Number, Average, String?
	 */
	valueType: PropTypes.string,
	/**
	 * A datetime formatting string, passed to d3TimeFormat.
	 */
	xFormat: PropTypes.string,
	/**
	 * A datetime formatting string, passed to d3TimeFormat.
	 */
	x2Format: PropTypes.string,
	/**
	 * A number formatting string, passed to d3Format.
	 */
	yBelow1Format: PropTypes.string,
	/**
	 * A number formatting string, passed to d3Format.
	 */
	yFormat: PropTypes.string,
	/**
	 * A currency object passed to d3Format.
	 */
	currency: PropTypes.object,
};

Chart.defaultProps = {
	baseValue: 0,
	chartType: 'line',
	data: [],
	dateParser: '%Y-%m-%dT%H:%M:%S',
	interactiveLegend: true,
	interval: 'day',
	isRequesting: false,
	mode: 'time-comparison',
	screenReaderFormat: '%B %-d, %Y',
	showHeaderControls: true,
	tooltipLabelFormat: '%B %-d, %Y',
	tooltipValueFormat: ',',
	xFormat: '%d',
	x2Format: '%b %Y',
	currency: {
		symbol: '$',
		symbolPosition: 'left',
		decimalSeparator: '.',
		thousandSeparator: ',',
	},
};

export default withViewportMatch( {
	isViewportMobile: '< medium',
	isViewportLarge: '>= large',
	isViewportWide: '>= wide',
} )( Chart );
