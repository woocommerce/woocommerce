/** @format */
/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import AgendaHeader from './header';
import AgendaItem from './item';
import Card from 'components/card';
import { getAdminLink } from 'lib/nav-utils';

import './style.scss';

// TODO Hook up to API Data once agenda API is available
class Agenda extends Component {
	render() {
		return (
			<Card
				title={ __( 'Your agenda', 'wc-admin' ) }
				className="woocommerce-dashboard__agenda-card"
			>
				<AgendaHeader
					count={ 2 }
					title={ _n(
						'Order needs to be fulfilled',
						'Orders need to be fulfilled',
						2,
						'wc-admin'
					) }
				>
					<AgendaItem onClick={ noop } actionLabel={ __( 'Fulfill', 'wc-admin' ) }>
						Order #99
					</AgendaItem>
					<AgendaItem
						href={ getAdminLink( '/edit.php?post_type=shop_order' ) }
						actionLabel={ __( 'Fulfill', 'wc-admin' ) }
					>
						Order #101
					</AgendaItem>
				</AgendaHeader>
				<AgendaHeader
					count={ 1 }
					title={ _n( 'Order awaiting payment', 'Orders awaiting payment', 1, 'wc-admin' ) }
					href={ getAdminLink( '/edit.php?post_status=wc-pending&post_type=shop_order' ) }
				/>
				<AgendaHeader
					title={ __( 'Extensions', 'wc-admin' ) }
					href={ getAdminLink( '/admin.php?page=wc-addons' ) }
				/>
			</Card>
		);
	}
}

export default Agenda;
