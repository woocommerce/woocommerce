/**
 * External dependencies
 */
import { useFormContext, useFormContext2 } from '@woocommerce/components';
import { useController } from 'react-hook-form';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { Attributes } from '../../fields/attributes';

export const AttributesField = () => {
	const { control, getValues } = useFormContext2< Product >();
	const { field } = useController( {
		name: 'attributes',
		control,
	} );

	return <Attributes { ...field } productId={ getValues( 'id' ) } />;
};
