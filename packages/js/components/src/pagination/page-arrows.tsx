/**
 * External dependencies
 */
import { Button, Icon } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { chevronLeft, chevronRight } from '@wordpress/icons';
import { sprintf, __ } from '@wordpress/i18n';
import classNames from 'classnames';

type PageArrowsProps = {
	page: number;
	pageCount: number;
	showPageArrowsLabel?: boolean;
	onPageChange: (
		page: number,
		action?: 'previous' | 'next' | 'goto'
	) => void;
};

export function PageArrows( {
	pageCount,
	page,
	showPageArrowsLabel = true,
	onPageChange,
}: PageArrowsProps ) {
	function previousPage( event: React.MouseEvent ) {
		event.stopPropagation();
		if ( page - 1 < 1 ) {
			return;
		}
		onPageChange( page - 1, 'previous' );
	}

	function nextPage( event: React.MouseEvent ) {
		event.stopPropagation();
		if ( page + 1 > pageCount ) {
			return;
		}
		onPageChange( page + 1, 'next' );
	}

	if ( pageCount <= 1 ) {
		return null;
	}

	const previousLinkClass = classNames( 'woocommerce-pagination__link', {
		'is-active': page > 1,
	} );

	const nextLinkClass = classNames( 'woocommerce-pagination__link', {
		'is-active': page < pageCount,
	} );

	return (
		<div className="woocommerce-pagination__page-arrows">
			{ showPageArrowsLabel && (
				<span
					className="woocommerce-pagination__page-arrows-label"
					role="status"
					aria-live="polite"
				>
					{ sprintf(
						__( 'Page %d of %d', 'woocommerce' ),
						page,
						pageCount
					) }
				</span>
			) }
			<div className="woocommerce-pagination__page-arrows-buttons">
				<Button
					className={ previousLinkClass }
					disabled={ ! ( page > 1 ) }
					onClick={ previousPage }
					label={ __( 'Previous Page', 'woocommerce' ) }
				>
					<Icon icon={ chevronLeft } />
				</Button>
				<Button
					className={ nextLinkClass }
					disabled={ ! ( page < pageCount ) }
					onClick={ nextPage }
					label={ __( 'Next Page', 'woocommerce' ) }
				>
					<Icon icon={ chevronRight } />
				</Button>
			</div>
		</div>
	);
}
