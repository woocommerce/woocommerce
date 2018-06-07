/** @format */
/**
 * External dependencies
 */
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import OrdersList from './orders';

function ActivityList( { section } ) {
	switch ( section ) {
		case 'orders':
			return <OrdersList />;
		default:
			return <p>Coming soon…</p>;
	}
}

ActivityList.propTypes = {
	section: PropTypes.oneOf( [ 'orders', 'reviews', 'stock', 'extensions' ] ).isRequired,
};

export default ActivityList;
