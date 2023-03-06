/**
 * External dependencies
 */
import classnames from 'classnames';
import { Fragment } from '@wordpress/element';
import {
	Card,
	CardHeader,
	CardBody,
	CardMedia,
	CardDivider,
} from '@wordpress/components';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './List.scss';

const PlaceholderItem = () => {
	const classes = classnames(
		'woocommerce-task-payment',
		'woocommerce-task-card'
	);

	return (
		<Fragment>
			<CardBody
				style={ { paddingLeft: 0, marginBottom: 0 } }
				className={ classes }
			>
				<CardMedia isBorderless>
					<span className="is-placeholder" />
				</CardMedia>
				<div className="woocommerce-task-payment__description">
					<Text as="h3" className="woocommerce-task-payment__title">
						<span className="is-placeholder" />
					</Text>
					<div className="woocommerce-task-payment__content">
						<span className="is-placeholder" />
					</div>
				</div>
				<div className="woocommerce-task-payment__footer">
					<span className="is-placeholder" />
				</div>
			</CardBody>
			<CardDivider />
		</Fragment>
	);
};

export const Placeholder = () => {
	const classes =
		'is-loading woocommerce-payment-gateway-suggestions-list-placeholder';

	return (
		<Card aria-hidden="true" className={ classes }>
			<CardHeader as="h2">
				<span className="is-placeholder" />
			</CardHeader>
			<PlaceholderItem />
			<PlaceholderItem />
			<PlaceholderItem />
		</Card>
	);
};
