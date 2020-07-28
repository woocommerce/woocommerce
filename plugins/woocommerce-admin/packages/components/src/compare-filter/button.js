/**
 * External dependencies
 */
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { Button, Tooltip } from '@wordpress/components';

/**
 * A button used when comparing items, if `count` is less than 2 a hoverable tooltip is added with `helpText`.
 *
 * @param root0
 * @param root0.className
 * @param root0.count
 * @param root0.children
 * @param root0.disabled
 * @param root0.helpText
 * @param root0.onClick
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
