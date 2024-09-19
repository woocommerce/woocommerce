/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import {
	DataViews,
	FieldType,
	SupportedLayouts,
	View,
} from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import {
	PRODUCT_FIELDS,
	PRODUCT_FIELDS_KEYS,
	PRODUCTS_DATA,
} from './utilites/product-data-view-data';

type ProductsViewProps = {
	productsData: typeof PRODUCTS_DATA;
	fields: {
		label: string;
		id: string;
		type: FieldType;
		options?: string[];
	}[];
	view: View;
	onChangeView: ( newView: View ) => void;
	paginationInfo: {
		totalPages: number;
		totalItems: number;
	};
	defaultLayouts: SupportedLayouts;
};

// ProductView component is just a wrapper around DataViews component. Currently, it is needed to experiment with the DataViews component in isolation.
// We expect that this component will be removed in the future, instead it will be used the component used in Products App.
const ProductsView = ( {
	fields,
	view,
	productsData,
	paginationInfo,
	defaultLayouts,
	onChangeView,
}: ProductsViewProps ) => {
	return (
		<DataViews
			data={ productsData }
			fields={ fields }
			view={ view }
			onChangeView={ onChangeView }
			paginationInfo={ paginationInfo }
			defaultLayouts={ defaultLayouts }
			getItemId={ ( item: ( typeof PRODUCTS_DATA )[ 0 ] ) => item.name }
		/>
	);
};

export default {
	title: 'Product App/Products View',
	component: ProductsView,
};

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - Improve typing.
const Template = ( args: unknown ) => <ProductsView { ...args } />;

export const Default = Template.bind( {} );
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - Improve typing.
Default.args = {
	productsData: PRODUCTS_DATA,
	fields: PRODUCT_FIELDS,
	view: {
		type: 'list',
		fields: PRODUCT_FIELDS_KEYS.filter(
			( field ) =>
				field !== 'downloads' &&
				field !== 'categories' &&
				field !== 'images'
		),
	},
	defaultLayouts: {
		list: {
			type: 'list',
		},
	},
	paginationInfo: {
		totalPages: 1,
		totalItems: 10,
	},
	onChangeView: () => {},
};
