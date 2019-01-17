/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withDispatch } from '@wordpress/data';
import { getCurrentDates, appendTimestamp, getDateParamsFromQuery } from '@woocommerce/date';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import moment from 'moment';
import { find } from 'lodash';

/**
 * Internal dependencies
 */
import {
	Card,
	EllipsisMenu,
	MenuItem,
	MenuTitle,
	SectionHeader,
	SummaryList,
	SummaryListPlaceholder,
	SummaryNumber,
} from '@woocommerce/components';
import withSelect from 'wc-api/with-select';
import './style.scss';
import { calculateDelta, formatValue } from 'lib/number';

class StorePerformance extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			userPrefs: props.userPrefs || [],
		};
		this.toggle = this.toggle.bind( this );
	}

	toggle( statKey ) {
		return () => {
			this.setState( state => {
				const prefs = [ ...state.userPrefs ];
				let newPrefs = [];
				if ( ! prefs.includes( statKey ) ) {
					prefs.push( statKey );
					newPrefs = prefs;
				} else {
					newPrefs = prefs.filter( pref => pref !== statKey );
				}
				this.props.updateCurrentUserData( {
					dashboard_performance_indicators: newPrefs,
				} );
				return {
					userPrefs: newPrefs,
				};
			} );
		};
	}

	renderMenu() {
		const { indicators } = this.props;
		return (
			<EllipsisMenu label={ __( 'Choose which analytics to display', 'wc-admin' ) }>
				<MenuTitle>{ __( 'Display Stats:', 'wc-admin' ) }</MenuTitle>
				{ indicators.map( ( indicator, i ) => {
					const checked = ! this.state.userPrefs.includes( indicator.stat );
					return (
						<MenuItem onInvoke={ this.toggle( indicator.stat ) } key={ i }>
							<ToggleControl
								label={ sprintf( __( 'Show %s', 'wc-admin' ), indicator.label ) }
								checked={ checked }
								onChange={ this.toggle( indicator.stat ) }
							/>
						</MenuItem>
					);
				} ) }
			</EllipsisMenu>
		);
	}

	renderList() {
		const {
			query,
			primaryRequesting,
			secondaryRequesting,
			primaryError,
			secondaryError,
			primaryData,
			secondaryData,
			userIndicators,
		} = this.props;
		if ( primaryRequesting || secondaryRequesting ) {
			return <SummaryListPlaceholder numberOfItems={ userIndicators.length } />;
		}

		if ( primaryError || secondaryError ) {
			return null;
		}

		const persistedQuery = getPersistedQuery( query );

		const { compare } = getDateParamsFromQuery( query );
		const prevLabel =
			'previous_period' === compare
				? __( 'Previous Period:', 'wc-admin' )
				: __( 'Previous Year:', 'wc-admin' );
		return (
			<SummaryList>
				{ userIndicators.map( ( indicator, i ) => {
					const primaryItem = find( primaryData.data, data => data.stat === indicator.stat );
					const secondaryItem = find( secondaryData.data, data => data.stat === indicator.stat );

					if ( ! primaryItem || ! secondaryItem ) {
						return null;
					}

					const href =
						( primaryItem._links &&
							primaryItem._links.report[ 0 ] &&
							primaryItem._links.report[ 0 ].href ) ||
						'';
					const reportUrl =
						( href && getNewPath( persistedQuery, href, { chart: primaryItem.chart } ) ) || '';
					const delta = calculateDelta( primaryItem.value, secondaryItem.value );
					const primaryValue = formatValue( primaryItem.format, primaryItem.value );
					const secondaryValue = formatValue( secondaryItem.format, secondaryItem.value );

					return (
						<SummaryNumber
							key={ i }
							href={ reportUrl }
							label={ indicator.label }
							value={ primaryValue }
							prevLabel={ prevLabel }
							prevValue={ secondaryValue }
							delta={ delta }
						/>
					);
				} ) }
			</SummaryList>
		);
	}

	render() {
		return (
			<Fragment>
				<SectionHeader title={ __( 'Store Performance', 'wc-admin' ) } menu={ this.renderMenu() } />
				<Card className="woocommerce-dashboard__store-performance">{ this.renderList() }</Card>
			</Fragment>
		);
	}
}
export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const {
			getCurrentUserData,
			getReportItems,
			getReportItemsError,
			isReportItemsRequesting,
		} = select( 'wc-api' );
		const userData = getCurrentUserData();
		const userPrefs = userData.dashboard_performance_indicators;

		const datesFromQuery = getCurrentDates( query );
		const endPrimary = datesFromQuery.primary.before;
		const endSecondary = datesFromQuery.secondary.before;

		const indicators = wcSettings.dataEndpoints.performanceIndicators;
		const userIndicators = indicators.filter( indicator => ! userPrefs.includes( indicator.stat ) );
		const statKeys = userIndicators.map( indicator => indicator.stat ).join( ',' );

		const primaryQuery = {
			after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
			before: appendTimestamp( endPrimary, endPrimary.isSame( moment(), 'day' ) ? 'now' : 'end' ),
			stats: statKeys,
		};

		const secondaryQuery = {
			after: appendTimestamp( datesFromQuery.secondary.after, 'start' ),
			before: appendTimestamp(
				endSecondary,
				endSecondary.isSame( moment(), 'day' ) ? 'now' : 'end'
			),
			stats: statKeys,
		};

		const primaryData = getReportItems( 'performance-indicators', primaryQuery );
		const primaryError = getReportItemsError( 'performance-indicators', primaryQuery ) || null;
		const primaryRequesting = isReportItemsRequesting( 'performance-indicators', primaryQuery );

		const secondaryData = getReportItems( 'performance-indicators', secondaryQuery );
		const secondaryError = getReportItemsError( 'performance-indicators', secondaryQuery ) || null;
		const secondaryRequesting = isReportItemsRequesting( 'performance-indicators', secondaryQuery );

		return {
			userPrefs,
			userIndicators,
			indicators,
			primaryData,
			primaryError,
			primaryRequesting,
			secondaryData,
			secondaryError,
			secondaryRequesting,
		};
	} ),
	withDispatch( dispatch => {
		const { updateCurrentUserData } = dispatch( 'wc-api' );

		return {
			updateCurrentUserData,
		};
	} )
)( StorePerformance );
