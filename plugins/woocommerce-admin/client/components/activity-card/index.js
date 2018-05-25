/** @format */
/**
 * External dependencies
 */
// import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import { cloneElement, Component } from '@wordpress/element';
import { Dashicon } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import { EllipsisMenu } from '../ellipsis-menu';

// @TODO Use @wordpress/date to format the date

class ActivityCard extends Component {
	render() {
		const { actions, className, date, icon, image, label, menu, children } = this.props;
		const cardClassName = classnames( 'woo-dash__activity-card', className );

		return (
			<section className={ cardClassName }>
				<header className="woo-dash__activity-card-header">
					<span className="woo-dash__activity-card-icon">{ icon }</span>
					<h3 className="woo-dash__activity-card-label">
						{ label }
						{ date && <span className="woo-dash__activity-card-date">– { date }</span> }
					</h3>
					{ menu && <div className="woo-dash__activity-card-menu">{ menu }</div> }
				</header>
				<div className="woo-dash__activity-card-body">
					<div className="woo-dash__activity-card-content">{ children }</div>
					{ image && <div className="woo-dash__activity-card-image">{ image }</div> }
				</div>
				{ actions && (
					<footer className="woo-dash__activity-card-actions">
						{ actions.map( ( item, i ) => cloneElement( item, { key: i } ) ) }
					</footer>
				) }
			</section>
		);
	}
}

ActivityCard.propTypes = {
	actions: PropTypes.oneOfType( [ PropTypes.array, PropTypes.element ] ),
	className: PropTypes.string,
	children: PropTypes.node.isRequired,
	date: PropTypes.string,
	icon: PropTypes.node,
	image: PropTypes.node,
	label: PropTypes.string.isRequired,
	menu: PropTypes.shape( {
		type: PropTypes.oneOf( [ EllipsisMenu ] ),
	} ),
};

ActivityCard.defaultProps = {
	icon: <Dashicon icon="warning" />,
};

export default ActivityCard;
