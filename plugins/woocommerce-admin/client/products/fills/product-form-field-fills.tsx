/**
 * External dependencies
 */
import {
	__experimentalWooProductFieldItem as WooProductFieldItem,
	renderField,
	useFormContext,
} from '@woocommerce/components';
import { Product, ProductFormField } from '@woocommerce/data';

export const Fields: React.FC< { fields: ProductFormField[] } > = ( {
	fields,
} ) => {
	const { getInputProps } = useFormContext< Product >();

	return (
		<>
			{ fields.map( ( field ) => (
				<WooProductFieldItem
					key={ field.properties.name }
					id={ field.id }
					sections={ [ { name: field.section, order: field.order } ] }
					pluginId={ field.plugin_id }
				>
					<>
						{ renderField( field.type, {
							...getInputProps( field.properties.name ),
							...field.properties,
						} ) }
					</>
				</WooProductFieldItem>
			) ) }{ ' ' }
		</>
	);
};
