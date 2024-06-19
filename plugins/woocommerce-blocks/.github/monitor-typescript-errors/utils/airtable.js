const axios = require( 'axios' ).default;

const BASE_URL = 'https://api.airtable.com/v0';
const TABLE_ID = 'appIIlxUVxOks06sZ';
const API_KEY = process.env[ 'AIRTABLE_API_KEY' ];

const TABLE_NAME = 'TypeScript Migration';
const TYPESCRIPT_ERRORS_COLUMN_NAME = 'TypeScript Errors';
const DATE_COLUMN_NAME = 'Date';

// https://community.airtable.com/t/datetime-date-field-woes/32121
const generateDateValueForAirtable = () => {
	const today = new Date();
	const string = today.toLocaleDateString();

	return new Date( string );
};

exports.addRecord = async ( errorsNumber ) =>
	axios.post(
		`${ BASE_URL }/${ TABLE_ID }/${ TABLE_NAME }`,
		{
			records: [
				{
					fields: {
						[ TYPESCRIPT_ERRORS_COLUMN_NAME ]: errorsNumber,
						[ DATE_COLUMN_NAME ]: generateDateValueForAirtable(),
					},
				},
			],
			typecast: true,
		},
		{
			headers: {
				Authorization: `Bearer ${ API_KEY }`,
			},
		}
	);
