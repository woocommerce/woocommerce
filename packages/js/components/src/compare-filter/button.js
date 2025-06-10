/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { Button, Tooltip } from '@wordpress/components';
import { createElement } from '@wordpress/element';

/**
 * A button used when comparing items, if `count` is less than 2 a hoverable tooltip is added with `helpText`.
 *
 * @param {Object}   props
 * @param {string}   props.className
 * @param {number}   props.count
 * @param {Node}     props.children
 * @param {boolean}  props.disabled
 * @param {string}   props.helpText
 * @param {Function} props.onClick
 * @return {Object} -
 */
const CompareButton = ( {
	className,
	count,
	children,
	disabled,
	helpText,
	onClick,
} ) =>
	! disabled && count < 2 ? (
		<Tooltip text={ helpText }>
			<span className={ className }>
				<Button
					className="woocommerce-compare-button"
					disabled={ true }
					isSecondary
				>
					{ children }
				</Button>
			</span>
		</Tooltip>
	) : (
		<Button
			className={ classnames( 'woocommerce-compare-button', className ) }
			onClick={ onClick }
			disabled={ disabled }
			isSecondary
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
	/**
	 * Whether the control is disabled or not.
	 */
	disabled: PropTypes.bool,
};

export default CompareButton;
