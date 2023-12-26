/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
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
	children?: ( props: { pageCount: number } ) => JSX.Element;
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
	children,
}: PaginationProps ): JSX.Element | null {
	const pageCount = Math.ceil( total / perPage );

	if ( children && typeof children === 'function' ) {
		return children( {
			pageCount,
		} );
	}

	const classes = classNames( 'woocommerce-pagination', className );

	if ( pageCount <= 1 ) {
		return (
			( total > perPageOptions[ 0 ] && (
				<div className={ classes }>
					<PageSizePicker
						currentPage={ page }
						perPage={ perPage }
						setCurrentPage={ onPageChange }
						total={ total }
						setPerPageChange={ onPerPageChange }
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
				currentPage={ page }
				pageCount={ pageCount }
				showPageArrowsLabel={ showPageArrowsLabel }
				setCurrentPage={ onPageChange }
			/>
			{ showPagePicker && (
				<PagePicker
					currentPage={ page }
					pageCount={ pageCount }
					setCurrentPage={ onPageChange }
				/>
			) }
			{ showPerPagePicker && (
				<PageSizePicker
					currentPage={ page }
					perPage={ perPage }
					setCurrentPage={ onPageChange }
					total={ total }
					setPerPageChange={ onPerPageChange }
					perPageOptions={ perPageOptions }
				/>
			) }
		</div>
	);
}
