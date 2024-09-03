/**
 * External dependencies
 */
import { createElement, useState } from '@wordpress/element';
import { DataForm, FieldType, Form } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import {
	PRODUCT_FIELDS,
	PRODUCT_FIELDS_KEYS,
	PRODUCTS_DATA,
} from './utilites/product-data-view-data';

type ProductFormProps = {
	productData: ( typeof PRODUCTS_DATA )[ 0 ];
	fields: {
		label: string;
		id: string;
		type: FieldType;
		options?: string[];
		Edit?: () => JSX.Element;
	}[];
	form: Form;
};

// ProductForm component is just a wrapper around DataViews component. Currently, it is needed to experiment with the DataViews component in isolation.
// We expect that this component will be removed in the future, instead it will be used the component used in Products App.
const ProductForm = ( { fields, form, productData }: ProductFormProps ) => {
	const [ product, setProduct ] = useState( productData );
	return (
		<DataForm
			data={ product }
			fields={ fields }
			form={ form }
			onChange={ ( newProduct ) =>
				setProduct( ( prev ) => ( {
					...prev,
					...newProduct,
				} ) )
			}
		/>
	);
};

export default {
	title: 'Product App/Product Form',
	component: ProductForm,
};

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - improve typing.
const Template = ( args: unknown ) => <ProductForm { ...args } />;

export const Default = Template.bind( {} );

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore - Improve typing.
Default.args = {
	productData: PRODUCTS_DATA[ 0 ],
	fields: PRODUCT_FIELDS,
	form: {
		type: 'regular',
		fields: PRODUCT_FIELDS_KEYS,
	},
};
