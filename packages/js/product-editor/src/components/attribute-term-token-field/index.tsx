/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { FormTokenField } from '@wordpress/components';
import { createElement } from '@wordpress/element';

export default function AttributeTermTokenField() {
	return (
		<FormTokenField
			label={ __( 'Attributes', 'woocommerce' ) }
			value={ [] }
			onChange={ () => {} }
			suggestions={ [] }
			onInputChange={ () => {} }
			disabled={ false }
		/>
	);
}
