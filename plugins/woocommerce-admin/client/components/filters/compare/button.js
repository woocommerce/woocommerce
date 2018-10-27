/** @format */

/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { Button, Tooltip } from '@wordpress/components';

/**
 * A button used when comparing items, if `count` is less than 2 a hoverable tooltip is added with `helpText`.
 *
 * @return { object } -
 */
const CompareButton = ( { className, count, children, helpText, onClick } ) =>
	count < 2 ? (
		<Tooltip text={ helpText }>
			<span className={ className }>
				<Button className="woocommerce-compare-button" isDefault disabled={ true }>
					{ children }
				</Button>
			</span>
		</Tooltip>
	) : (
		<Button
			className={ classnames( 'woocommerce-compare-button', className ) }
			isDefault
			onClick={ onClick }
		>
			{ children }
		</Button>
	);

CompareButton.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * The count of items selected.
	 */
	count: PropTypes.number.isRequired,
	/**
	 * The button content.
	 */
	children: PropTypes.node.isRequired,
	/**
	 * Text displayed when hovering over a disabled button.
	 */
	helpText: PropTypes.string.isRequired,
	/**
	 * The function called when the button is clicked.
	 */
	onClick: PropTypes.func.isRequired,
};

export default CompareButton;
