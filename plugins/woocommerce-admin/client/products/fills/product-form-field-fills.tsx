/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	__experimentalWooProductFieldItem as WooProductFieldItem,
	renderField,
	useFormContext,
} from '@woocommerce/components';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { Field } from './types';

export const Fields: React.FC< { fields: Field[] } > = ( { fields } ) => {
	const { getInputProps } = useFormContext< Product >();

	return (
		<>
			{ fields.map( ( field ) => (
				<WooProductFieldItem
					key={ field.properties.name }
					id={ field.id }
					section={ field.section }
					pluginId={ field.plugin_id }
					order={ field.order }
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
