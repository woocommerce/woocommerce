/**
 * External dependencies
 */
import classnames from 'classnames';
import { cloneElement, Component } from '@wordpress/element';
import Gridicon from 'gridicons';
import moment from 'moment';
import PropTypes from 'prop-types';
import { H, Section } from '@woocommerce/components';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './style.scss';

class ActivityCard extends Component {
	getCard() {
		const {
			actions,
			className,
			children,
			date,
			icon,
			subtitle,
			title,
			unread,
		} = this.props;
		const cardClassName = classnames(
			'woocommerce-activity-card',
			className
		);
		const actionsList = Array.isArray( actions ) ? actions : [ actions ];

		return (
			<section className={ cardClassName }>
				{ unread && (
					<span className="woocommerce-activity-card__unread" />
				) }
				{ icon && (
					<span
						className="woocommerce-activity-card__icon"
						aria-hidden
					>
						{ icon }
					</span>
				) }
				{ title && (
					<header className="woocommerce-activity-card__header">
						<H className="woocommerce-activity-card__title">
							{ title }
						</H>
						{ subtitle && (
							<div className="woocommerce-activity-card__subtitle">
								{ subtitle }
							</div>
						) }
						{ date && (
							<span className="woocommerce-activity-card__date">
								{ moment.utc( date ).fromNow() }
							</span>
						) }
					</header>
				) }
				{ children && (
					<Section className="woocommerce-activity-card__body">
						{ children }
					</Section>
				) }
				{ actions && (
					<footer className="woocommerce-activity-card__actions">
						{ actionsList.map( ( item, i ) =>
							cloneElement( item, { key: i } )
						) }
					</footer>
				) }
			</section>
		);
	}

	render() {
		const { onClick } = this.props;
		if ( onClick ) {
			return (
				<Button
					className="woocommerce-activity-card__button"
					onClick={ onClick }
				>
					{ this.getCard() }
				</Button>
			);
		}
		return this.getCard();
	}
}

ActivityCard.propTypes = {
	actions: PropTypes.oneOfType( [
		PropTypes.arrayOf( PropTypes.element ),
		PropTypes.element,
	] ),
	onClick: PropTypes.func,
	className: PropTypes.string,
	children: PropTypes.node,
	date: PropTypes.string,
	icon: PropTypes.node,
	subtitle: PropTypes.node,
	title: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ),
	unread: PropTypes.bool,
};

ActivityCard.defaultProps = {
	icon: <Gridicon icon="notice-outline" size={ 48 } />,
	unread: false,
};

export { ActivityCard };
export { default as ActivityCardPlaceholder } from './placeholder';
