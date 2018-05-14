/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { keycodes } from '@wordpress/utils';
import PropTypes from 'prop-types';

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
		if ( event.keyCode === keycodes.ENTER || event.keyCode === keycodes.SPACE ) {
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
				className="woo-dash__ellipsis-menu-item"
			>
				{ children }
			</div>
		);
	}
}

MenuItem.propTypes = {
	children: PropTypes.node,
	isClickable: PropTypes.bool,
	onInvoke: PropTypes.func.isRequired,
};

MenuItem.defaultProps = {
	isClickable: false,
};

export default MenuItem;
