/** @format */
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
import { EllipsisMenu } from 'components/ellipsis-menu';
import { H } from 'layout/section';

class ActivityHeader extends Component {
	render() {
		const { title, className, menu } = this.props;
		const cardClassName = classnames( 'woocommerce-layout__activity-panel-header', className );

		return (
			<div className={ cardClassName }>
				<H className="woocommerce-layout__activity-panel-header-title">{ title }</H>
				{ menu && <div className="woocommerce-layout__activity-panel-header-menu">{ menu }</div> }
			</div>
		);
	}
}

ActivityHeader.propTypes = {
	className: PropTypes.string,
	title: PropTypes.string.isRequired,
	menu: PropTypes.shape( {
		type: PropTypes.oneOf( [ EllipsisMenu ] ),
	} ),
};

export default ActivityHeader;
