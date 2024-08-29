/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Icon, chevronDown } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { PreviewDropdown } from '../../components/preview-dropdown';

type Props = {
	label: string;
};

export const AttributeDropdown = ( { label }: Props ) => {
	return (
		<div className="wc-block-attribute-filter style-dropdown">
			<PreviewDropdown
				placeholder={ sprintf(
					/* translators: %s attribute name. */
					__( 'Select %s', 'woocommerce' ),
					label
				) }
			/>
			<Icon icon={ chevronDown } size={ 30 } />
		</div>
	);
};
