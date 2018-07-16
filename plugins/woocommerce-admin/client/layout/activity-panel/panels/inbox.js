/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import ActivityCard from '../activity-card';
import ActivityHeader from '../activity-header';
import { Section } from 'layout/section';

class InboxPanel extends Component {
	render() {
		return (
			<Fragment>
				<ActivityHeader title={ __( 'Inbox', 'wc-admin' ) } />
				<Section>
					<ActivityCard
						className="woocommerce-inbox-activity-card"
						title={ __( 'Accept Apple Pay using Stripe', 'wc-admin' ) }
						date={ '2018-07-12T16:23:08Z' }
						icon={ <Gridicon icon="customize" size={ 48 } /> }
						unread
					>
						{ __(
							'Your Stripe payment gateway now allows your customers to pay using Apple Pay.',
							'wc-admin'
						) }
					</ActivityCard>
					<ActivityCard
						className="woocommerce-inbox-activity-card"
						title={ __( 'Extension subscription expired', 'wc-admin' ) }
						date={ '2018-07-11T02:49:00Z' }
					>
						{ __(
							'Your subscription for WooCommerce Subscriptions expired on Jun 7th 2018.',
							'wc-admin'
						) }
					</ActivityCard>
				</Section>
			</Fragment>
		);
	}
}

export default InboxPanel;
