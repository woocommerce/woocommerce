/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Tag from '../tag';

/**
 * This component displays a 'X more' button that displays a list of items on a popover when clicked.
 *
 * @param root0
 * @param root0.items
 * @param root0
 * @param root0.items
 * @return {Object} -
 */
const ViewMoreList = ( { items } ) => {
	return (
		<Tag
			className="woocommerce-view-more-list"
			label={ sprintf(
				__( '+%d more', 'woocommerce-admin' ),
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

ViewMoreList.defaultProps = {
	items: [],
};

export default ViewMoreList;
