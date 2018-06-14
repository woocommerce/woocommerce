/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import DatePicker from 'components/date-picker';
import Header from 'components/header';
import { SummaryList, SummaryNumber } from 'components/summary';

class RevenueReport extends Component {
	render() {
		const { path, query } = this.props;
		return (
			<Fragment>
				<Header
					sections={ [
						[ '/analytics', __( 'Analytics', 'woo-dash' ) ],
						__( 'Revenue', 'woo-dash' ),
					] }
				/>
				<DatePicker query={ query } path={ path } />

				<SummaryList>
					<SummaryNumber
						value={ '$829.40' }
						label={ __( 'Gross Revenue', 'woo-dash' ) }
						delta={ 29 }
					/>
					<SummaryNumber
						value={ '$24.00' }
						label={ __( 'Refunds', 'woo-dash' ) }
						delta={ -10 }
						selected
					/>
					<SummaryNumber value={ '$49.90' } label={ __( 'Coupons', 'woo-dash' ) } delta={ 15 } />
					<SummaryNumber value={ '$66.39' } label={ __( 'Taxes', 'woo-dash' ) } />
				</SummaryList>
			</Fragment>
		);
	}
}

RevenueReport.propTypes = {
	params: PropTypes.object.isRequired,
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};

export default RevenueReport;
