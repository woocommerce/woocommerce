/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import { Card, CardBody, CardFooter, CardHeader } from '@wordpress/components';
import clsx from 'clsx';

class StoreAlertsPlaceholder extends Component {
	render() {
		const { hasMultipleAlerts, className } = this.props;

		return (
			<Card
				className={ clsx(
					'woocommerce-store-alerts is-loading',
					className
				) }
				aria-hidden
				size={ null }
			>
				<CardHeader isBorderless>
					<span className="is-placeholder" />
					{ hasMultipleAlerts && <span className="is-placeholder" /> }
				</CardHeader>
				<CardBody>
					<div className="woocommerce-store-alerts__message">
						<span className="is-placeholder" />
						<span className="is-placeholder" />
					</div>
				</CardBody>
				<CardFooter isBorderless>
					<span className="is-placeholder" />
				</CardFooter>
			</Card>
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
