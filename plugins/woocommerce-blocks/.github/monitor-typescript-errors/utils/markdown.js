exports.generateMarkdownMessage = ( dataFromParsedXml ) => {
	const header = generateHeader( dataFromParsedXml );
	const body = generateBody( dataFromParsedXml );

	return { header, body };
};

const generateHeader = ( dataFromParsedXml ) => {
	return `
## TypeScript Errors Report

- Files with errors: ${ dataFromParsedXml.totalFilesWithErrors }
- Total errors: ${ dataFromParsedXml.totalErrors }
`;
};

const generateBody = ( dataFromParsedXml ) => {
	const files = dataFromParsedXml.files;

	return Object.keys( files ).map( ( file ) => {
		return `
Files with errors:
	File: ${ file }
		${ files[ file ].map( ( error ) => `- ${ error }` ).join( '\r\n' ) }
		`;
	} );
};
