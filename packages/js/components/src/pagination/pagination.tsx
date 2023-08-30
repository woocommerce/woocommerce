/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { PageArrows } from './page-arrows';
import { PagePicker } from './page-picker';
import { DEFAULT_PER_PAGE_OPTIONS, PageSizePicker } from './page-size-picker';

export type PaginationProps = {
	page: number;
	perPage: number;
	total: number;
	onPageChange?: (
		page: number,
		action?: 'previous' | 'next' | 'goto'
	) => void;
	onPerPageChange?: ( perPage: number ) => void;
	className?: string;
	showPagePicker?: boolean;
	showPerPagePicker?: boolean;
	showPageArrowsLabel?: boolean;
	perPageOptions?: number[];
};

export function Pagination( {
	page,
	onPageChange = () => {},
	total,
	perPage,
	onPerPageChange = () => {},
	showPagePicker = true,
	showPerPagePicker = true,
	showPageArrowsLabel = true,
	className,
	perPageOptions = DEFAULT_PER_PAGE_OPTIONS,
}: React.PropsWithChildren< PaginationProps > ) {
	const pageCount = Math.ceil( total / perPage );

	const classes = classNames( 'woocommerce-pagination', className );

	if ( pageCount <= 1 ) {
		return (
			( total > perPageOptions[ 0 ] && (
				<div className={ classes }>
					<PageSizePicker
						page={ page }
						perPage={ perPage }
						onPageChange={ onPageChange }
						total={ total }
						onPerPageChange={ onPerPageChange }
						perPageOptions={ perPageOptions }
					/>
				</div>
			) ) ||
			null
		);
	}

	return (
		<div className={ classes }>
			<PageArrows
				page={ page }
				pageCount={ pageCount }
				showPageArrowsLabel={ showPageArrowsLabel }
				onPageChange={ onPageChange }
			/>
			{ showPagePicker && (
				<PagePicker
					page={ page }
					pageCount={ pageCount }
					onPageChange={ onPageChange }
				/>
			) }
			{ showPerPagePicker && (
				<PageSizePicker
					page={ page }
					perPage={ perPage }
					onPageChange={ onPageChange }
					total={ total }
					onPerPageChange={ onPerPageChange }
					perPageOptions={ perPageOptions }
				/>
			) }
		</div>
	);
}
