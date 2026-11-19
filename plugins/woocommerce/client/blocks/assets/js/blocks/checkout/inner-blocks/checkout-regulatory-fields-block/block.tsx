/**
 * External dependencies
 */
import { noticeContexts } from '@woocommerce/base-context';
import {
	StoreNoticesContainer,
	ValidatedTextInput,
} from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { useRegulatoryFields } from './use-regulatory-fields';

const Block = (): JSX.Element => {
	const fields = useRegulatoryFields();
	const values = useSelect(
		( select ) => select( checkoutStore ).getRegulatoryFields(),
		[]
	);
	const { __internalSetRegulatoryFields } = useDispatch( checkoutStore );

	return (
		<>
			<StoreNoticesContainer
				context={ noticeContexts.REGULATORY_FIELDS }
			/>
			{ fields.map( ( field ) => (
				<ValidatedTextInput
					key={ field.id }
					id={ `regulatory-${ field.id }` }
					errorId={ `regulatory_${ field.id }` }
					type="text"
					label={ field.label }
					required={ field.required }
					value={ values[ field.id ] || '' }
					onChange={ ( value ) =>
						__internalSetRegulatoryFields( {
							[ field.id ]: value,
						} )
					}
				/>
			) ) }
		</>
	);
};

export default Block;
