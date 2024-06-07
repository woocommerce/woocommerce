const { createDocs } = require( './create-docs' );
const { generateHookName } = require( './generate-hook-name' );
const { generateIntroduction } = require( './generate-introduction' );
const { json2md } = require( './json2md' );
const { sectionWithHeading } = require( './section-with-heading' );
const { contentWithHeading } = require( './content-with-heading' );
const { generateToc } = require( './generate-toc' );

module.exports = {
	createDocs,
	generateHookName,
	generateIntroduction,
	json2md,
	sectionWithHeading,
	contentWithHeading,
	generateToc,
};
