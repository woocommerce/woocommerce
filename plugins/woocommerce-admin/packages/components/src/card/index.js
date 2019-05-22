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
import EllipsisMenu from '../ellipsis-menu';
import { H, Section } from '../section';
import { validateComponent } from '../lib/proptype-validator';

/**
 * A basic card component with a header. The header can contain a title, an action, and an `EllipsisMenu` menu.
 */
class Card extends Component {
	render() {
		const { action, children, description, isInactive, menu, title } = this.props;
		const className = classnames( 'woocommerce-card', this.props.className, {
			'has-menu': !! menu,
			'has-action': !! action,
			'is-inactive': !! isInactive,
		} );
		return (
			<div className={ className }>
				{ title && (
					<div className="woocommerce-card__header">
						<div className="woocommerce-card__title-wrapper">
							<H className="woocommerce-card__title woocommerce-card__header-item">{ title }</H>
							{ description && (
								<H className="woocommerce-card__description woocommerce-card__header-item">
									{ description }
								</H>
							) }
						</div>
						{ action && (
							<div className="woocommerce-card__action woocommerce-card__header-item">
								{ action }
							</div>
						) }
						{ menu && (
							<div className="woocommerce-card__menu woocommerce-card__header-item">{ menu }</div>
						) }
					</div>
				) }
				<Section className="woocommerce-card__body">{ children }</Section>
			</div>
		);
	}
}

Card.propTypes = {
	/**
	 * One "primary" action for this card, appears in the card header.
	 */
	action: PropTypes.node,
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * The description displayed beneath the title.
	 */
	description: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ),
	/**
	 * Boolean representing whether the card is inactive or not.
	 */
	isInactive: PropTypes.bool,
	/**
	 * An `EllipsisMenu`, with filters used to control the content visible in this card
	 */
	menu: validateComponent( EllipsisMenu ),
	/**
	 * The title to use for this card.
	 */
	title: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ),
};

export default Card;
