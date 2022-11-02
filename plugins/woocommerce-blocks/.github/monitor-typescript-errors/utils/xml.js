const { XMLParser } = require( 'fast-xml-parser' );

exports.parseXml = ( filePath ) => {
	const parser = new XMLParser( {
		ignoreAttributes: false,
		attributeNamePrefix: '',
		attributesGroupName: '',
	} );
	const parsedFile = parser.parse( filePath );

	return getDataFromParsedXml( parsedFile );
};

const getErrorInfo = ( error ) => {
	const line = error.line;
	const column = error.column;
	const message = error.message;

	return {
		line,
		column,
		message,
	};
};

const getDataFromParsedXml = ( parsedXml ) => {
	const data = parsedXml.checkstyle.file;

	return data.reduce(
		( acc, { name, error } ) => {
			const pathFile = name;
			const hasMultipleErrors = Array.isArray( error );

			return {
				files: {
					[ pathFile ]: hasMultipleErrors
						? error.map( getErrorInfo )
						: [ getErrorInfo( error ) ],
					...acc.files,
				},
				totalErrors:
					acc.totalErrors + ( hasMultipleErrors ? error.length : 1 ),
				totalFilesWithErrors: acc.totalFilesWithErrors + 1,
			};
		},
		{
			totalErrors: 0,
			totalFilesWithErrors: 0,
		}
	);
};

exports.getFilesWithNewErrors = (
	newCheckStyleFileParsed,
	currentCheckStyleFileParsed
) => {
	const newFilesReport = newCheckStyleFileParsed.files;
	const currentFilesReport = currentCheckStyleFileParsed.files;

	return Object.keys( newFilesReport )
		.sort( ( a, b ) => a.localeCompare( b ) )
		.reduce(
			( acc, pathfile ) =>
				typeof currentFilesReport[ pathfile ] === 'undefined' ||
				currentFilesReport[ pathfile ] === null ||
				newFilesReport[ pathfile ].length >
					currentFilesReport[ pathfile ].length
					? [ ...acc, pathfile ]
					: acc,
			[]
		);
};
