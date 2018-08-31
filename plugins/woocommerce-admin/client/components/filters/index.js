/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { noop } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import AdvancedFilters from './advanced';
import Card from 'components/card';
import DatePicker from './date';
import FilterPicker from './filter';
import { H, Section } from 'layout/section';
import './style.scss';

/**
 * Add a collection of report filters to a page. This uses `DatePicker` & `FilterPicker` for the "basic" filters, and `AdvancedFilters`
 * or a comparison card if "advanced" or "compare" are picked from `FilterPicker`.
 *
 * @return { object } -
 */
const ReportFilters = ( { advancedConfig, filters, query, path } ) => {
	let advancedCard = false;
	switch ( query.filter ) {
		case 'compare':
			advancedCard = (
				<Card
					title={ __( 'Compare Products', 'wc-admin' ) }
					className="woocommerce-filters__compare"
				>
					<div className="woocommerce-filters__compare-body">
						<input type="search" />
						<div>Tokens</div>
					</div>
					<div className="woocommerce-filters__compare-footer">
						<Button onClick={ noop } isDefault>
							{ __( 'Compare', 'wc-admin' ) }
						</Button>
					</div>
				</Card>
			);
			break;
		case 'advanced':
			advancedCard = (
				<AdvancedFilters
					config={ advancedConfig }
					filterTitle={ __( 'Orders', 'wc-admin' ) }
					path={ path }
					query={ query }
				/>
			);
			break;
	}

	return (
		<Fragment>
			<H className="screen-reader-text">{ __( 'Filters', 'wc-admin' ) }</H>
			<Section component="div" className="woocommerce-filters">
				<div className="woocommerce-filters__basic-filters">
					<DatePicker key={ JSON.stringify( query ) } query={ query } path={ path } />
					{ !! filters.length && (
						<FilterPicker filters={ filters } query={ query } path={ path } />
					) }
				</div>
				{ false !== advancedCard && (
					<div className="woocommerce-filters__advanced-filters">{ advancedCard }</div>
				) }
			</Section>
		</Fragment>
	);
};

ReportFilters.propTypes = {
	/**
	 * Config option passed through to `AdvancedFilters`
	 */
	advancedConfig: PropTypes.object,
	/**
	 * Config option passed through to `FilterPicker` - if not used, `FilterPicker` is not displayed.
	 */
	filters: PropTypes.array,
	/**
	 * The `path` parameter supplied by React-Router
	 */
	path: PropTypes.string.isRequired,
	/**
	 * The query string represented in object form
	 */
	query: PropTypes.object,
};

ReportFilters.defaultProps = {
	advancedConfig: {},
	filters: [],
	query: {},
};

export default ReportFilters;
