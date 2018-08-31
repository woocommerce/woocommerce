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
import EllipsisMenu from 'components/ellipsis-menu';
import { H, Section } from 'layout/section';
import { validateComponent } from 'lib/proptype-validator';

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
					<H className="woocommerce-card__title">{ title }</H>
					{ action && <div className="woocommerce-card__action">{ action }</div> }
					{ menu && <div className="woocommerce-card__menu">{ menu }</div> }
				</div>
				<Section className="woocommerce-card__body">{ children }</Section>
			</div>
		);
	}
}

Card.propTypes = {
	action: PropTypes.node,
	className: PropTypes.string,
	menu: validateComponent( EllipsisMenu ),
	title: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ).isRequired,
};

export default Card;
