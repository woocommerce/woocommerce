/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { format } from '@wordpress/date';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TimelineGroup from './timeline-group';
import { sortByDateUsing, groupItemsUsing } from './util';

const Timeline = ( {
	className = '',
	items = [],
	groupBy = 'day',
	orderBy = 'desc',
	/* translators: PHP date format string used to display dates, see php.net/date. */
	dateFormat = __( 'F j, Y', 'woocommerce' ),
	/* translators: PHP clock format string used to display times, see php.net/date. */
	clockFormat = __( 'g:ia', 'woocommerce' ),
} ) => {
	const timelineClassName = classnames( 'woocommerce-timeline', className );

	// Early return in case no data was passed to the component.
	if ( ! items || items.length === 0 ) {
		return (
			<div className={ timelineClassName }>
				<p className={ 'timeline_no_events' }>
					{ __( 'No data to display', 'woocommerce' ) }
				</p>
			</div>
		);
	}

	const addGroupTitles = ( group ) => {
		return {
			...group,
			title: format( dateFormat, group.date ),
		};
	};

	return (
		<div className={ timelineClassName }>
			<ul>
				{ items
					.reduce( groupItemsUsing( groupBy ), [] )
					.map( addGroupTitles )
					.sort( sortByDateUsing( orderBy ) )
					.map( ( group ) => (
						<TimelineGroup
							key={ group.date.getTime().toString() }
							group={ group }
							orderBy={ orderBy }
							clockFormat={ clockFormat }
						/>
					) ) }
			</ul>
		</div>
	);
};

Timeline.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * An array of list items.
	 */
	items: PropTypes.arrayOf(
		PropTypes.shape( {
			/**
			 * Date for the timeline item.
			 */
			date: PropTypes.instanceOf( Date ).isRequired,
			/**
			 * Icon for the Timeline item.
			 */
			icon: PropTypes.element.isRequired,
			/**
			 * Headline displayed for the list item.
			 */
			headline: PropTypes.oneOfType( [
				PropTypes.element,
				PropTypes.string,
			] ).isRequired,
			/**
			 * Body displayed for the list item.
			 */
			body: PropTypes.arrayOf(
				PropTypes.oneOfType( [ PropTypes.element, PropTypes.string ] )
			),
			/**
			 * Allows users to toggle the timestamp on or off.
			 */
			hideTimestamp: PropTypes.bool,
		} )
	),
	/**
	 * Defines how items should be grouped together.
	 */
	groupBy: PropTypes.oneOf( [ 'day', 'week', 'month' ] ),
	/**
	 * Defines how groups should be ordered.
	 */
	orderBy: PropTypes.oneOf( [ 'asc', 'desc' ] ),
	/**
	 * The PHP date format string used to format dates, see php.net/date.
	 */
	dateFormat: PropTypes.string,
	/**
	 * The PHP clock format string used to format times, see php.net/date.
	 */
	clockFormat: PropTypes.string,
};

export { orderByOptions, groupByOptions } from './util';
export default Timeline;
