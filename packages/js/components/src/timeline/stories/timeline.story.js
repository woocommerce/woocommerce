/**
 * External dependencies
 */
import GridIcon from 'gridicons';

/**
 * Internal dependencies
 */
import Timeline, { orderByOptions } from '../';

export const Filled = () => {
	return (
		<Timeline
			orderBy={ orderByOptions.DESC }
			items={ [
				{
					date: new Date( 2020, 0, 20, 1, 30 ),
					body: [
						<p key="1">p element in body</p>,
						'string in body',
					],
					headline: <p>p tag in headline</p>,
					icon: (
						<GridIcon
							className="is-success"
							icon="checkmark"
							size={ 16 }
						/>
					),
					hideTimestamp: true,
				},
				{
					date: new Date( 2020, 0, 20, 23, 45 ),
					body: [],
					headline: <span>span in headline</span>,
					icon: (
						<GridIcon
							className={ 'is-warning' }
							icon="refresh"
							size={ 16 }
						/>
					),
				},
				{
					date: new Date( 2020, 0, 22, 15, 13 ),
					body: [ <span key="1">span in body</span> ],
					headline: 'string in headline',
					icon: (
						<GridIcon
							className={ 'is-error' }
							icon="cross"
							size={ 16 }
						/>
					),
				},
				{
					date: new Date( 2020, 0, 17, 1, 45 ),
					headline: 'undefined body and string headline',
					icon: <GridIcon icon="cross" size={ 16 } />,
				},
			] }
		/>
	);
};

export default {
	title: 'WooCommerce Admin/components/Timeline',
	component: Filled,
};

export const Empty = () => <Timeline />;
