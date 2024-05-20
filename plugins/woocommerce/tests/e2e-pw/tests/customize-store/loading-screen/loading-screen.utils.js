const setupRequestInterceptor = ( page, requestToSetupStore ) => {
	page.on( 'request', ( request ) => {
		const url = request.url();
		const requestAssembler = Object.keys( requestToSetupStore ).find(
			( key ) => url.includes( key )
		);

		if ( requestToSetupStore[ requestAssembler ] !== undefined ) {
			requestToSetupStore[ requestAssembler ] = true;
		}
	} );
};

const createRequestsToSetupStoreDictionary = () => ( {
	'onboarding/themes/install?theme=twentytwentyfour': false,
	'onboarding/products': false,
	'themes/activate?theme=twentytwentyfour&theme_switch_via_cys_ai_loader': false,
} );

module.exports = {
	setupRequestInterceptor,
	createRequestsToSetupStoreDictionary,
};
