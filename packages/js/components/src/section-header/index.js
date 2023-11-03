/**
 * External dependencies
 */
import { createElement, Component } from '@wordpress/element';
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import EllipsisMenu from '../ellipsis-menu';
import { H } from '../section/header';
import { validateComponent } from '../lib/proptype-validator';

/**
 * A header component. The header can contain a title, actions via children, and an `EllipsisMenu` menu.
 */
class SectionHeader extends Component {
	render() {
		const { children, menu, title } = this.props;
		const className = classnames(
			'woocommerce-section-header',
			this.props.className
		);
		return (
			<div className={ className }>
				<H className="woocommerce-section-header__title woocommerce-section-header__header-item">
					{ title }
				</H>
				<hr role="presentation" />
				{ children && (
					<div className="woocommerce-section-header__actions woocommerce-section-header__header-item">
						{ children }
					</div>
				) }
				{ menu && (
					<div className="woocommerce-section-header__menu woocommerce-section-header__header-item">
						{ menu }
					</div>
				) }
			</div>
		);
	}
}

SectionHeader.propTypes = {
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * An `EllipsisMenu`, with filters used to control the content visible in this card
	 */
	menu: validateComponent( EllipsisMenu ),
	/**
	 * The title to use for this card.
	 */
	title: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] )
		.isRequired,
};

export default SectionHeader;
