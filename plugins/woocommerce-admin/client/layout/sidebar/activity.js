/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { Dashicon } from '@wordpress/components';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import ActivityCard from 'components/activity-card';
import { EllipsisMenu, MenuItem } from 'components/ellipsis-menu';

// @TODO Get an activity list from an API?
// @TODO Add back translations once we're showing real cards

class ActivityList extends Component {
	render() {
		const exampleEllipsisMenu = (
			<EllipsisMenu label="Menu">
				<MenuItem onInvoke={ noop }>Demo 1</MenuItem>
				<MenuItem onInvoke={ noop }>Demo 2</MenuItem>
			</EllipsisMenu>
		);

		return (
			<div>
				<ActivityCard
					label="Insight"
					icon={ <Dashicon icon="search" /> }
					date="30 minutes ago"
					actions={ [ <a href="/">Action link</a>, <a href="/">Action link 2</a> ] }
					image={ <Dashicon icon="palmtree" /> }
				>
					Insight content goes in this area here. It will probably be a couple of lines long and may
					include an accompanying image. We might consider color-coding the icon for quicker
					scanning.
				</ActivityCard>
				<ActivityCard
					label="Traffic"
					icon={ <Dashicon icon="chart-bar" /> }
					date="1 hour ago"
					menu={ exampleEllipsisMenu }
					actions={ [ <a href="/">View referral analytics</a> ] }
					image={ <img src="https://ps.w.org/woocommerce/assets/icon-128x128.png" alt="" /> }
				>
					You’re receiving a lot of traffic from the following Reddit topic…
				</ActivityCard>
				<ActivityCard label="Order" icon={ <Dashicon icon="format-aside" /> } date="yesterday">
					{ 'Zé Marques placed order #99' }
					{ '4 products - $198.34' }
					[ Completed ]
				</ActivityCard>
			</div>
		);
	}
}

export default ActivityList;
