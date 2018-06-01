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
		const className = classnames( 'woocommerce-card', this.props.className, {
			'has-menu': !! menu,
			'has-action': !! action,
		} );
		return (
			<div className={ className }>
				<div className="woocommerce-card__header">
					<h2 className="woocommerce-card__title">{ title }</h2>
					{ action && <div className="woocommerce-card__action">{ action }</div> }
					{ menu && <div className="woocommerce-card__menu">{ menu }</div> }
				</div>
				<div className="woocommerce-card__body">{ children }</div>
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
