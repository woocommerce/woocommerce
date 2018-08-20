/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Header from 'layout/header';
import { SummaryList, SummaryNumber } from '@woocommerce/components';

export default class extends Component {
	render() {
		return (
			<Fragment>
				<Header
					sections={ [
						[ '/analytics', __( 'Analytics', 'wc-admin' ) ],
						__( 'Report Title', 'wc-admin' ),
					] }
				/>
				<h2>One Data Point</h2>
				<SummaryList>
					<SummaryNumber
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						value={ '$829.40' }
						prevValue={ '$785.90' }
						delta={ 5.5 }
						selected
					/>
				</SummaryList>

				<h2>Two Data Points</h2>
				<SummaryList>
					<SummaryNumber
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						value={ '$829.40' }
						prevValue={ '$785.90' }
						delta={ 5.5 }
					/>
					<SummaryNumber
						label={ __( 'Refunds', 'wc-admin' ) }
						value={ '$24.00' }
						prevValue={ '$26.40' }
						delta={ -10 }
						reverseTrend
						selected
					/>
				</SummaryList>

				<h2>Three Data Points</h2>
				<SummaryList>
					<SummaryNumber
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						value={ '$829.40' }
						prevValue={ '$785.90' }
						delta={ 5.5 }
					/>
					<SummaryNumber
						label={ __( 'Refunds', 'wc-admin' ) }
						value={ '$24.00' }
						prevValue={ '$26.40' }
						delta={ -10 }
						reverseTrend
						selected
					/>
					<SummaryNumber
						label={ __( 'Coupons', 'wc-admin' ) }
						value={ '$40.00' }
						prevValue={ '$40.00' }
						delta={ 0 }
					/>
				</SummaryList>

				<h2>Four Data Points</h2>
				<SummaryList>
					<SummaryNumber
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						value={ '$829.40' }
						prevValue={ '$785.90' }
						delta={ 5.5 }
					/>
					<SummaryNumber
						label={ __( 'Refunds', 'wc-admin' ) }
						value={ '$24.00' }
						prevValue={ '$26.40' }
						delta={ -10 }
						reverseTrend
						selected
					/>
					<SummaryNumber label={ __( 'Coupons', 'wc-admin' ) } value={ '$45.00' } />
					<SummaryNumber
						label={ __( 'Taxes', 'wc-admin' ) }
						value={ '$84.73' }
						prevValue={ '$92.30' }
						delta={ -8.9 }
					/>
				</SummaryList>

				<h2>Five Data Points</h2>
				<SummaryList>
					<SummaryNumber
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						value={ '$829.40' }
						prevValue={ '$785.90' }
						delta={ 5.5 }
					/>
					<SummaryNumber
						label={ __( 'Refunds', 'wc-admin' ) }
						value={ '$24.00' }
						prevValue={ '$26.40' }
						delta={ -10 }
						reverseTrend
						selected
					/>
					<SummaryNumber
						label={ __( 'Coupons', 'wc-admin' ) }
						value={ '$40.00' }
						prevValue={ '$40.00' }
						delta={ 0 }
					/>
					<SummaryNumber
						label={ __( 'Taxes', 'wc-admin' ) }
						value={ '$84.73' }
						prevValue={ '$92.30' }
						delta={ -8.9 }
					/>
					<SummaryNumber
						label={ __( 'Average Order Value', 'wc-admin' ) }
						value={ '$25.00' }
						prevValue={ '$30.00' }
						delta={ -20 }
					/>
				</SummaryList>

				<h2>Six Data Points</h2>
				<SummaryList>
					<SummaryNumber
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						value={ '$829.40' }
						prevValue={ '$785.90' }
						delta={ 5.5 }
					/>
					<SummaryNumber
						label={ __( 'Refunds', 'wc-admin' ) }
						value={ '$24.00' }
						prevValue={ '$26.40' }
						delta={ -10 }
						reverseTrend
						selected
					/>
					<SummaryNumber
						label={ __( 'Coupons', 'wc-admin' ) }
						value={ '$40.00' }
						prevValue={ '$40.00' }
						delta={ 0 }
					/>
					<SummaryNumber
						label={ __( 'Taxes', 'wc-admin' ) }
						value={ '$84.73' }
						prevValue={ '$92.30' }
						delta={ -8.9 }
					/>
					<SummaryNumber
						label={ __( 'Average Order Value', 'wc-admin' ) }
						value={ '$25.00' }
						prevValue={ '$30.00' }
						delta={ -20 }
					/>
					<SummaryNumber
						label={ __( 'Shipping', 'wc-admin' ) }
						value={ '$75.00' }
						prevValue={ '$60.00' }
						delta={ 25 }
					/>
				</SummaryList>

				<h2>Seven Data Points</h2>
				<SummaryList>
					<SummaryNumber
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						value={ '$829.40' }
						prevValue={ '$785.90' }
						delta={ 5.5 }
					/>
					<SummaryNumber
						label={ __( 'Refunds', 'wc-admin' ) }
						value={ '$24.00' }
						prevValue={ '$26.40' }
						delta={ -10 }
						reverseTrend
						selected
					/>
					<SummaryNumber
						label={ __( 'Coupons', 'wc-admin' ) }
						value={ '$40.00' }
						prevValue={ '$40.00' }
						delta={ 0 }
					/>
					<SummaryNumber
						label={ __( 'Taxes', 'wc-admin' ) }
						value={ '$84.73' }
						prevValue={ '$92.30' }
						delta={ -8.9 }
					/>
					<SummaryNumber
						label={ __( 'Average Order Value', 'wc-admin' ) }
						value={ '$25.00' }
						prevValue={ '$30.00' }
						delta={ -20 }
					/>
					<SummaryNumber
						label={ __( 'Shipping', 'wc-admin' ) }
						value={ '$75.00' }
						prevValue={ '$60.00' }
						delta={ 25 }
					/>
					<SummaryNumber
						label={ __( 'Coupons', 'wc-admin' ) }
						value={ '$40.00' }
						prevValue={ '$40.00' }
						delta={ 0 }
					/>
				</SummaryList>

				<h2>Eight Data Points</h2>
				<SummaryList>
					<SummaryNumber
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						value={ '$829.40' }
						prevValue={ '$785.90' }
						delta={ 5.5 }
					/>
					<SummaryNumber
						label={ __( 'Refunds', 'wc-admin' ) }
						value={ '$24.00' }
						prevValue={ '$26.40' }
						delta={ -10 }
						reverseTrend
						selected
					/>
					<SummaryNumber
						label={ __( 'Coupons', 'wc-admin' ) }
						value={ '$40.00' }
						prevValue={ '$40.00' }
						delta={ 0 }
					/>
					<SummaryNumber
						label={ __( 'Taxes', 'wc-admin' ) }
						value={ '$84.73' }
						prevValue={ '$92.30' }
						delta={ -8.9 }
					/>
					<SummaryNumber
						label={ __( 'Average Order Value', 'wc-admin' ) }
						value={ '$25.00' }
						prevValue={ '$30.00' }
						delta={ -20 }
					/>
					<SummaryNumber
						label={ __( 'Shipping', 'wc-admin' ) }
						value={ '$75.00' }
						prevValue={ '$60.00' }
						delta={ 25 }
					/>
					<SummaryNumber
						label={ __( 'Coupons', 'wc-admin' ) }
						value={ '$40.00' }
						prevValue={ '$40.00' }
						delta={ 0 }
					/>
					<SummaryNumber
						label={ __( 'Taxes', 'wc-admin' ) }
						value={ '$84.73' }
						prevValue={ '$92.30' }
						delta={ -8.9 }
					/>
				</SummaryList>

				<h2>Nine Data Points</h2>
				<SummaryList>
					<SummaryNumber
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						value={ '$829.40' }
						prevValue={ '$785.90' }
						delta={ 5.5 }
					/>
					<SummaryNumber
						label={ __( 'Refunds', 'wc-admin' ) }
						value={ '$24.00' }
						prevValue={ '$26.40' }
						delta={ -10 }
						reverseTrend
						selected
					/>
					<SummaryNumber
						label={ __( 'Coupons', 'wc-admin' ) }
						value={ '$40.00' }
						prevValue={ '$40.00' }
						delta={ 0 }
					/>
					<SummaryNumber
						label={ __( 'Taxes', 'wc-admin' ) }
						value={ '$84.73' }
						prevValue={ '$92.30' }
						delta={ -8.9 }
					/>
					<SummaryNumber
						label={ __( 'Average Order Value', 'wc-admin' ) }
						value={ '$25.00' }
						prevValue={ '$30.00' }
						delta={ -20 }
					/>
					<SummaryNumber
						label={ __( 'Shipping', 'wc-admin' ) }
						value={ '$75.00' }
						prevValue={ '$60.00' }
						delta={ 25 }
					/>
					<SummaryNumber
						label={ __( 'Coupons', 'wc-admin' ) }
						value={ '$40.00' }
						prevValue={ '$40.00' }
						delta={ 0 }
					/>
					<SummaryNumber
						label={ __( 'Taxes', 'wc-admin' ) }
						value={ '$84.73' }
						prevValue={ '$92.30' }
						delta={ -8.9 }
					/>
					<SummaryNumber
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						value={ '$829.40' }
						prevValue={ '$785.90' }
						delta={ 5.5 }
					/>
				</SummaryList>

				<h2>Ten or More Data Points</h2>
				<SummaryList>
					<SummaryNumber
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						value={ '$829.40' }
						prevValue={ '$785.90' }
						delta={ 5.5 }
					/>
					<SummaryNumber
						label={ __( 'Refunds', 'wc-admin' ) }
						value={ '$24.00' }
						prevValue={ '$26.40' }
						delta={ -10 }
						reverseTrend
					/>
					<SummaryNumber
						label={ __( 'Coupons', 'wc-admin' ) }
						value={ '$40.00' }
						prevValue={ '$40.00' }
						delta={ 0 }
					/>
					<SummaryNumber
						label={ __( 'Taxes', 'wc-admin' ) }
						value={ '$84.73' }
						prevValue={ '$92.30' }
						delta={ -8.9 }
					/>
					<SummaryNumber
						label={ __( 'Average Order Value', 'wc-admin' ) }
						value={ '$25.00' }
						prevValue={ '$30.00' }
						delta={ -20 }
						selected
					/>
					<SummaryNumber
						label={ __( 'Shipping', 'wc-admin' ) }
						value={ '$75.00' }
						prevValue={ '$60.00' }
						delta={ 25 }
					/>
					<SummaryNumber
						label={ __( 'Coupons', 'wc-admin' ) }
						value={ '$40.00' }
						prevValue={ '$40.00' }
						delta={ 0 }
					/>
					<SummaryNumber
						label={ __( 'Taxes', 'wc-admin' ) }
						value={ '$84.73' }
						prevValue={ '$304,803,048.30' }
						delta={ -8.9 }
					/>
					<SummaryNumber
						label={ __( 'Average Order Value', 'wc-admin' ) }
						value={ '$25.00' }
						prevValue={ '$30.00' }
						delta={ -20 }
					/>
					<SummaryNumber
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						value={ '$829.40' }
						prevValue={ '$785.90' }
						delta={ 5.5 }
					/>
				</SummaryList>
			</Fragment>
		);
	}
}
