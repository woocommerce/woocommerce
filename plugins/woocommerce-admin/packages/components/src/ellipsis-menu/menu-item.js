/**
 * External dependencies
 */
import { BaseControl, FormToggle } from '@wordpress/components';
import { createElement, Component, createRef } from '@wordpress/element';
import { DOWN, ENTER, SPACE, UP } from '@wordpress/keycodes';
import PropTypes from 'prop-types';

/**
 * `MenuItem` is used to give the item an accessible wrapper, with the `menuitem` role and added keyboard functionality (`onInvoke`).
 * `MenuItem`s can also be deemed "clickable", though this is disabled by default because generally the inner component handles
 * the click event.
 */
class MenuItem extends Component {
	constructor() {
		super( ...arguments );
		this.onClick = this.onClick.bind( this );
		this.onFocusFormToggle = this.onFocusFormToggle.bind( this );
		this.onKeyDown = this.onKeyDown.bind( this );
		this.container = createRef();
	}

	onClick( event ) {
		const { isClickable, onInvoke } = this.props;
		if ( isClickable ) {
			event.preventDefault();
			onInvoke();
		}
	}

	onKeyDown( event ) {
		if ( event.target.isSameNode( event.currentTarget ) ) {
			if ( event.keyCode === ENTER || event.keyCode === SPACE ) {
				event.preventDefault();
				this.props.onInvoke();
			}
			if ( event.keyCode === UP ) {
				event.preventDefault();
			}
			if ( event.keyCode === DOWN ) {
				event.preventDefault();
				const nextElementToFocus =
					event.target.nextSibling ||
					event.target.parentNode.querySelector(
						'.woocommerce-ellipsis-menu__item'
					);
				nextElementToFocus.focus();
			}
		}
	}

	onFocusFormToggle() {
		this.container.current.focus();
	}

	render() {
		const { checked, children, isCheckbox } = this.props;

		if ( isCheckbox ) {
			return (
				<div
					aria-checked={ checked }
					ref={ this.container }
					role="menuitemcheckbox"
					tabIndex="0"
					onKeyDown={ this.onKeyDown }
					onClick={ this.onClick }
					className="woocommerce-ellipsis-menu__item"
				>
					<BaseControl className="components-toggle-control">
						<FormToggle
							aria-hidden="true"
							checked={ checked }
							onChange={ this.props.onInvoke }
							onFocus={ this.onFocusFormToggle }
							onClick={ ( e ) => e.stopPropagation() }
							tabIndex="-1"
						/>
						{ children }
					</BaseControl>
				</div>
			);
		}

		return (
			<div
				role="menuitem"
				tabIndex="0"
				onKeyDown={ this.onKeyDown }
				onClick={ this.onClick }
				className="woocommerce-ellipsis-menu__item"
			>
				{ children }
			</div>
		);
	}
}

MenuItem.propTypes = {
	/**
	 * Whether the menu item is checked or not. Only relevant for menu items with `isCheckbox`.
	 */
	checked: PropTypes.bool,
	/**
	 * A renderable component (or string) which will be displayed as the content of this item. Generally a `ToggleControl`.
	 */
	children: PropTypes.node,
	/**
	 * Whether the menu item is a checkbox (will render a FormToggle and use the `menuitemcheckbox` role).
	 */
	isCheckbox: PropTypes.bool,
	/**
	 * Boolean to control whether the MenuItem should handle the click event. Defaults to false, assuming your child component
	 * handles the click event.
	 */
	isClickable: PropTypes.bool,
	/**
	 * A function called when this item is activated via keyboard ENTER or SPACE; or when the item is clicked
	 * (only if `isClickable` is set).
	 */
	onInvoke: PropTypes.func.isRequired,
};

MenuItem.defaultProps = {
	isClickable: false,
	isCheckbox: false,
};

export default MenuItem;
