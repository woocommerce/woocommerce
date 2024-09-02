/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import TimelineItem from './timeline-item';
import { sortByDateUsing } from './util';

const TimelineGroup = ( {
	group = { title: '', items: [] },
	className = '',
	orderBy = 'desc',
	clockFormat,
} ) => {
	const groupClassName = classnames(
		'woocommerce-timeline-group',
		className
	);
	const itemsToTimlineItem = ( item, itemIndex ) => {
		const itemKey = group.title + '-' + itemIndex;
		return (
			<TimelineItem
				key={ itemKey }
				item={ item }
				clockFormat={ clockFormat }
			/>
		);
	};

	return (
		<li className={ groupClassName }>
			<p className={ 'woocommerce-timeline-group__title' }>
				{ group.title }
			</p>
			<ul>
				{ group.items
					.sort( sortByDateUsing( orderBy ) )
					.map( itemsToTimlineItem ) }
			</ul>
			<hr />
		</li>
	);
};

TimelineGroup.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * The group to render.
	 */
	group: PropTypes.shape( {
		/**
		 * The group title.
		 */
		title: PropTypes.string,
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
					PropTypes.oneOfType( [
						PropTypes.element,
						PropTypes.string,
					] )
				),
				/**
				 * Allows users to toggle the timestamp on or off.
				 */
				hideTimestamp: PropTypes.bool,
			} )
		),
	} ),
	/**
	 * Defines how items should be ordered.
	 */
	orderBy: PropTypes.oneOf( [ 'asc', 'desc' ] ),
	/**
	 * The PHP clock format string used to format times, see php.net/date.
	 */
	clockFormat: PropTypes.string,
};

export default TimelineGroup;
