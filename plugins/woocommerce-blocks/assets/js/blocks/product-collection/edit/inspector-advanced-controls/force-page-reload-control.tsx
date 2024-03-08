/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
// import { QueryControlProps } from '../../types';

const ForcePageReloadControl = ( props ) => {
	console.log( props );
	return (
		<ToggleControl
			label={ __( 'Force Page Reload', 'woocommerce' ) }
			help={ __(
				'Enforce full page reload on certain interactions, like using paginations controls.'
			) }
			checked={ false }
			onChange={ () => true }
		/>
	);
};

export default ForcePageReloadControl;
