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

	return (
		<div
			className={ classNames(
				className,
				'woocommerce-product-variations-pagination'
			) }
		>
			<div className="woocommerce-product-variations-pagination__info">
				{ sprintf(
					__( 'Viewing %d-%d of %d items', 'woocommerce' ),
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
