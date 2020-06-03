/**
 * External dependencies
 */
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import { EllipsisMenu, H } from '@woocommerce/components';

class ActivityHeader extends Component {
	render() {
		const { className, menu, subtitle, title, unreadMessages } = this.props;
		const cardClassName = classnames(
			{
				'woocommerce-layout__inbox-panel-header': subtitle,
				'woocommerce-layout__activity-panel-header': ! subtitle,
			},
			className
		);
		const countUnread = unreadMessages ? unreadMessages : 0;

		return (
			<div className={ cardClassName }>
				<H className="woocommerce-layout__activity-panel-header-title">
					{ title }
					{ countUnread > 0 && <span>{ unreadMessages }</span> }
				</H>
				{ subtitle && (
					<div className="woocommerce-layout__activity-panel-header-subtitle">
						{ subtitle }
					</div>
				) }
				{ menu && (
					<div className="woocommerce-layout__activity-panel-header-menu">
						{ menu }
					</div>
				) }
			</div>
		);
	}
}

ActivityHeader.propTypes = {
	className: PropTypes.string,
	unreadMessages: PropTypes.number,
	title: PropTypes.string.isRequired,
	subtitle: PropTypes.string,
	menu: PropTypes.shape( {
		type: PropTypes.oneOf( [ EllipsisMenu ] ),
	} ),
};

export default ActivityHeader;
