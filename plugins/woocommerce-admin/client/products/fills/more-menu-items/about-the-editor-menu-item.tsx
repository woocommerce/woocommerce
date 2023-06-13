/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { MenuItem } from '@wordpress/components';
import { info, Icon } from '@wordpress/icons';

export const AboutTheEditorMenuItem = () => {
	return (
		<MenuItem
			onClick={ () => {} }
			icon={ <Icon icon={ info } /> }
			iconPosition="right"
		>
			{ __( 'About the editor…', 'woocommerce' ) }
		</MenuItem>
	);
};
