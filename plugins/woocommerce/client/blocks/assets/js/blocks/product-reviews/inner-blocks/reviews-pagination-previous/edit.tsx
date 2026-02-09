/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, PlainText } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';

const arrowMap = {
	none: '',
	arrow: '←',
	chevron: '«',
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
			href="#reviews-pagination-previous-pseudo-link"
			onClick={ ( event ) => event.preventDefault() }
			{ ...useBlockProps() }
		>
			{ displayArrow && (
				<span
					className={ `wp-block-woocommerce-product-reviews-pagination-previous-arrow is-arrow-${ paginationArrow }` }
				>
					{ displayArrow }
				</span>
			) }
			<PlainText
				__experimentalVersion={ 2 }
				tagName="span"
				aria-label={ __( 'Older reviews page link', 'woocommerce' ) }
				placeholder={ __( 'Older Reviews', 'woocommerce' ) }
				value={ label }
				onChange={ ( newLabel ) =>
					setAttributes( { label: newLabel } )
				}
			/>
		</a>
	);
}
