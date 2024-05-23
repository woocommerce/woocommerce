/**
 * External dependencies
 */
import { Card as WPCard, CardBody, CardHeader } from '@wordpress/components';
import PropTypes from 'prop-types';
import clsx from 'clsx';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './style.scss';

const Card = ( props ) => {
	const { title, description, children, className } = props;

	return (
		<WPCard
			className={ clsx( className, 'woocommerce-admin-marketing-card' ) }
		>
			<CardHeader>
				<div>
					<Text
						variant="title.small"
						as="p"
						size="20"
						lineHeight="28px"
					>
						{ title }
					</Text>
					<Text
						variant="subtitle.small"
						as="p"
						className="woocommerce-admin-marketing-card-subtitle"
						size="14"
						lineHeight="20px"
					>
						{ description }
					</Text>
				</div>
			</CardHeader>
			<CardBody>{ children }</CardBody>
		</WPCard>
	);
};

Card.propTypes = {
	/**
	 * Card title.
	 */
	title: PropTypes.string,
	/**
	 * Card description.
	 */
	description: PropTypes.string,
	/**
	 * Additional class name to style the component.
	 */
	className: PropTypes.string,
	/**
	 * A renderable component (or string) which will be displayed as the content of this item. Generally a `ToggleControl`.
	 */
	children: PropTypes.node,
};

export default Card;
