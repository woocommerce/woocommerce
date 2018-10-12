/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Tag from 'components/tag';
import './style.scss';

/**
 * This component displays a 'X more' button that displays a list of items on a popover when clicked.
 *
 * @return { object } -
 */
const ViewMoreList = ( { items } ) => {
	return (
		<Tag
			className="woocommerce-view-more-list"
			label={ sprintf( __( '+%d more', 'wc-admin' ), items.length - 1 ) }
			popoverContents={
				<ul className="woocommerce-view-more-list__popover">
					{ items.map( ( item, i ) => (
						<li key={ i } className="woocommerce-view-more-list__popover__item">
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
