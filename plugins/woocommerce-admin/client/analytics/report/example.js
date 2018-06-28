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
import { SummaryList, SummaryNumber } from 'components/summary';

export default class extends Component {
	render() {
		return (
			<Fragment>
				<Header
					sections={ [
						[ '/analytics', __( 'Analytics', 'woo-dash' ) ],
						__( 'Report Title', 'woo-dash' ),
					] }
				/>
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
					<SummaryNumber value={ '$66.39' } label={ __( 'Tax', 'woo-dash' ) } />
				</SummaryList>
			</Fragment>
		);
	}
}
