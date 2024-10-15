/**
 * External dependencies
 */
import React from '@wordpress/element';
import { external, Icon } from '@wordpress/icons';
import { Link } from '@woocommerce/components';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './style.scss';

export const QuickLink = ( { icon, title, href, linkType, onClick } ) => {
	const isExternal = linkType === 'external';

	return (
		<div className="woocommerce-quick-links__item">
			<Link
				onClick={ onClick }
				href={ href }
				type={ linkType }
				target={ isExternal ? '_blank' : null }
				className="woocommerce-quick-links__item-link"
			>
				<Icon
					className="woocommerce-quick-links__item-link__icon"
					icon={ icon }
				/>
				<Text
					className="woocommerce-quick-links__item-link__text"
					as="div"
					variant="button"
					weight="600"
					size="14"
					lineHeight="20px"
				>
					{ title }
				</Text>
				{ isExternal && <Icon icon={ external } /> }
			</Link>
		</div>
	);
};
