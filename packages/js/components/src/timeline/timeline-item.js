/**
 * External dependencies
 */
import clsx from 'clsx';
import PropTypes from 'prop-types';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { formatTimelineDate } from './util';

const TimelineItem = ( {
	item = {},
	className = '',
	clockFormat,
	timezone = 'browser',
} ) => {
	const itemClassName = clsx( 'woocommerce-timeline-item', className );
	const itemTimeString = formatTimelineDate(
		clockFormat,
		item.date,
		timezone
	);

	return (
		<li className={ itemClassName }>
			<div className={ 'woocommerce-timeline-item__top-border' }></div>
			<div className={ 'woocommerce-timeline-item__title' }>
				<div className={ 'woocommerce-timeline-item__headline' }>
					{ item.icon }
					<span>{ item.headline }</span>
				</div>
				<span className={ 'woocommerce-timeline-item__timestamp' }>
					{ item.hideTimestamp || false ? null : itemTimeString }
				</span>
			</div>
			<div className={ 'woocommerce-timeline-item__body' }>
				{ ( item.body || [] ).map( ( bodyItem, index ) => (
					<span key={ `timeline-item-body-${ index }` }>
						{ bodyItem }
					</span>
				) ) }
			</div>
		</li>
	);
};

TimelineItem.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * An array of list items.
	 */
	item: PropTypes.shape( {
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
		headline: PropTypes.oneOfType( [ PropTypes.element, PropTypes.string ] )
			.isRequired,
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
	} ),
	/**
	 * The PHP clock format string used to format times, see php.net/date.
	 */
	clockFormat: PropTypes.string,
	/**
	 * Defines whether item dates should be displayed in the browser timezone or the WordPress site timezone.
	 */
	timezone: PropTypes.oneOf( [ 'browser', 'site' ] ),
};

export default TimelineItem;
