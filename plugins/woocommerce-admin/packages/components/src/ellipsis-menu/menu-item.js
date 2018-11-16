/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { ENTER, SPACE } from '@wordpress/keycodes';
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
		this.onKeyDown = this.onKeyDown.bind( this );
	}

	onClick( event ) {
		const { isClickable, onInvoke } = this.props;
		if ( isClickable ) {
			event.preventDefault();
			onInvoke();
		}
	}

	onKeyDown( event ) {
		if ( event.keyCode === ENTER || event.keyCode === SPACE ) {
			event.preventDefault();
			this.props.onInvoke();
		}
	}

	render() {
		const { children } = this.props;

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
	 * A renderable component (or string) which will be displayed as the content of this item. Generally a `ToggleControl`.
	 */
	children: PropTypes.node,
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
};

export default MenuItem;
