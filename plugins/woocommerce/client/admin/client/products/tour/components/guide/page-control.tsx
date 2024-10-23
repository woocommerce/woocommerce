/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PageControlIcon } from './icons';
import type { PageControlProps } from './types';

export default function PageControl( {
	currentPage,
	numberOfPages,
	setCurrentPage,
}: PageControlProps ) {
	return (
		<ul
			className="components-guide__page-control"
			aria-label={ __( 'Guide controls', 'woocommerce' ) }
		>
			{ Array.from( { length: numberOfPages } ).map( ( _, page ) => (
				<li
					key={ page }
					// Set aria-current="step" on the active page, see https://www.w3.org/TR/wai-aria-1.1/#aria-current
					aria-current={ page === currentPage ? 'step' : undefined }
				>
					<Button
						key={ page }
						icon={
							<PageControlIcon
								isSelected={ page === currentPage }
							/>
						}
						aria-label={ sprintf(
							/* translators: 1: current page number 2: total number of pages */
							__( 'Page %1$d of %2$d', 'woocommerce' ),
							page + 1,
							numberOfPages
						) }
						onClick={ () => setCurrentPage( page ) }
					/>
				</li>
			) ) }
		</ul>
	);
}
