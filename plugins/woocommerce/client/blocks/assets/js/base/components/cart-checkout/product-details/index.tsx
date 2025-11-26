/**
 * External dependencies
 */
import { paramCase as kebabCase } from 'change-case';
import { sanitizeHTML } from '@woocommerce/sanitize';

import type { ProductResponseItemData } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import './style.scss';

const CONTENT_TAGS = [ 'a', 'b', 'em', 'i', 'strong', 'br', 'abbr', 'span' ];

const CONTENT_ATTR = [
	'target',
	'href',
	'rel',
	'name',
	'download',
	'class',
	'title',
];

interface ProductDetailsProps {
	details: ProductResponseItemData[];
}

// Component to display cart item data and variations.
const ProductDetails = ( {
	details = [],
}: ProductDetailsProps ): JSX.Element | null => {
	if ( ! Array.isArray( details ) ) {
		return null;
	}

	details = details.filter( ( detail ) => ! detail.hidden );

	if ( details.length === 0 ) {
		return null;
	}

	let ParentTag = 'ul' as keyof JSX.IntrinsicElements;
	let ChildTag = 'li' as keyof JSX.IntrinsicElements;

	if ( details.length === 1 ) {
		ParentTag = 'div';
		ChildTag = 'div';
	}

	return (
		<ParentTag className="wc-block-components-product-details">
			{ details.map( ( detail ) => {
				// Support both `key` and `name` props
				const name = detail?.key || detail.name || '';
				// Strip HTML tags from name for CSS class generation
				const tempDiv = document.createElement( 'div' );
				tempDiv.innerHTML = name;
				const nameForClass =
					tempDiv.textContent || tempDiv.innerText || '';
				const className =
					detail?.className ||
					( nameForClass
						? `wc-block-components-product-details__${ kebabCase(
								nameForClass
						  ) }`
						: '' );

				return (
					<ChildTag
						key={ name + ( detail.display || detail.value ) }
						className={ className }
					>
						{ name && (
							<>
								<span
									className="wc-block-components-product-details__name"
									dangerouslySetInnerHTML={ {
										__html:
											sanitizeHTML( name, {
												tags: CONTENT_TAGS,
												attr: CONTENT_ATTR,
											} ) + ':',
									} }
								/>{ ' ' }
							</>
						) }
						<span
							className="wc-block-components-product-details__value"
							dangerouslySetInnerHTML={ {
								__html: sanitizeHTML(
									detail.display || detail.value,
									{
										tags: CONTENT_TAGS,
										attr: CONTENT_ATTR,
									}
								),
							} }
						/>
					</ChildTag>
				);
			} ) }
		</ParentTag>
	);
};

export default ProductDetails;
