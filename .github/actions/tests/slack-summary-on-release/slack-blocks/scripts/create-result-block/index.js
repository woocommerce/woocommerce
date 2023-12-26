module.exports = () => {
	const { API_RESULT, E2E_RESULT, ENV_SLUG, TEST_NAME, RELEASE_VERSION } =
		process.env;
	const { setElementText } = require( './utils' );

	const apiLinkText = setElementText( {
		testType: 'API',
		result: API_RESULT,
		envSlug: ENV_SLUG,
		releaseVersion: RELEASE_VERSION,
	} );
	const e2eLinkText = setElementText( {
		testType: 'E2E',
		result: E2E_RESULT,
		envSlug: ENV_SLUG,
		releaseVersion: RELEASE_VERSION,
	} );
	const elementText = `*${ TEST_NAME }*\n    ${ apiLinkText }    ${ e2eLinkText }`;

	const contextBlock = {
		type: 'context',
		elements: [
			{
				type: 'mrkdwn',
				text: elementText,
			},
		],
	};

	return contextBlock;
};
