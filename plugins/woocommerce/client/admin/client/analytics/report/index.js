/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import PropTypes from 'prop-types';
import { find } from 'lodash';
import { getQuery, getSearchWords } from '@woocommerce/navigation';
import { searchItemsByString, ITEMS_STORE_NAME } from '@woocommerce/data';
import { AnalyticsError } from '@woocommerce/components';
import {
	CurrencyContext,
	getFilteredCurrencyInstance,
} from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import './style.scss';
import { NoMatch } from '~/layout/NoMatch';
import getReports from './get-reports';

/**
 * An object defining a chart.
 *
 * @typedef {Object} chart
 * @property {string}                key     Chart slug.
 * @property {string}                label   Chart label.
 * @property {string}                order   Default way to order the `orderby` property.
 * @property {string}                orderby Column by which to order.
 * @property {('number'|'currency')} type    Specify the type of number.
 */

/**
 * An object defining a set of report filters.
 *
 * @typedef {Object} filter
 * @property {string}         label        Label describing the set of filters.
 * @property {string}         param        Url query param this set of filters operates on.
 * @property {Array.<string>} staticParams Array of `param` that remain constant when other params are manipulated.
 * @property {Function}       showFilters  A function with url query as an argument returning a Boolean depending on whether or not the filters should be shown.
 * @property {Array}          filters      An array of filter objects.
 */

/**
 * The Customers Report will not have the `report` param supplied by the router/
 * because it no longer exists under the path `/analytics/:report`. Use `props.path`/
 * instead to determine if the Customers Report is being rendered.
 *
 * @param {Object} args
 * @param {Object} args.params - url parameters
 * @param {string} args.path
 * @return {string} - report parameter
 */
const getReportParam = ( { params, path } ) => {
	return params.report || path.replace( /^\/+/, '' );
};

class Report extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			hasError: false,
		};
	}

	componentDidCatch( error ) {
		this.setState( {
			hasError: true,
		} );
		/* eslint-disable no-console */
		console.warn( error );
		/* eslint-enable no-console */
	}

	render() {
		if ( this.state.hasError ) {
			return null;
		}

		const { isError } = this.props;

		if ( isError ) {
			return <AnalyticsError />;
		}

		const reportParam = getReportParam( this.props );

		const report = find( getReports(), { report: reportParam } );
		if ( ! report ) {
			return <NoMatch />;
		}
		const Container = report.component;
		return (
			<CurrencyContext.Provider
				value={ getFilteredCurrencyInstance( getQuery() ) }
			>
				<Container { ...this.props } />
			</CurrencyContext.Provider>
		);
	}
}

Report.propTypes = {
	params: PropTypes.object.isRequired,
};

export default compose(
	withSelect( ( select, props ) => {
		const query = getQuery();
		const { search } = query;

		/* eslint @wordpress/no-unused-vars-before-return: "off" */
		const itemsSelector = select( ITEMS_STORE_NAME );

		if ( ! search ) {
			return {};
		}

		const report = getReportParam( props );
		const searchWords = getSearchWords( query );
		// Single category view in Categories Report uses the products endpoint, so search must also.
		const mappedReport =
			report === 'categories' && query.filter === 'single_category'
				? 'products'
				: report;
		const itemsResult = searchItemsByString(
			itemsSelector,
			mappedReport,
			searchWords,
			{
				per_page: 100,
			}
		);
		const { isError, isRequesting, items } = itemsResult;
		const ids = Object.keys( items );
		if ( ! ids.length ) {
			return {
				isError,
				isRequesting,
			};
		}

		return {
			isError,
			isRequesting,
			query: {
				...props.query,
				[ mappedReport ]: ids.join( ',' ),
			},
		};
	} )
)( Report );
