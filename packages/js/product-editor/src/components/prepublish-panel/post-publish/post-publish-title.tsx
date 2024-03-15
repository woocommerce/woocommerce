/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { createElement, createInterpolateElement } from '@wordpress/element';
import { useEntityProp } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { PostPublishTitleProps } from './types';
import { useProductURL } from '../../../hooks/use-product-url';
import { useProductScheduled } from '../../../hooks/use-product-scheduled';

export function PostPublishTitle( {
	productType = 'product',
}: PostPublishTitleProps ) {
	const { getProductURL } = useProductURL( productType );
	const { isScheduled, formattedDate } = useProductScheduled( productType );
	const [ editedProductName ] = useEntityProp< string >(
		'postType',
		productType,
		'name'
	);

	const productURLString = getProductURL( false );
	const getPostPublishedTitle = () => {
		if ( isScheduled ) {
			return createInterpolateElement(
				sprintf(
					/* translators: %s is the date when the product will be published */
					__(
						'<productURL /> is now scheduled. It will go live on %s',
						'woocommerce'
					),
					formattedDate
				),
				{
					productURL: (
						<a
							className="woocommerce-product-list__product-name"
							href={ productURLString }
							target="_blank"
							rel="noreferrer"
						>
							{ editedProductName }
						</a>
					),
				}
			);
		}
		return createInterpolateElement(
			__( '<productURL /> is now live.', 'woocommerce' ),
			{
				productURL: (
					<a
						className="woocommerce-product-list__product-name"
						href={ productURLString }
						target="_blank"
						rel="noreferrer"
					>
						{ editedProductName }
					</a>
				),
			}
		);
	};

	return (
		<div className="woocommerce-product-publish-panel__published">
			{ getPostPublishedTitle() }
		</div>
	);
}
