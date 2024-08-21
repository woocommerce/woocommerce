/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Tag from '../tag';

/**
 * This component displays a 'X more' button that displays a list of items on a popover when clicked.
 *
 * @param {Object} props
 * @param {Array}  props.items
 * @return {Object} -
 */
const ViewMoreList = ( { items = [] } ) => {
	return (
		<Tag
			className="woocommerce-view-more-list"
			label={ sprintf(
				/* translators: %d: number of items more to view */
				__( '+%d more', 'woocommerce' ),
				items.length - 1
			) }
			popoverContents={
				<ul className="woocommerce-view-more-list__popover">
					{ items.map( ( item, i ) => (
						<li
							key={ i }
							className="woocommerce-view-more-list__popover__item"
						>
							{ item }
						</li>
					) ) }
				</ul>
			}
		/>
	);
};

ViewMoreList.propTypes = {
	/**
	 * Items to list in the popover
	 */
	items: PropTypes.arrayOf( PropTypes.node ),
};

export default ViewMoreList;
