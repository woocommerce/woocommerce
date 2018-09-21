/** @format */

/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classnames from 'classnames';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import './style.scss';
import { Link } from '@woocommerce/components';

const ActivityOutboundLink = props => {
	const { href, type, className, children, ...restOfProps } = props;
	const classes = classnames( 'woocommerce-layout__activity-panel-outbound-link', className );
	return (
		<Link href={ href } type={ type } className={ classes } { ...restOfProps }>
			{ children }
			<Gridicon icon="arrow-right" />
		</Link>
	);
};

ActivityOutboundLink.propTypes = {
	href: PropTypes.string.isRequired,
	type: PropTypes.oneOf( [ 'wp-admin', 'wc-admin', 'external' ] ).isRequired,
	className: PropTypes.string,
};

ActivityOutboundLink.defaultProps = {
	type: 'wp-admin',
};

export default ActivityOutboundLink;
