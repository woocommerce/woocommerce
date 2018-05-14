/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import { EllipsisMenu } from '../ellipsis-menu';

class Card extends Component {
	render() {
		const { action, children, menu, title } = this.props;
		const className = classnames( 'woo-dash__card', this.props.className, {
			'has-menu': !! menu,
			'has-action': !! action,
		} );
		return (
			<div className={ className }>
				<div className="woo-dash__card-header">
					<h2 className="woo-dash__card-title">{ title }</h2>
					{ action && <div className="woo-dash__card-action">{ action }</div> }
					{ menu && <div className="woo-dash__card-menu">{ menu }</div> }
				</div>
				<div className="woo-dash__card-body">{ children }</div>
			</div>
		);
	}
}

Card.propTypes = {
	action: PropTypes.node,
	className: PropTypes.string,
	menu: PropTypes.shape( {
		type: PropTypes.oneOf( [ EllipsisMenu ] ),
	} ),
	title: PropTypes.string.isRequired,
};

export default Card;
