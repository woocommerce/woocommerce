/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';

class StoreAlertsPlaceholder extends Component {
	render() {
		const { hasMultipleAlerts } = this.props;

		return (
			<div
				className="woocommerce-card woocommerce-store-alerts is-loading"
				aria-hidden
			>
				<div className="woocommerce-card__header">
					<div className="woocommerce-card__title woocommerce-card__header-item">
						<span className="is-placeholder" />
					</div>
					{ hasMultipleAlerts && (
						<div className="woocommerce-card__action woocommerce-card__header-item">
							<span className="is-placeholder" />
						</div>
					) }
				</div>
				<div className="woocommerce-card__body">
					<div className="woocommerce-store-alerts__message">
						<span className="is-placeholder" />
						<span className="is-placeholder" />
					</div>
					<div className="woocommerce-store-alerts__actions">
						<span className="is-placeholder" />
					</div>
				</div>
			</div>
		);
	}
}

export default StoreAlertsPlaceholder;

StoreAlertsPlaceholder.propTypes = {
	/**
	 * Whether multiple alerts exists.
	 */
	hasMultipleAlerts: PropTypes.bool,
};

StoreAlertsPlaceholder.defaultProps = {
	hasMultipleAlerts: false,
};
