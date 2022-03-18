/**
 * External dependencies
 */
import { date, text } from '@storybook/addon-knobs';
import GridIcon from 'gridicons';

/**
 * Internal dependencies
 */
import Timeline, { orderByOptions } from '../';

export default {
	title: 'WooCommerce Admin/components/Timeline',
	component: Timeline,
};

export const Empty = () => <Timeline />;

const itemDate = ( label, value ) => {
	const d = date( label, value );
	return new Date( d );
};

export const Filled = () => (
	<Timeline
		orderBy={ orderByOptions.DESC }
		items={ [
			{
				date: itemDate(
					'event 1 date',
					new Date( 2020, 0, 20, 1, 30 )
				),
				body: [
					<p key={ '1' }>
						{ text( 'event 1, first event', 'p element in body' ) }
					</p>,
					text( 'event 1, second event', 'string in body' ),
				],
				headline: (
					<p>{ text( 'event 1, headline', 'p tag in headline' ) }</p>
				),
				icon: (
					<GridIcon
						className={ 'is-success' }
						icon={ text( 'event 1 gridicon', 'checkmark' ) }
						size={ 16 }
					/>
				),
				hideTimestamp: true,
			},
			{
				date: itemDate(
					'event 2 date',
					new Date( 2020, 0, 20, 23, 45 )
				),
				body: [],
				headline: (
					<span>
						{ text( 'event 2, headline', 'span in headline' ) }
					</span>
				),
				icon: (
					<GridIcon
						className={ 'is-warning' }
						icon={ text( 'event 2 gridicon', 'refresh' ) }
						size={ 16 }
					/>
				),
			},
			{
				date: itemDate(
					'event 3 date',
					new Date( 2020, 0, 22, 15, 13 )
				),
				body: [
					<span key={ '1' }>
						{ text( 'event 3, second event', 'span in body' ) }
					</span>,
				],
				headline: text( 'event 3, headline', 'string in headline' ),
				icon: (
					<GridIcon
						className={ 'is-error' }
						icon={ text( 'event 3 gridicon', 'cross' ) }
						size={ 16 }
					/>
				),
			},
			{
				date: itemDate(
					'event 4 date',
					new Date( 2020, 0, 17, 1, 45 )
				),
				headline: text(
					'event 4, headline',
					'undefined body and string headline'
				),
				icon: (
					<GridIcon
						icon={ text( 'event 4 gridicon', 'cross' ) }
						size={ 16 }
					/>
				),
			},
		] }
	/>
);
