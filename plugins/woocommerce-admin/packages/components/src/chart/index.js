/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';
import { Component, createRef, Fragment } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { formatDefaultLocale as d3FormatDefaultLocale } from 'd3-format';
import { get, isEqual, partial } from 'lodash';
import Gridicon from 'gridicons';
import { IconButton, NavigableMenu, SelectControl } from '@wordpress/components';
import { interpolateViridis as d3InterpolateViridis } from 'd3-scale-chromatic';
import PropTypes from 'prop-types';
import { withViewportMatch } from '@wordpress/viewport';

/**
 * WooCommerce dependencies
 */
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ChartPlaceholder from './placeholder';
import { H, Section } from '../section';
import { D3Chart, D3Legend } from './d3chart';

d3FormatDefaultLocale( {
	decimal: '.',
	thousands: ',',
	grouping: [ 3 ],
	currency: [ decodeEntities( get( wcSettings, 'currency.symbol', '' ) ), '' ],
} );

function getOrderedKeys( props, previousOrderedKeys = [] ) {
	const updatedKeys = [
		...new Set(
			props.data.reduce( ( accum, curr ) => {
				Object.keys( curr ).forEach( key => key !== 'date' && accum.push( key ) );
				return accum;
			}, [] )
		),
	].map( key => {
		const previousKey = previousOrderedKeys.find( item => key === item.key );
		return {
			key,
			total: props.data.reduce( ( a, c ) => a + c[ key ].value, 0 ),
			visible: previousKey ? previousKey.visible : true,
			focus: true,
		};
	} );
	if ( props.mode === 'item-comparison' ) {
		updatedKeys.sort( ( a, b ) => b.total - a.total );
	}
	return updatedKeys;
}

/**
 * A chart container using d3, to display timeseries data with an interactive legend.
 */
class Chart extends Component {
	constructor( props ) {
		super( props );
		this.chartBodyRef = createRef();
		this.state = {
			data: props.data,
			orderedKeys: getOrderedKeys( props ),
			visibleData: [ ...props.data ],
			width: 0,
		};
		this.handleTypeToggle = this.handleTypeToggle.bind( this );
		this.handleLegendToggle = this.handleLegendToggle.bind( this );
		this.handleLegendHover = this.handleLegendHover.bind( this );
		this.updateDimensions = this.updateDimensions.bind( this );
		this.getVisibleData = this.getVisibleData.bind( this );
		this.setInterval = this.setInterval.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const { data } = this.props;
		if ( ! isEqual( [ ...data ].sort(), [ ...prevProps.data ].sort() ) ) {
			/**
			 * Only update the orderedKeys when data is present so that
			 * selection may persist while requesting new data.
			 */
			const orderedKeys = data.length
				? getOrderedKeys( this.props, this.state.orderedKeys )
				: this.state.orderedKeys;
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				orderedKeys,
				visibleData: this.getVisibleData( data, orderedKeys ),
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	componentDidMount() {
		this.updateDimensions();
		window.addEventListener( 'resize', this.updateDimensions );
	}

	componentWillUnmount() {
		window.removeEventListener( 'resize', this.updateDimensions );
	}

	handleTypeToggle( type ) {
		if ( this.props.type !== type ) {
			const { path, query } = this.props;
			updateQueryString( { type }, path, query );
		}
	}

	handleLegendToggle( event ) {
		const { data, mode } = this.props;
		if ( mode ) {
			return;
		}
		const orderedKeys = this.state.orderedKeys.map( d => ( {
			...d,
			visible: d.key === event.target.id ? ! d.visible : d.visible,
		} ) );
		const copyEvent = { ...event }; // can't pass a synthetic event into the hover handler
		this.setState(
			{
				orderedKeys,
				visibleData: this.getVisibleData( data, orderedKeys ),
			},
			() => {
				this.handleLegendHover( copyEvent );
			}
		);
	}

	handleLegendHover( event ) {
		const hoverTarget = this.state.orderedKeys.filter( d => d.key === event.target.id )[ 0 ];
		this.setState( {
			orderedKeys: this.state.orderedKeys.map( d => {
				let enterFocus = d.key === event.target.id ? true : false;
				enterFocus = ! hoverTarget.visible ? true : enterFocus;
				return {
					...d,
					focus: event.type === 'mouseleave' || event.type === 'blur' ? true : enterFocus,
				};
			} ),
		} );
	}

	updateDimensions() {
		this.setState( {
			width: this.chartBodyRef.current.offsetWidth,
		} );
	}

	getVisibleData( data, orderedKeys ) {
		const visibleKeys = orderedKeys.filter( d => d.visible );
		return data.map( d => {
			const newRow = { date: d.date };
			visibleKeys.forEach( row => {
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
			hour: __( 'By hour', 'wc-admin' ),
			day: __( 'By day', 'wc-admin' ),
			week: __( 'By week', 'wc-admin' ),
			month: __( 'By month', 'wc-admin' ),
			quarter: __( 'By quarter', 'wc-admin' ),
			year: __( 'By year', 'wc-admin' ),
		};

		return (
			<SelectControl
				className="woocommerce-chart__interval-select"
				value={ interval }
				options={ allowedIntervals.map( allowedInterval => ( {
					value: allowedInterval,
					label: intervalLabels[ allowedInterval ],
				} ) ) }
				onChange={ this.setInterval }
			/>
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

	render() {
		const { orderedKeys, visibleData, width } = this.state;
		const {
			dateParser,
			itemsLabel,
			mode,
			isViewportLarge,
			isViewportWide,
			title,
			tooltipLabelFormat,
			tooltipValueFormat,
			tooltipTitle,
			xFormat,
			x2Format,
			interval,
			valueType,
			type,
			isRequesting,
		} = this.props;
		let { yFormat } = this.props;
		const legendDirection = mode === 'time-comparison' && isViewportWide ? 'row' : 'column';
		const chartDirection = ( mode === 'item-comparison' ) && isViewportWide ? 'row' : 'column';

		const chartHeight = this.getChartHeight();
		const legend = (
			<D3Legend
				colorScheme={ d3InterpolateViridis }
				data={ orderedKeys }
				handleLegendHover={ this.handleLegendHover }
				handleLegendToggle={ this.handleLegendToggle }
				interactive={ mode !== 'block' }
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

		switch ( valueType ) {
			case 'average':
				yFormat = '.0f';
				break;
			case 'currency':
				yFormat = '$.3s';
				break;
			case 'number':
				yFormat = '.0f';
				break;
		}
		return (
			<div className="woocommerce-chart">
				{ mode !== 'block' &&
					<div className="woocommerce-chart__header">
						<H className="woocommerce-chart__title">{ title }</H>
						{ isViewportWide && legendDirection === 'row' && legend }
						{ this.renderIntervalSelector() }
						<NavigableMenu
							className="woocommerce-chart__types"
							orientation="horizontal"
							role="menubar"
						>
							<IconButton
								className={ classNames( 'woocommerce-chart__type-button', {
									'woocommerce-chart__type-button-selected': type === 'line',
								} ) }
								icon={ <Gridicon icon="line-graph" /> }
								title={ __( 'Line chart', 'wc-admin' ) }
								aria-checked={ type === 'line' }
								role="menuitemradio"
								tabIndex={ type === 'line' ? 0 : -1 }
								onClick={ partial( this.handleTypeToggle, 'line' ) }
							/>
							<IconButton
								className={ classNames( 'woocommerce-chart__type-button', {
									'woocommerce-chart__type-button-selected': type === 'bar',
								} ) }
								icon={ <Gridicon icon="stats-alt" /> }
								title={ __( 'Bar chart', 'wc-admin' ) }
								aria-checked={ type === 'bar' }
								role="menuitemradio"
								tabIndex={ type === 'bar' ? 0 : -1 }
								onClick={ partial( this.handleTypeToggle, 'bar' ) }
							/>
						</NavigableMenu>
					</div>
				}
				<Section component={ false }>
					<div
						className={ classNames(
							'woocommerce-chart__body',
							`woocommerce-chart__body-${ chartDirection }`
						) }
						ref={ this.chartBodyRef }
					>
						{ isViewportWide && legendDirection === 'column' && mode !== 'block' && legend }
						{ isRequesting && (
							<Fragment>
								<span className="screen-reader-text">
									{ __( 'Your requested data is loading', 'wc-admin' ) }
								</span>
								<ChartPlaceholder height={ chartHeight } />
							</Fragment>
						) }
						{ ! isRequesting &&
							width > 0 && (
								<D3Chart
									colorScheme={ d3InterpolateViridis }
									data={ visibleData }
									dateParser={ dateParser }
									height={ chartHeight }
									interval={ interval }
									margin={ margin }
									mode={ mode === 'block' ? 'item-comparison' : mode }
									orderedKeys={ orderedKeys }
									tooltipLabelFormat={ tooltipLabelFormat }
									tooltipValueFormat={ tooltipValueFormat }
									tooltipPosition={ isViewportLarge ? 'over' : 'below' }
									tooltipTitle={ tooltipTitle }
									type={ type }
									width={ chartDirection === 'row' ? width - 320 : width }
									xFormat={ xFormat }
									x2Format={ x2Format }
									yFormat={ yFormat }
									valueType={ valueType }
								/>
							) }
					</div>
					{ ( ! isViewportWide || mode === 'block' ) && <div className="woocommerce-chart__footer">{ legend }</div> }
				</Section>
			</div>
		);
	}
}

Chart.propTypes = {
	/**
	 * An array of data.
	 */
	data: PropTypes.array.isRequired,
	/**
	 * Format to parse dates into d3 time format
	 */
	dateParser: PropTypes.string.isRequired,
	/**
	 * Current path
	 */
	path: PropTypes.string,
	/**
	 * The query string represented in object form
	 */
	query: PropTypes.object,
	/**
	 * A datetime formatting string or overriding function to format the tooltip label.
	 */
	tooltipLabelFormat: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	/**
	 * A number formatting string or function to format the value displayed in the tooltips.
	 */
	tooltipValueFormat: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	/**
	 * A string to use as a title for the tooltip. Takes preference over `tooltipLabelFormat`.
	 */
	tooltipTitle: PropTypes.string,
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
	yFormat: PropTypes.string,
	/**
	 * `item-comparison` (default) or `time-comparison`, this is used to generate correct
	 * ARIA properties.
	 */
	mode: PropTypes.oneOf( [ 'block', 'item-comparison', 'time-comparison' ] ),
	/**
	 * A title describing this chart.
	 */
	title: PropTypes.string,
	/**
	 * Chart type of either `line` or `bar`.
	 */
	type: PropTypes.oneOf( [ 'bar', 'line' ] ),
	/**
	 * Information about the currently selected interval, and set of allowed intervals for the chart. See `getIntervalsForQuery`.
	 */
	intervalData: PropTypes.object,
	/**
	 * Interval specification (hourly, daily, weekly etc).
	 */
	interval: PropTypes.oneOf( [ 'hour', 'day', 'week', 'month', 'quarter', 'year' ] ),
	/**
	 * Allowed intervals to show in a dropdown.
	 */
	allowedIntervals: PropTypes.array,
	/**
	 * What type of data is to be displayed? Number, Average, String?
	 */
	valueType: PropTypes.string,
	/**
	 * Render a chart placeholder to signify an in-flight data request.
	 */
	isRequesting: PropTypes.bool,
};

Chart.defaultProps = {
	data: [],
	dateParser: '%Y-%m-%dT%H:%M:%S',
	tooltipLabelFormat: '%B %d, %Y',
	tooltipValueFormat: ',',
	xFormat: '%d',
	x2Format: '%b %Y',
	yFormat: '$.3s',
	mode: 'time-comparison',
	type: 'line',
	interval: 'day',
	isRequesting: false,
};

export default withViewportMatch( {
	isViewportMobile: '< medium',
	isViewportLarge: '>= large',
	isViewportWide: '>= wide',
} )( Chart );
