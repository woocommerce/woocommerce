/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { ProductAttribute } from '@woocommerce/data';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import './attribute-field.scss';
import AttributeEmptyStateLogo from './attribute-empty-state-logo.svg';

type AttributeFieldProps = {
	value: ProductAttribute[];
	onChange: ( value: ProductAttribute[] ) => void;
};

export const AttributeField: React.FC< AttributeFieldProps > = ( {
	value,
	onChange,
} ) => {
	if ( ! value || value.length === 0 ) {
		return (
			<div className="woocommerce-attribute-field">
				<div className="woocommerce-attribute-field__empty-container">
					<img
						src={ AttributeEmptyStateLogo }
						alt="Completed"
						className="woocommerce-attribute-field__empty-logo"
					/>
					<Text
						variant="subtitle.small"
						weight="600"
						size="14"
						lineHeight="20px"
						className="woocommerce-attribute-field__empty-subtitle"
					>
						{ __( 'No attributes yet', 'woocommerce' ) }
					</Text>
					<Button
						variant="secondary"
						className="woocommerce-attribute-field__add-new"
						disabled={ true }
					>
						{ __( 'Add first attribute', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		);
	}
	return <div className="woocommerce-attribute-field"></div>;
};
