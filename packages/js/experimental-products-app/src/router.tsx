/**
 * External dependencies
 */
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useEffect, useMemo, useState } from '@wordpress/element';
import type { View } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { unlock } from './lock-unlock';
import ProductList from './product-list';
import ProductEdit from './product-edit';
import type { ProductEntityRecord } from './fields/types';
import { DEFAULT_VIEW } from './product-list/constants';
import { buildProductListQuery } from './product-list/query';
import {
	getProductListTab,
	getStatusForProductListTab,
} from './product-list/utils';

const { useLocation } = unlock( routerPrivateApis );

export type Route = {
	key: string;
	areas: {
		sidebar?: React.JSX.Element;
		content?: React.JSX.Element;
		edit?: React.JSX.Element;
		mobile?: React.JSX.Element | boolean;
		preview?: boolean;
	};
	widths?: {
		content?: number;
		edit?: number;
		sidebar?: number;
	};
};

export default function useLayoutAreas() {
	const { params = {}, query = {} } = useLocation();
	const postType = params.postType ?? query.postType ?? 'product';
	const activeView = query.activeView as string | undefined;
	const selectedTabFromLocation = getProductListTab( activeView );
	const [ selectedTab, setSelectedTab ] = useState( selectedTabFromLocation );
	const [ view, setView ] = useState< View >( DEFAULT_VIEW );
	const showQuickEdit =
		params.quickEdit === 'true' ||
		query.quickEdit === 'true' ||
		params.quickEdit === true ||
		query.quickEdit === true;

	useEffect( () => {
		setSelectedTab( selectedTabFromLocation );
	}, [ selectedTabFromLocation ] );

	useEffect( () => {
		setView( DEFAULT_VIEW );
	}, [ activeView ] );

	const queryParams = useMemo( () => {
		const productListQuery = buildProductListQuery( view );
		const productStatus = getStatusForProductListTab( selectedTab );

		if ( productStatus ) {
			productListQuery.status = productStatus;
		}

		return productListQuery;
	}, [ selectedTab, view ] );

	const {
		records,
		totalItems: totalCount,
		isResolving: isLoading,
		hasResolved,
	} = useSelect(
		( select ) => {
			const {
				getEntityRecords,
				isResolving,
				hasFinishedResolution,
				getEntityRecordsTotalItems,
			} = select( coreStore );

			return {
				records: getEntityRecords< ProductEntityRecord >(
					'root',
					'product',
					queryParams
				),
				totalItems: getEntityRecordsTotalItems( 'root', 'product', {
					...queryParams,
				} ),
				isResolving: isResolving( 'getEntityRecords', [
					'root',
					'product',
					queryParams,
				] ),
				hasResolved: hasFinishedResolution( 'getEntityRecords', [
					'root',
					'product',
					queryParams,
				] ),
			};
		},
		[ queryParams ]
	);

	const productListProps = {
		hasResolved,
		isLoading,
		records,
		selectedTab,
		setSelectedTab,
		setView,
		totalCount,
		view,
	};

	return {
		key: 'products-list',
		areas: {
			content: <ProductList { ...productListProps } />,
			edit: (
				<ProductEdit
					products={ records ?? [] }
					isOpen={ showQuickEdit }
				/>
			),
			preview: false,
			mobile: (
				<ProductList postType={ postType } { ...productListProps } />
			),
		},
		widths: {
			edit: 380,
		},
	};
}
