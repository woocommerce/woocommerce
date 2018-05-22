/** @format */
/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import classnames from 'classnames';
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

class AgendaItem extends Component {
	render() {
		const { children, className, onClick, href, actionLabel } = this.props;
		const classes = classnames( 'woo-dash__agenda-item-wrapper', className );

		const action = href !== undefined ? { href } : { onClick };

		return (
			<div className={ classes }>
				{ children && <div className="woo-dash__agenda-item-content"> { children } </div> }
				<Button className="woo-dash__agenda-item-action-button button-secondary" { ...action }>
					{ actionLabel }
				</Button>
			</div>
		);
	}
}

AgendaItem.propTypes = {
	onClick: PropTypes.func,
	href: PropTypes.string,
	actionLabel: PropTypes.string.isRequired,
	className: PropTypes.string,
};

export default AgendaItem;
