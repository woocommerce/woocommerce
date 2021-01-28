/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

class InboxNotePlaceholder extends Component {
	render() {
		const { className } = this.props;
		return (
			<div
				className={ `woocommerce-inbox-message is-placeholder ${ className }` }
				aria-hidden
			>
				<div className="woocommerce-inbox-message__wrapper">
					<div className="woocommerce-inbox-message__content">
						<div className="woocommerce-inbox-message__date">
							<div className="sixth-line" />
						</div>
						<div className="woocommerce-inbox-message__title">
							<div className="line" />
							<div className="line" />
						</div>
						<div className="woocommerce-inbox-message__text">
							<div className="line" />
							<div className="third-line" />
						</div>
					</div>
					<div className="woocommerce-inbox-message__actions">
						<div className="fifth-line" />
						<div className="fifth-line" />
					</div>
				</div>
			</div>
		);
	}
}

InboxNotePlaceholder.propTypes = {
	className: PropTypes.string,
};

export default InboxNotePlaceholder;
