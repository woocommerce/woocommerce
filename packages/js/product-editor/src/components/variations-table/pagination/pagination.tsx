/**
 * External dependencies
 */
import classNames from 'classnames';
import { __, sprintf } from '@wordpress/i18n';
import {
	PaginationPageSizePicker,
	PaginationPageArrowsWithPicker,
	usePagination,
} from '@woocommerce/components';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { PaginationProps } from './types';
import {
	DEFAULT_VARIATION_PER_PAGE_OPTION,
	DEFAULT_VARIATION_PER_PAGE_OPTIONS,
} from '../../../constants';

export function Pagination( {
	className,
	totalCount,
	perPageOptions = DEFAULT_VARIATION_PER_PAGE_OPTIONS,
	defaultPerPage = DEFAULT_VARIATION_PER_PAGE_OPTION,
	onPageChange,
	onPerPageChange,
}: PaginationProps ) {
	const paginationProps = usePagination( {
		defaultPerPage,
		totalCount,
		onPageChange,
		onPerPageChange,
	} );

	// translators: Viewing 1-5 of 100 items. First two %ds are a range of items that are shown on the screen. The last %d is the total amount of items that exist.
	const paginationLabel = __( 'Viewing %d-%d of %d items', 'woocommerce' );

	return (
		<div
			className={ classNames(
				className,
				'woocommerce-product-variations-pagination'
			) }
		>
			<div className="woocommerce-product-variations-pagination__info">
				{ sprintf(
					paginationLabel,
					paginationProps.start,
					paginationProps.end,
					totalCount
				) }
			</div>

			<div className="woocommerce-product-variations-pagination__current-page">
				<PaginationPageArrowsWithPicker { ...paginationProps } />
			</div>

			<div className="woocommerce-product-variations-pagination__page-size">
				<PaginationPageSizePicker
					{ ...paginationProps }
					total={ totalCount }
					perPageOptions={ perPageOptions }
					label=""
				/>
			</div>
		</div>
	);
}
