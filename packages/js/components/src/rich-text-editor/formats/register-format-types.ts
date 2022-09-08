/**
 * External dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { register as registerBold } from './bold';
import { register as registerItalic } from './italic';
import { register as registerLink } from './link';

export const formatIsRegistered = ( formatName: string ) => {
	const registeredFormats = select( 'core/rich-text' ).getFormatTypes();
	return !! registeredFormats.find(
		( format ) => format.name === formatName
	);
};

export const registerFormatTypes = () => {
	[ registerBold, registerItalic, registerLink ].forEach( ( register ) =>
		register()
	);
};
