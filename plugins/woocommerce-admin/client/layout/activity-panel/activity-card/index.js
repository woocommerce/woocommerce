/** @format */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { cloneElement, Component } from '@wordpress/element';
import Gridicon from 'gridicons';
import { moment } from '@wordpress/date';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import { H, Section } from 'layout/section';

class ActivityCard extends Component {
	render() {
		const { actions, className, children, date, icon, subtitle, title, unread } = this.props;
		const cardClassName = classnames( 'woocommerce-activity-card', className );
		const actionsList = Array.isArray( actions ) ? actions : [ actions ];

		return (
			<section className={ cardClassName }>
				{ unread && <span className="woocommerce-activity-card__unread" /> }
				<span className="woocommerce-activity-card__icon" aria-hidden>
					{ icon }
				</span>
				<header className="woocommerce-activity-card__header">
					<H className="woocommerce-activity-card__title">{ title }</H>
					{ subtitle && <div className="woocommerce-activity-card__subtitle">{ subtitle }</div> }
					{ date && (
						<span className="woocommerce-activity-card__date">{ moment( date ).fromNow() }</span>
					) }
				</header>
				<Section className="woocommerce-activity-card__body">{ children }</Section>
				{ actions && (
					<footer className="woocommerce-activity-card__actions">
						{ actionsList.map( ( item, i ) => cloneElement( item, { key: i } ) ) }
					</footer>
				) }
			</section>
		);
	}
}

ActivityCard.propTypes = {
	actions: PropTypes.oneOfType( [ PropTypes.arrayOf( PropTypes.element ), PropTypes.element ] ),
	className: PropTypes.string,
	children: PropTypes.node.isRequired,
	date: PropTypes.string,
	icon: PropTypes.node,
	subtitle: PropTypes.node,
	title: PropTypes.oneOfType( [ PropTypes.string, PropTypes.node ] ).isRequired,
	unread: PropTypes.bool,
};

ActivityCard.defaultProps = {
	icon: <Gridicon icon="notice-outline" size={ 48 } />,
	unread: false,
};

export { ActivityCard };
export { default as ActivityCardPlaceholder } from './placeholder';
