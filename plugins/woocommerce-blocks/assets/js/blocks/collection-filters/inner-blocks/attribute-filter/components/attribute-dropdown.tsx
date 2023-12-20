/**
 * External dependencies
 */
import FormTokenField from '@woocommerce/base-components/form-token-field';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, chevronDown } from '@wordpress/icons';

type Props = {
	label: string;
};
export const AttributeDropdown = ( { label }: Props ) => (
	<div className="wc-block-attribute-filter style-dropdown">
		<FormTokenField
			suggestions={ [] }
			placeholder={ sprintf(
				/* translators: %s attribute name. */
				__( 'Select %s', 'woocommerce' ),
				label
			) }
			onChange={ () => null }
			value={ [] }
		/>
		<Icon icon={ chevronDown } size={ 30 } />
	</div>
);
