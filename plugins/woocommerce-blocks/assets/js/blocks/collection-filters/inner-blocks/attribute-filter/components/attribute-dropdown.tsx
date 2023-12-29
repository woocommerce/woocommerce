/**
 * External dependencies
 */
import styled from '@emotion/styled';
import FormTokenField from '@woocommerce/base-components/form-token-field';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, chevronDown } from '@wordpress/icons';

type Props = {
	label: string;
	textColor: string;
};

export const AttributeDropdown = ( { label, textColor }: Props ) => {
	const StyledFormTokenField = textColor
		? styled( FormTokenField )`
				.components-form-token-field__input::placeholder {
					color: ${ textColor } !important;
				}
		  `
		: FormTokenField;

	return (
		<div className="wc-block-attribute-filter style-dropdown">
			<StyledFormTokenField
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
};
