const setElementText = ( { testType, result, envSlug, releaseVersion } ) => {
	const { selectEmoji } = require( './select-emoji' );
	const allureReportURL = `https://woocommerce.github.io/woocommerce-test-reports/release/${ releaseVersion }/${ envSlug }/${ testType.toLowerCase() }`;
	const emoji = selectEmoji( result );
	const textValue = `<${ allureReportURL }|${ testType.toUpperCase() } ${ emoji }>`;

	return textValue;
};

module.exports = {
	setElementText,
};
