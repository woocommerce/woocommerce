module.exports = () => {
	const { API_RESULT, E2E_RESULT, ENV_SLUG, TEST_NAME, RELEASE_VERSION } =
		process.env;
	const { createElementBlock, initContextBlock } = require( './utils' );

	const contextBlock = initContextBlock( TEST_NAME );
	const apiElementBlock = createElementBlock( {
		testType: 'API',
		result: API_RESULT,
		envSlug: ENV_SLUG,
		releaseVersion: RELEASE_VERSION,
	} );
	const e2eElementBlock = createElementBlock( {
		testType: 'E2E',
		result: E2E_RESULT,
		envSlug: ENV_SLUG,
		releaseVersion: RELEASE_VERSION,
	} );

	contextBlock.elements.push( apiElementBlock );
	contextBlock.elements.push( e2eElementBlock );

	return contextBlock;
};
