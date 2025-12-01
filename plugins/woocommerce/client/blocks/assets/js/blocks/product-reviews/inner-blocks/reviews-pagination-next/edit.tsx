/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, PlainText } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';

const arrowMap = {
	none: '',
	arrow: '→',
	chevron: '»',
};

type Props = BlockEditProps< { label: string } > & {
	// eslint-disable-next-line @typescript-eslint/naming-convention
	context: { 'reviews/paginationArrow': string };
};

export default function Edit( {
	attributes: { label },
	setAttributes,
	context: { 'reviews/paginationArrow': paginationArrow },
}: Props ) {
	const displayArrow = arrowMap[ paginationArrow as keyof typeof arrowMap ];
	return (
		<a
			href="#reviews-pagination-next-pseudo-link"
			onClick={ ( event ) => event.preventDefault() }
			{ ...useBlockProps() }
		>
			<PlainText
				__experimentalVersion={ 2 }
				tagName="span"
				aria-label={ __( 'Newer reviews page link', 'woocommerce' ) }
				placeholder={ __( 'Newer Reviews', 'woocommerce' ) }
				value={ label }
				onChange={ ( newLabel ) =>
					setAttributes( { label: newLabel } )
				}
			/>
			{ displayArrow && (
				<span
					className={ `wp-block-woocommerce-product-reviews-pagination-next-arrow is-arrow-${ paginationArrow }` }
				>
					{ displayArrow }
				</span>
			) }
		</a>
	);
}
