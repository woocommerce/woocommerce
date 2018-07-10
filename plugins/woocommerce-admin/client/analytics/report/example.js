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
						[ '/analytics', __( 'Analytics', 'wc-admin' ) ],
						__( 'Report Title', 'wc-admin' ),
					] }
				/>
				<SummaryList>
					<SummaryNumber
						value={ '$829.40' }
						label={ __( 'Gross Revenue', 'wc-admin' ) }
						delta={ 29 }
					/>
					<SummaryNumber
						value={ '$24.00' }
						label={ __( 'Refunds', 'wc-admin' ) }
						delta={ -10 }
						selected
					/>
					<SummaryNumber value={ '$49.90' } label={ __( 'Coupons', 'wc-admin' ) } delta={ 15 } />
					<SummaryNumber value={ '$66.39' } label={ __( 'Tax', 'wc-admin' ) } />
				</SummaryList>
			</Fragment>
		);
	}
}
