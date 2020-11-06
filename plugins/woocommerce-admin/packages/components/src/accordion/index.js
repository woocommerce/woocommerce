/**
 * External dependencies
 */
import { Panel } from '@wordpress/components';
import PropTypes from 'prop-types';
import classnames from 'classnames';

/**
 * Use `Accordion` to display an accordion that renders the children inside.
 *
 * @param {Object} props
 * @param {string} props.children
 * @param {string} props.className
 * @return {Object} -
 */
const Accordion = ( { className, children } ) => {
	return (
		<Panel className={ classnames( className, 'woocommerce-accordion' ) }>
			{ children }
		</Panel>
	);
};

Accordion.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
};

export default Accordion;
