/**
 * External dependencies
 */
import { Sender } from 'xstate';
/**
 * Internal dependencies
 */
import { updateTemplate } from '../data/actions';
import { HOMEPAGE_TEMPLATES } from '../data/homepageTemplates';

const assembleSite = async () => {
	await updateTemplate( {
		homepageTemplateId: 'template1' as keyof typeof HOMEPAGE_TEMPLATES,
	} );
};

const browserPopstateHandler =
	() => ( sendBack: Sender< { type: 'EXTERNAL_URL_UPDATE' } > ) => {
		const popstateHandler = () => {
			sendBack( { type: 'EXTERNAL_URL_UPDATE' } );
		};
		window.addEventListener( 'popstate', popstateHandler );
		return () => {
			window.removeEventListener( 'popstate', popstateHandler );
		};
	};

export const services = {
	assembleSite,
	browserPopstateHandler,
};
