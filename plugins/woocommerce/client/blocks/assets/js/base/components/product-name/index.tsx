/**
 * External dependencies
 */
import clsx from 'clsx';
import type { AnchorHTMLAttributes, HTMLAttributes } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';

type DisabledTagNameType = 'span' | 'h3';

export interface ProductNameProps
	extends AnchorHTMLAttributes< HTMLAnchorElement > {
	/**
	 * If `true` renders a `span` element instead of a link
	 */
	disabled?: boolean;
	/**
	 * The product name
	 *
	 * Note: can be an HTML string
	 */
	name: string;
	/**
	 * Click handler
	 */
	onClick?: () => void;
	/**
	 * Link for the product
	 */
	permalink?: string;
	/*
	 * Disabled tag for the product name
	 */
	disabledTagName?: DisabledTagNameType;
}

/**
 * Render the Product name.
 */
export const ProductName = ( {
	className = '',
	disabled = false,
	name,
	permalink = '',
	target,
	rel,
	style,
	onClick,
	disabledTagName = 'span',
	...props
}: ProductNameProps ): JSX.Element => {
	const classes = clsx( 'wc-block-components-product-name', className );
	const DisabledTagName = disabledTagName as DisabledTagNameType;

	if ( disabled ) {
		const disabledProps = props as HTMLAttributes<
			HTMLHeadingElement | HTMLSpanElement
		>;
		return (
			<DisabledTagName
				className={ classes }
				{ ...disabledProps }
				// eslint-disable-next-line react/no-danger
				dangerouslySetInnerHTML={ {
					__html: name,
				} }
			/>
		);
	}
	return (
		<a
			className={ classes }
			href={ permalink }
			target={ target }
			{ ...props }
			// eslint-disable-next-line react/no-danger
			dangerouslySetInnerHTML={ {
				__html: name,
			} }
			style={ style }
		/>
	);
};

export default ProductName;
