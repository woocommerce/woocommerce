/**
 * External dependencies
 */
import { usePaginationProps } from '@woocommerce/components';

export type PaginationProps = usePaginationProps & {
	className?: string;
	perPageOptions?: number[];
	defaultPerPage?: number;
};
