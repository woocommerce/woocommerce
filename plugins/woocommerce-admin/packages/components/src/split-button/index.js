/** @format */
/**
 * External dependencies
 */
import { Button, IconButton, Dropdown, NavigableMenu } from '@wordpress/components';
import classnames from 'classnames';
import PropTypes from 'prop-types';
import { noop } from 'lodash';

/**
 * A component for displaying a button with a main action plus a secondary set of actions behind a menu toggle.
 *
 * @return { object } -
 */
const SplitButton = ( {
	isPrimary,
	mainIcon,
	mainLabel,
	onClick,
	menuLabel,
	controls,
	className,
} ) => {
	if ( ! controls || ! controls.length ) {
		return null;
	}

	const MainButtonComponent = ( mainIcon && IconButton ) || Button;
	const classes = classnames( 'woocommerce-split-button', className, {
		'is-primary': isPrimary,
		'has-label': mainLabel,
	} );

	return (
		<div className={ classes }>
			<MainButtonComponent
				icon={ mainIcon }
				className="woocommerce-split-button__main-action"
				onClick={ onClick }
			>
				{ mainLabel }
			</MainButtonComponent>
			<Dropdown
				className="woocommerce-split-button__menu"
				position="bottom left"
				contentClassName="woocommerce-split-button__menu-popover"
				expandOnMobile
				headerTitle={ menuLabel }
				renderToggle={ ( { isOpen, onToggle } ) => {
					return (
						<IconButton
							icon={ isOpen ? 'arrow-up-alt2' : 'arrow-down-alt2' }
							className={ classnames( 'woocommerce-split-button__menu-toggle', {
								'is-active': isOpen,
							} ) }
							onClick={ onToggle }
							aria-haspopup="true"
							aria-expanded={ isOpen }
							label={ menuLabel }
							tooltip={ menuLabel }
						/>
					);
				} }
				renderContent={ ( { onClose } ) => {
					const renderControl = ( control, index ) => {
						const ButtonComponent = ( control.icon && IconButton ) || Button;
						return (
							<ButtonComponent
								key={ index }
								onClick={ event => {
									event.stopPropagation();
									onClose();
									if ( control.onClick ) {
										control.onClick();
									}
								} }
								className="woocommerce-split-button__menu-item"
								icon={ control.icon || '' }
								role="menuitem"
							>
								{ control.label }
							</ButtonComponent>
						);
					};

					return (
						<NavigableMenu
							className="woocommerce-split-button__menu-wrapper"
							role="menu"
							aria-label={ menuLabel }
						>
							{ controls.map( renderControl ) }
						</NavigableMenu>
					);
				} }
			/>
		</div>
	);
};

SplitButton.propTypes = {
	/**
	 * Whether the button is styled as a primary button.
	 */
	isPrimary: PropTypes.bool,
	/**
	 * Icon for the main button.
	 */
	mainIcon: PropTypes.node,
	/**
	 * Label for the main button.
	 */
	mainLabel: PropTypes.string,
	/**
	 * Function to activate when the the main button is clicked.
	 */
	onClick: PropTypes.func,
	/**
	 * Label to display for the menu of actions, used as a heading on the mobile popover and for accessible text.
	 */
	menuLabel: PropTypes.string,
	/**
	 * An array of additional actions. Accepts additional icon, label, and onClick props.
	 */
	controls: PropTypes.arrayOf(
		PropTypes.shape( {
			/**
			 * Icon used in button, passed to `IconButton`. Can be either string (dashicon name) or Gridicon.
			 */
			icon: PropTypes.oneOfType( [ PropTypes.string, PropTypes.element ] ),
			/**
			 * Label displayed for this button.
			 */
			label: PropTypes.string.isRequired,
			/**
			 * Click handler for this button.
			 */
			onClick: PropTypes.func,
		} )
	).isRequired,
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
};

SplitButton.defaultProps = {
	isPrimary: false,
	onClick: noop,
};

export default SplitButton;
