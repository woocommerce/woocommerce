/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { useIsEmailEditor } from '@woocommerce/email-editor';

/**
 * Internal dependencies
 */
import { registerEmailCollections } from '../collections';

/**
 * Custom hook to register email-only product collections when in the email editor.
 * This ensures that email-specific collections like "Cart Contents" are only
 * available in the email editor context.
 */
export const useRegisterEmailCollections = () => {
	const isEmailEditor = useIsEmailEditor();

	useEffect( () => {
		if ( isEmailEditor ) {
			registerEmailCollections();
		}
	}, [ isEmailEditor ] );
};
