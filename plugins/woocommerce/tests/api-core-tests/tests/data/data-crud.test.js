const { test, expect } = require( '@playwright/test' );
const { API_BASE_URL } = process.env;
const shouldSkip = API_BASE_URL != undefined;

// 259 countries total
const countryCodes = [
	'af',
	'ax',
	'al',
	'dz',
	'as',
	'ad',
	'ao',
	'ai',
	'aq',
	'ag',
	'ar',
	'am',
	'aw',
	'au',
	'at',
	'az',
	'bs',
	'bh',
	'bd',
	'bb',
	'by',
	'pw',
	'be',
	'bz',
	'bj',
	'bm',
	'bt',
	'bo',
	'bq',
	'ba',
	'bw',
	'bv',
	'br',
	'io',
	'bn',
	'bg',
	'bf',
	'bi',
	'kh',
	'cm',
	'ca',
	'cv',
	'ky',
	'cf',
	'td',
	'cl',
	'cn',
	'cx',
	'cc',
	'co',
	'km',
	'cg',
	'cd',
	'ck',
	'cr',
	'hr',
	'cu',
	'cw',
	'cy',
	'cz',
	'dk',
	'dj',
	'dm',
	'do',
	'ec',
	'eg',
	'sv',
	'gq',
	'er',
	'ee',
	'sz',
	'et',
	'fk',
	'fo',
	'fj',
	'fi',
	'fr',
	'gf',
	'pf',
	'tf',
	'ga',
	'gm',
	'ge',
	'de',
	'gh',
	'gi',
	'gr',
	'gl',
	'gd',
	'gp',
	'gu',
	'gt',
	'gg',
	'gn',
	'gw',
	'gy',
	'ht',
	'hm',
	'hn',
	'hk',
	'hu',
	'is',
	'in',
	'id',
	'ir',
	'iq',
	'ie',
	'im',
	'il',
	'it',
	'ci',
	'jm',
	'jp',
	'je',
	'jo',
	'kz',
	'ke',
	'ki',
	'kw',
	'kg',
	'la',
	'lv',
	'lb',
	'ls',
	'lr',
	'ly',
	'li',
	'lt',
	'lu',
	'mo',
	'mg',
	'mw',
	'my',
	'mv',
	'ml',
	'mt',
	'mh',
	'mq',
	'mr',
	'mu',
	'yt',
	'mx',
	'fm',
	'md',
	'mc',
	'mn',
	'me',
	'ms',
	'ma',
	'mz',
	'mm',
	'na',
	'nr',
	'np',
	'nl',
	'nc',
	'nz',
	'ni',
	'ne',
	'ng',
	'nu',
	'nf',
	'kp',
	'mk',
	'mp',
	'no',
	'om',
	'pk',
	'ps',
	'pa',
	'pg',
	'py',
	'pe',
	'ph',
	'pn',
	'pl',
	'pt',
	'pr',
	'qa',
	're',
	'ro',
	'ru',
	'rw',
	'st',
	'bl',
	'sh',
	'kn',
	'lc',
	'sx',
	'mf',
	'pm',
	'vc',
	'ws',
	'sm',
	'sa',
	'sn',
	'rs',
	'sc',
	'sl',
	'sg',
	'sk',
	'si',
	'sb',
	'so',
	'za',
	'gs',
	'kr',
	'ss',
	'es',
	'lk',
	'sd',
	'sr',
	'sj',
	'se',
	'ch',
	'sy',
	'tw',
	'tj',
	'tz',
	'th',
	'tl',
	'tg',
	'tk',
	'to',
	'tt',
	'tn',
	'tr',
	'tm',
	'tc',
	'tv',
	'ug',
	'ua',
	'ae',
	'gb',
	'us',
	'um',
	'uy',
	'uz',
	'vu',
	'va',
	've',
	'vn',
	'vg',
	'vi',
	'wf',
	'eh',
	'ye',
	'zm',
	'zw',
];

/**
 * Tests for the WooCommerce Refunds API.
 *
 * @group api
 * @group data
 *
 */
test.describe( 'Data API tests', () => {
	test( 'can list all data', async ( { request } ) => {
		// call API to retrieve data values
		const response = await request.get( '/wp-json/wc/v3/data' );
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( true );

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					slug: 'continents',
					description:
						'List of supported continents, countries, and states.',
				} ),

				expect.objectContaining( {
					slug: 'countries',
					description: 'List of supported states in a given country.',
				} ),

				expect.objectContaining( {
					slug: 'currencies',
					description: 'List of supported currencies.',
				} ),
			] )
		);
	} );

	test( 'can view all continents', async ( { request } ) => {
		// call API to retrieve all continents
		const response = await request.get( '/wp-json/wc/v3/data/continents' );
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( true );

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'AF',
					name: 'Africa',
					countries: [
						{
							code: 'AO',
							name: 'Angolan Kwanza',
							currency_code: 'AOA',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'BF',
							name: 'West African CFA Franc',
							currency_code: 'XOF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BI',
							name: 'Burundian Franc',
							currency_code: 'BIF',
							currency_pos: 'right',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BJ',
							name: 'West African CFA Franc',
							currency_code: 'XOF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'BW',
							name: 'Botswanan Pula',
							currency_code: 'BWP',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'CD',
							name: 'Congolese Franc',
							currency_code: 'CDF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'CF',
							name: 'Central African CFA Franc',
							currency_code: 'XAF',
							currency_pos: 'left',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'CG',
							name: 'Central African CFA Franc',
							currency_code: 'XAF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'CI',
							name: 'West African CFA Franc',
							currency_code: 'XOF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'CM',
							name: 'Central African CFA Franc',
							currency_code: 'XAF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'CV',
							name: 'Cape Verdean Escudo',
							currency_code: 'CVE',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'DJ',
							name: 'Djiboutian Franc',
							currency_code: 'DJF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'DZ',
							name: 'Algerian Dinar',
							currency_code: 'DZD',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'EG',
							name: 'Egyptian Pound',
							currency_code: 'EGP',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'EH',
							name: 'Moroccan Dirham',
							currency_code: 'MAD',
							currency_pos: 'right_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'ER',
							name: 'Eritrean Nakfa',
							currency_code: 'ERN',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'ET',
							name: 'Ethiopian Birr',
							currency_code: 'ETB',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GA',
							name: 'Central African CFA Franc',
							currency_code: 'XAF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GH',
							name: 'Ghanaian Cedi',
							currency_code: 'GHS',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'GM',
							name: 'Gambian Dalasi',
							currency_code: 'GMD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GN',
							name: 'Guinean Franc',
							currency_code: 'GNF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GQ',
							name: 'Central African CFA Franc',
							currency_code: 'XAF',
							currency_pos: 'left',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GW',
							name: 'West African CFA Franc',
							currency_code: 'XOF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'KE',
							name: 'Kenyan Shilling',
							currency_code: 'KES',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'KM',
							name: 'Comorian Franc',
							currency_code: 'KMF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'LR',
							name: 'Liberian Dollar',
							currency_code: 'LRD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'LS',
							name: 'Lesotho Loti',
							currency_code: 'LSL',
							currency_pos: 'left',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'LY',
							name: 'Libyan Dinar',
							currency_code: 'LYD',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 3,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MA',
							name: 'Moroccan Dirham',
							currency_code: 'MAD',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MG',
							name: 'Malagasy Ariary',
							currency_code: 'MGA',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'ML',
							name: 'West African CFA Franc',
							currency_code: 'XOF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MR',
							name: 'Mauritanian Ouguiya',
							currency_code: 'MRU',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MU',
							name: 'Mauritian Rupee',
							currency_code: 'MUR',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MW',
							name: 'Malawian Kwacha',
							currency_code: 'MWK',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MZ',
							name: 'Mozambican Metical',
							currency_code: 'MZN',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'NA',
							name: 'Namibian Dollar',
							currency_code: 'NAD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'NE',
							name: 'West African CFA Franc',
							currency_code: 'XOF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'NG',
							name: 'Nigerian Naira',
							currency_code: 'NGN',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'RE',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'RW',
							name: 'Rwandan Franc',
							currency_code: 'RWF',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SC',
							name: 'Seychellois Rupee',
							currency_code: 'SCR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SD',
							name: 'Sudanese Pound',
							currency_code: 'SDG',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SH',
							name: 'St. Helena Pound',
							currency_code: 'SHP',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SL',
							name: 'Sierra Leonean Leone',
							currency_code: 'SLE',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SN',
							name: 'West African CFA Franc',
							currency_code: 'XOF',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'SO',
							name: 'Somali Shilling',
							currency_code: 'SOS',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SS',
							name: 'South Sudanese Pound',
							currency_code: 'SSP',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'ST',
							name: 'São Tomé & Príncipe Dobra',
							currency_code: 'STN',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SZ',
							name: 'Swazi Lilangeni',
							currency_code: 'SZL',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TD',
							name: 'Central African CFA Franc',
							currency_code: 'XAF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TG',
							name: 'West African CFA Franc',
							currency_code: 'XOF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TN',
							name: 'Tunisian Dinar',
							currency_code: 'TND',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 3,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TZ',
							name: 'Tanzanian Shilling',
							currency_code: 'TZS',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'UG',
							name: 'Ugandan Shilling',
							currency_code: 'UGX',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'YT',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'ZA',
							name: 'South African Rand',
							currency_code: 'ZAR',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'ZM',
							name: 'Zambian Kwacha',
							currency_code: 'ZMW',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'ZW',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
					],
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'AN',
					name: 'Antarctica',
					countries: [
						{
							code: 'AQ',
							name: 'Antarctica',
							states: [],
						},
						{
							code: 'BV',
							name: 'Bouvet Island',
							states: [],
						},
						{
							code: 'GS',
							name: 'South Georgia/Sandwich Islands',
							states: [],
						},
						{
							code: 'HM',
							name: 'Heard Island and McDonald Islands',
							states: [],
						},
						{
							code: 'TF',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
					],
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'AS',
					name: 'Asia',
					countries: [
						{
							code: 'AE',
							name: 'United Arab Emirates Dirham',
							currency_code: 'AED',
							currency_pos: 'right_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'AF',
							name: 'Afghan Afghani',
							currency_code: 'AFN',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'AM',
							name: 'Armenian Dram',
							currency_code: 'AMD',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'AZ',
							name: 'Azerbaijani Manat',
							currency_code: 'AZN',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BD',
							name: 'Bangladeshi Taka',
							currency_code: 'BDT',
							currency_pos: 'right',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'BH',
							name: 'Bahraini Dinar',
							currency_code: 'BHD',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 3,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BN',
							name: 'Brunei Dollar',
							currency_code: 'BND',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BT',
							name: 'Bhutanese Ngultrum',
							currency_code: 'BTN',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'CC',
							name: 'Australian Dollar',
							currency_code: 'AUD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'CN',
							name: 'Chinese Yuan',
							currency_code: 'CNY',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'CX',
							name: 'Australian Dollar',
							currency_code: 'AUD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'CY',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GE',
							name: 'Georgian Lari',
							currency_code: 'GEL',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'HK',
							name: 'Hong Kong Dollar',
							currency_code: 'HKD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'ID',
							name: 'Indonesian Rupiah',
							currency_code: 'IDR',
							currency_pos: 'left',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'IL',
							name: 'Israeli New Shekel',
							currency_code: 'ILS',
							currency_pos: 'right_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'IN',
							name: 'Indian Rupee',
							currency_code: 'INR',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'IO',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'IQ',
							name: 'Iraqi Dinar',
							currency_code: 'IQD',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'IR',
							name: 'Iranian Rial',
							currency_code: 'IRR',
							currency_pos: 'left',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'JO',
							name: 'Jordanian Dinar',
							currency_code: 'JOD',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 3,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'JP',
							name: 'Japanese Yen',
							currency_code: 'JPY',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'KG',
							name: 'Kyrgystani Som',
							currency_code: 'KGS',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'KH',
							name: 'Cambodian Riel',
							currency_code: 'KHR',
							currency_pos: 'right',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'KP',
							name: 'North Korean Won',
							currency_code: 'KPW',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'KR',
							name: 'South Korean Won',
							currency_code: 'KRW',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'KW',
							name: 'Kuwaiti Dinar',
							currency_code: 'KWD',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 3,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'KZ',
							name: 'Kazakhstani Tenge',
							currency_code: 'KZT',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'LA',
							name: 'Laotian Kip',
							currency_code: 'LAK',
							currency_pos: 'left',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'LB',
							name: 'Lebanese Pound',
							currency_code: 'LBP',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'LK',
							name: 'Sri Lankan Rupee',
							currency_code: 'LKR',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MM',
							name: 'Myanmar Kyat',
							currency_code: 'MMK',
							currency_pos: 'right_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MN',
							name: 'Mongolian Tugrik',
							currency_code: 'MNT',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MO',
							name: 'Macanese Pataca',
							currency_code: 'MOP',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MV',
							name: 'Maldivian Rufiyaa',
							currency_code: 'MVR',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MY',
							name: 'Malaysian Ringgit',
							currency_code: 'MYR',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'NP',
							name: 'Nepalese Rupee',
							currency_code: 'NPR',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'OM',
							name: 'Omani Rial',
							currency_code: 'OMR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 3,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'PH',
							name: 'Philippine Peso',
							currency_code: 'PHP',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'PK',
							name: 'Pakistani Rupee',
							currency_code: 'PKR',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'PS',
							name: 'Israeli New Shekel',
							currency_code: 'ILS',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'QA',
							name: 'Qatari Riyal',
							currency_code: 'QAR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SA',
							name: 'Saudi Riyal',
							currency_code: 'SAR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SG',
							name: 'Singapore Dollar',
							currency_code: 'SGD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SY',
							name: 'Syrian Pound',
							currency_code: 'SYP',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TH',
							name: 'Thai Baht',
							currency_code: 'THB',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'TJ',
							name: 'Tajikistani Somoni',
							currency_code: 'TJS',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TL',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TM',
							name: 'Turkmenistani Manat',
							currency_code: 'TMT',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TW',
							name: 'New Taiwan Dollar',
							currency_code: 'TWD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'UZ',
							name: 'Uzbekistani Som',
							currency_code: 'UZS',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'VN',
							name: 'Vietnamese Dong',
							currency_code: 'VND',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'YE',
							name: 'Yemeni Rial',
							currency_code: 'YER',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
					],
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'EU',
					name: 'Europe',
					countries: [
						{
							code: 'AD',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'AL',
							name: 'Albanian Lek',
							currency_code: 'ALL',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'AT',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'AX',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BA',
							name: 'Bosnia-Herzegovina Convertible Mark',
							currency_code: 'BAM',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BE',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BG',
							name: 'Bulgarian Lev',
							currency_code: 'BGN',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'BY',
							name: 'Belarusian Ruble',
							currency_code: 'BYN',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'CH',
							name: 'Swiss Franc',
							currency_code: 'CHF',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: "'",
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'CZ',
							name: 'Czech Koruna',
							currency_code: 'CZK',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'DE',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'DK',
							name: 'Danish Krone',
							currency_code: 'DKK',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'EE',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'ES',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'FI',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'FO',
							name: 'Danish Krone',
							currency_code: 'DKK',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'FR',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GB',
							name: 'British Pound',
							currency_code: 'GBP',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'foot',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'oz',
							states: [],
						},
						{
							code: 'GG',
							name: 'British Pound',
							currency_code: 'GBP',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GI',
							name: 'Gibraltar Pound',
							currency_code: 'GIP',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GR',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'HR',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'HU',
							name: 'Hungarian Forint',
							currency_code: 'HUF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'IE',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'IM',
							name: 'British Pound',
							currency_code: 'GBP',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'IS',
							name: 'Icelandic Króna',
							currency_code: 'ISK',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'IT',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'JE',
							name: 'British Pound',
							currency_code: 'GBP',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'LI',
							name: 'Swiss Franc',
							currency_code: 'CHF',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: "'",
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'LT',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'LU',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'LV',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MC',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MD',
							name: 'Moldovan Leu',
							currency_code: 'MDL',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'ME',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MK',
							name: 'Macedonian Denar',
							currency_code: 'MKD',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MT',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'NL',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'NO',
							name: 'Norwegian Krone',
							currency_code: 'NOK',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'PL',
							name: 'Polish Zloty',
							currency_code: 'PLN',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'PT',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'RO',
							name: 'Romanian Leu',
							currency_code: 'RON',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'RS',
							name: 'Serbian Dinar',
							currency_code: 'RSD',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'RU',
							name: 'Russian Ruble',
							currency_code: 'RUB',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SE',
							name: 'Swedish Krona',
							currency_code: 'SEK',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SI',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SJ',
							name: 'Norwegian Krone',
							currency_code: 'NOK',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SK',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SM',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TR',
							name: 'Turkish Lira',
							currency_code: 'TRY',
							currency_pos: 'left',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'UA',
							name: 'Ukrainian Hryvnia',
							currency_code: 'UAH',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'VA',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
					],
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'NA',
					name: 'North America',
					countries: [
						{
							code: 'AG',
							name: 'East Caribbean Dollar',
							currency_code: 'XCD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'AI',
							name: 'East Caribbean Dollar',
							currency_code: 'XCD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'AW',
							name: 'Aruban Florin',
							currency_code: 'AWG',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BB',
							name: 'Barbadian Dollar',
							currency_code: 'BBD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BL',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BM',
							name: 'Bermudan Dollar',
							currency_code: 'BMD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BQ',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BS',
							name: 'Bahamian Dollar',
							currency_code: 'BSD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'BZ',
							name: 'Belize Dollar',
							currency_code: 'BZD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'CA',
							name: 'Canadian Dollar',
							currency_code: 'CAD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'CR',
							name: 'Costa Rican Colón',
							currency_code: 'CRC',
							currency_pos: 'left',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'CU',
							name: 'Cuban Peso',
							currency_code: 'CUP',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'CW',
							name: 'Netherlands Antillean Guilder',
							currency_code: 'ANG',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'DM',
							name: 'East Caribbean Dollar',
							currency_code: 'XCD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'DO',
							name: 'Dominican Peso',
							currency_code: 'DOP',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'GD',
							name: 'East Caribbean Dollar',
							currency_code: 'XCD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GL',
							name: 'Danish Krone',
							currency_code: 'DKK',
							currency_pos: 'left',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GP',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GT',
							name: 'Guatemalan Quetzal',
							currency_code: 'GTQ',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'HN',
							name: 'Honduran Lempira',
							currency_code: 'HNL',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'HT',
							name: 'Haitian Gourde',
							currency_code: 'HTG',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'JM',
							name: 'Jamaican Dollar',
							currency_code: 'JMD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'KN',
							name: 'East Caribbean Dollar',
							currency_code: 'XCD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'KY',
							name: 'Cayman Islands Dollar',
							currency_code: 'KYD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'LC',
							name: 'East Caribbean Dollar',
							currency_code: 'XCD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MF',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MQ',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MS',
							name: 'East Caribbean Dollar',
							currency_code: 'XCD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MX',
							name: 'Mexican Peso',
							currency_code: 'MXN',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'NI',
							name: 'Nicaraguan Córdoba',
							currency_code: 'NIO',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'PA',
							name: 'Panamanian Balboa',
							currency_code: 'PAB',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'PM',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'PR',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SV',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'SX',
							name: 'Netherlands Antillean Guilder',
							currency_code: 'ANG',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TC',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TT',
							name: 'Trinidad & Tobago Dollar',
							currency_code: 'TTD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'US',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'foot',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'oz',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'VC',
							name: 'East Caribbean Dollar',
							currency_code: 'XCD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'VG',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'VI',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
					],
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'OC',
					name: 'Oceania',
					countries: [
						{
							code: 'AS',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'AU',
							name: 'Australian Dollar',
							currency_code: 'AUD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'CK',
							name: 'New Zealand Dollar',
							currency_code: 'NZD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'FJ',
							name: 'Fijian Dollar',
							currency_code: 'FJD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'FM',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GU',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'KI',
							name: 'Australian Dollar',
							currency_code: 'AUD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MH',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'MP',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'NC',
							name: 'CFP Franc',
							currency_code: 'XPF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'NF',
							name: 'Australian Dollar',
							currency_code: 'AUD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'NR',
							name: 'Australian Dollar',
							currency_code: 'AUD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'NU',
							name: 'New Zealand Dollar',
							currency_code: 'NZD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'NZ',
							name: 'New Zealand Dollar',
							currency_code: 'NZD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'PF',
							name: 'CFP Franc',
							currency_code: 'XPF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'PG',
							name: 'Papua New Guinean Kina',
							currency_code: 'PGK',
							currency_pos: 'right_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'PN',
							name: 'New Zealand Dollar',
							currency_code: 'NZD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'PW',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'SB',
							name: 'Solomon Islands Dollar',
							currency_code: 'SBD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TK',
							name: 'New Zealand Dollar',
							currency_code: 'NZD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TO',
							name: 'Tongan Paʻanga',
							currency_code: 'TOP',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'TV',
							name: 'Australian Dollar',
							currency_code: 'AUD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'UM',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'VU',
							name: 'Vanuatu Vatu',
							currency_code: 'VUV',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'WF',
							name: 'CFP Franc',
							currency_code: 'XPF',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'WS',
							name: 'Samoan Tala',
							currency_code: 'WST',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
					],
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SA',
					name: 'South America',
					countries: [
						{
							code: 'AR',
							name: 'Argentine Peso',
							currency_code: 'ARS',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'BO',
							name: 'Bolivian Boliviano',
							currency_code: 'BOB',
							currency_pos: 'left',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'BR',
							name: 'Brazilian Real',
							currency_code: 'BRL',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'CL',
							name: 'Chilean Peso',
							currency_code: 'CLP',
							currency_pos: 'left',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'CO',
							name: 'Colombian Peso',
							currency_code: 'COP',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'EC',
							name: 'US Dollar',
							currency_code: 'USD',
							currency_pos: 'left',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'FK',
							name: 'Falkland Islands Pound',
							currency_code: 'FKP',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GF',
							name: 'Euro',
							currency_code: 'EUR',
							currency_pos: 'right_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ' ',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'GY',
							name: 'Guyanaese Dollar',
							currency_code: 'GYD',
							currency_pos: 'left',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'PE',
							name: 'Peruvian Sol',
							currency_code: 'PEN',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'PY',
							name: 'Paraguayan Guarani',
							currency_code: 'PYG',
							currency_pos: 'left_space',
							decimal_sep: '.',
							dimension_unit: 'cm',
							num_decimals: 0,
							thousand_sep: ',',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'SR',
							name: 'Surinamese Dollar',
							currency_code: 'SRD',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: [],
						},
						{
							code: 'UY',
							name: 'Uruguayan Peso',
							currency_code: 'UYU',
							currency_pos: 'left_space',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
						{
							code: 'VE',
							name: 'Venezuelan Bolívar',
							currency_code: 'VES',
							currency_pos: 'left',
							decimal_sep: ',',
							dimension_unit: 'cm',
							num_decimals: 2,
							thousand_sep: '.',
							weight_unit: 'kg',
							states: expect.arrayContaining( [] ),
						},
					],
				} ),
			] )
		);
	} );

	test( 'can view continent data', async ( { request } ) => {
		// call API to retrieve a specific continent data
		const response = await request.get(
			'/wp-json/wc/v3/data/continents/eu'
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( false );

		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				code: 'EU',
				name: 'Europe',
				countries: [
					{
						code: 'AD',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'AL',
						name: 'Albanian Lek',
						currency_code: 'ALL',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 0,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'AT',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'left_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'AX',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'BA',
						name: 'Bosnia-Herzegovina Convertible Mark',
						currency_code: 'BAM',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'BE',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'left_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'BG',
						name: 'Bulgarian Lev',
						currency_code: 'BGN',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'BY',
						name: 'Belarusian Ruble',
						currency_code: 'BYN',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'CH',
						name: 'Swiss Franc',
						currency_code: 'CHF',
						currency_pos: 'left_space',
						decimal_sep: '.',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: "'",
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'CZ',
						name: 'Czech Koruna',
						currency_code: 'CZK',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'DE',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'DK',
						name: 'Danish Krone',
						currency_code: 'DKK',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'EE',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'ES',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'FI',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'FO',
						name: 'Danish Krone',
						currency_code: 'DKK',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'FR',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'GB',
						name: 'British Pound',
						currency_code: 'GBP',
						currency_pos: 'left',
						decimal_sep: '.',
						dimension_unit: 'foot',
						num_decimals: 2,
						thousand_sep: ',',
						weight_unit: 'oz',
						states: [],
					},
					{
						code: 'GG',
						name: 'British Pound',
						currency_code: 'GBP',
						currency_pos: 'left',
						decimal_sep: '.',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ',',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'GI',
						name: 'Gibraltar Pound',
						currency_code: 'GIP',
						currency_pos: 'left',
						decimal_sep: '.',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ',',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'GR',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'HR',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'HU',
						name: 'Hungarian Forint',
						currency_code: 'HUF',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 0,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'IE',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'left',
						decimal_sep: '.',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ',',
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'IM',
						name: 'British Pound',
						currency_code: 'GBP',
						currency_pos: 'left',
						decimal_sep: '.',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ',',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'IS',
						name: 'Icelandic Króna',
						currency_code: 'ISK',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 0,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'IT',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'JE',
						name: 'British Pound',
						currency_code: 'GBP',
						currency_pos: 'left',
						decimal_sep: '.',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ',',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'LI',
						name: 'Swiss Franc',
						currency_code: 'CHF',
						currency_pos: 'left_space',
						decimal_sep: '.',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: "'",
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'LT',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'LU',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'LV',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'MC',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'MD',
						name: 'Moldovan Leu',
						currency_code: 'MDL',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'ME',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'MK',
						name: 'Macedonian Denar',
						currency_code: 'MKD',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'MT',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'left',
						decimal_sep: '.',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ',',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'NL',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'left_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'NO',
						name: 'Norwegian Krone',
						currency_code: 'NOK',
						currency_pos: 'left_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'PL',
						name: 'Polish Zloty',
						currency_code: 'PLN',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'PT',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'RO',
						name: 'Romanian Leu',
						currency_code: 'RON',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'RS',
						name: 'Serbian Dinar',
						currency_code: 'RSD',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 0,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'RU',
						name: 'Russian Ruble',
						currency_code: 'RUB',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'SE',
						name: 'Swedish Krona',
						currency_code: 'SEK',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'SI',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'SJ',
						name: 'Norwegian Krone',
						currency_code: 'NOK',
						currency_pos: 'left_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'SK',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'SM',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
					{
						code: 'TR',
						name: 'Turkish Lira',
						currency_code: 'TRY',
						currency_pos: 'left',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'UA',
						name: 'Ukrainian Hryvnia',
						currency_code: 'UAH',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: ' ',
						weight_unit: 'kg',
						states: expect.arrayContaining( [] ),
					},
					{
						code: 'VA',
						name: 'Euro',
						currency_code: 'EUR',
						currency_pos: 'right_space',
						decimal_sep: ',',
						dimension_unit: 'cm',
						num_decimals: 2,
						thousand_sep: '.',
						weight_unit: 'kg',
						states: [],
					},
				],
			} )
		);
	} );

	test( 'can view country data', async ( { request } ) => {
		// call API to retrieve all countries
		const response = await request.get( '/wp-json/wc/v3/data/countries' );
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( true );

		// loop through all the countries and validate against the expected data
		for ( const country of countryCodes ) {
			const countryData = require( `../../data/countries/${ country }.json` );
			expect( responseJSON ).toEqual(
				expect.arrayContaining( [
					expect.objectContaining( {
						code: countryData.code,
						name: countryData.name,
						states: expect.arrayContaining( countryData.states ),
						_links: {
							self: [
								{
									href: expect.stringContaining(
										`/wp-json/wc/v3/data/countries/${ country }`
									),
								},
							],
							collection: [
								{
									href: expect.stringContaining(
										'/wp-json/wc/v3/data/countries'
									),
								},
							],
						},
					} ),
				] )
			);
		}
	} );

	test( 'can view all currencies', async ( { request } ) => {
		// call API to retrieve all currencies
		const response = await request.get( '/wp-json/wc/v3/data/currencies' );
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( true );

		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'AED',
					name: 'United Arab Emirates dirham',
					symbol: '&#x62f;.&#x625;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/AED'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'AFN',
					name: 'Afghan afghani',
					symbol: '&#x60b;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/AFN'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'ALL',
					name: 'Albanian lek',
					symbol: 'L',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/ALL'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'AMD',
					name: 'Armenian dram',
					symbol: 'AMD',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/AMD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'ANG',
					name: 'Netherlands Antillean guilder',
					symbol: '&fnof;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/ANG'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'AOA',
					name: 'Angolan kwanza',
					symbol: 'Kz',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/AOA'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'ARS',
					name: 'Argentine peso',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/ARS'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'AUD',
					name: 'Australian dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/AUD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'AWG',
					name: 'Aruban florin',
					symbol: 'Afl.',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/AWG'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'AZN',
					name: 'Azerbaijani manat',
					symbol: '&#8380;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/AZN'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BAM',
					name: 'Bosnia and Herzegovina convertible mark',
					symbol: 'KM',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BAM'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BBD',
					name: 'Barbadian dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BBD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BDT',
					name: 'Bangladeshi taka',
					symbol: '&#2547;&nbsp;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BDT'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BGN',
					name: 'Bulgarian lev',
					symbol: '&#1083;&#1074;.',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BGN'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BHD',
					name: 'Bahraini dinar',
					symbol: '.&#x62f;.&#x628;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BHD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BIF',
					name: 'Burundian franc',
					symbol: 'Fr',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BIF'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BMD',
					name: 'Bermudian dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BMD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BND',
					name: 'Brunei dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BND'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BOB',
					name: 'Bolivian boliviano',
					symbol: 'Bs.',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BOB'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BRL',
					name: 'Brazilian real',
					symbol: '&#82;&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BRL'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BSD',
					name: 'Bahamian dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BSD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BTC',
					name: 'Bitcoin',
					symbol: '&#3647;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BTC'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BTN',
					name: 'Bhutanese ngultrum',
					symbol: 'Nu.',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BTN'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BWP',
					name: 'Botswana pula',
					symbol: 'P',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BWP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BYR',
					name: 'Belarusian ruble (old)',
					symbol: 'Br',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BYR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BYN',
					name: 'Belarusian ruble',
					symbol: 'Br',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BYN'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'BZD',
					name: 'Belize dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/BZD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'CAD',
					name: 'Canadian dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/CAD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'CDF',
					name: 'Congolese franc',
					symbol: 'Fr',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/CDF'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'CHF',
					name: 'Swiss franc',
					symbol: '&#67;&#72;&#70;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/CHF'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'CLP',
					name: 'Chilean peso',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/CLP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'CNY',
					name: 'Chinese yuan',
					symbol: '&yen;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/CNY'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'COP',
					name: 'Colombian peso',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/COP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'CRC',
					name: 'Costa Rican col&oacute;n',
					symbol: '&#x20a1;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/CRC'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'CUC',
					name: 'Cuban convertible peso',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/CUC'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'CUP',
					name: 'Cuban peso',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/CUP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'CVE',
					name: 'Cape Verdean escudo',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/CVE'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'CZK',
					name: 'Czech koruna',
					symbol: '&#75;&#269;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/CZK'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'DJF',
					name: 'Djiboutian franc',
					symbol: 'Fr',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/DJF'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'DKK',
					name: 'Danish krone',
					symbol: 'kr.',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/DKK'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'DOP',
					name: 'Dominican peso',
					symbol: 'RD&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/DOP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'DZD',
					name: 'Algerian dinar',
					symbol: '&#x62f;.&#x62c;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/DZD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'EGP',
					name: 'Egyptian pound',
					symbol: 'EGP',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/EGP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'ERN',
					name: 'Eritrean nakfa',
					symbol: 'Nfk',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/ERN'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'ETB',
					name: 'Ethiopian birr',
					symbol: 'Br',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/ETB'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'EUR',
					name: 'Euro',
					symbol: '&euro;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/EUR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'FJD',
					name: 'Fijian dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/FJD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'FKP',
					name: 'Falkland Islands pound',
					symbol: '&pound;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/FKP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'GBP',
					name: 'Pound sterling',
					symbol: '&pound;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/GBP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'GEL',
					name: 'Georgian lari',
					symbol: '&#x20be;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/GEL'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'GGP',
					name: 'Guernsey pound',
					symbol: '&pound;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/GGP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'GHS',
					name: 'Ghana cedi',
					symbol: '&#x20b5;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/GHS'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'GIP',
					name: 'Gibraltar pound',
					symbol: '&pound;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/GIP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'GMD',
					name: 'Gambian dalasi',
					symbol: 'D',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/GMD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'GNF',
					name: 'Guinean franc',
					symbol: 'Fr',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/GNF'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'GTQ',
					name: 'Guatemalan quetzal',
					symbol: 'Q',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/GTQ'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'GYD',
					name: 'Guyanese dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/GYD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'HKD',
					name: 'Hong Kong dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/HKD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'HNL',
					name: 'Honduran lempira',
					symbol: 'L',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/HNL'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'HRK',
					name: 'Croatian kuna',
					symbol: 'kn',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/HRK'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'HTG',
					name: 'Haitian gourde',
					symbol: 'G',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/HTG'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'HUF',
					name: 'Hungarian forint',
					symbol: '&#70;&#116;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/HUF'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'IDR',
					name: 'Indonesian rupiah',
					symbol: 'Rp',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/IDR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'ILS',
					name: 'Israeli new shekel',
					symbol: '&#8362;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/ILS'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'IMP',
					name: 'Manx pound',
					symbol: '&pound;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/IMP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'INR',
					name: 'Indian rupee',
					symbol: '&#8377;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/INR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'IQD',
					name: 'Iraqi dinar',
					symbol: '&#x62f;.&#x639;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/IQD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'IRR',
					name: 'Iranian rial',
					symbol: '&#xfdfc;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/IRR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'IRT',
					name: 'Iranian toman',
					symbol: '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/IRT'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'ISK',
					name: 'Icelandic kr&oacute;na',
					symbol: 'kr.',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/ISK'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'JEP',
					name: 'Jersey pound',
					symbol: '&pound;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/JEP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'JMD',
					name: 'Jamaican dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/JMD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'JOD',
					name: 'Jordanian dinar',
					symbol: '&#x62f;.&#x627;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/JOD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'JPY',
					name: 'Japanese yen',
					symbol: '&yen;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/JPY'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'KES',
					name: 'Kenyan shilling',
					symbol: 'KSh',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/KES'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'KGS',
					name: 'Kyrgyzstani som',
					symbol: '&#x441;&#x43e;&#x43c;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/KGS'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'KHR',
					name: 'Cambodian riel',
					symbol: '&#x17db;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/KHR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'KMF',
					name: 'Comorian franc',
					symbol: 'Fr',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/KMF'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'KPW',
					name: 'North Korean won',
					symbol: '&#x20a9;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/KPW'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'KRW',
					name: 'South Korean won',
					symbol: '&#8361;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/KRW'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'KWD',
					name: 'Kuwaiti dinar',
					symbol: '&#x62f;.&#x643;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/KWD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'KYD',
					name: 'Cayman Islands dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/KYD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'KZT',
					name: 'Kazakhstani tenge',
					symbol: '&#8376;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/KZT'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'LAK',
					name: 'Lao kip',
					symbol: '&#8365;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/LAK'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'LBP',
					name: 'Lebanese pound',
					symbol: '&#x644;.&#x644;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/LBP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'LKR',
					name: 'Sri Lankan rupee',
					symbol: '&#xdbb;&#xdd4;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/LKR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'LRD',
					name: 'Liberian dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/LRD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'LSL',
					name: 'Lesotho loti',
					symbol: 'L',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/LSL'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'LYD',
					name: 'Libyan dinar',
					//"symbol": "&#x62f;.&#x644;",
					symbol: expect.stringContaining( '&#x62f' ),
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/LYD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MAD',
					name: 'Moroccan dirham',
					symbol: '&#x62f;.&#x645;.',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MAD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MDL',
					name: 'Moldovan leu',
					symbol: 'MDL',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MDL'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MGA',
					name: 'Malagasy ariary',
					symbol: 'Ar',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MGA'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MKD',
					name: 'Macedonian denar',
					symbol: '&#x434;&#x435;&#x43d;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MKD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MMK',
					name: 'Burmese kyat',
					symbol: 'Ks',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MMK'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MNT',
					name: 'Mongolian t&ouml;gr&ouml;g',
					symbol: '&#x20ae;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MNT'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MOP',
					name: 'Macanese pataca',
					symbol: 'P',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MOP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MRU',
					name: 'Mauritanian ouguiya',
					symbol: 'UM',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MRU'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MUR',
					name: 'Mauritian rupee',
					symbol: '&#x20a8;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MUR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MVR',
					name: 'Maldivian rufiyaa',
					symbol: '.&#x783;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MVR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MWK',
					name: 'Malawian kwacha',
					symbol: 'MK',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MWK'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MXN',
					name: 'Mexican peso',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MXN'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MYR',
					name: 'Malaysian ringgit',
					symbol: '&#82;&#77;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MYR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'MZN',
					name: 'Mozambican metical',
					symbol: 'MT',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/MZN'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'NAD',
					name: 'Namibian dollar',
					symbol: 'N&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/NAD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'NGN',
					name: 'Nigerian naira',
					symbol: '&#8358;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/NGN'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'NIO',
					name: 'Nicaraguan c&oacute;rdoba',
					symbol: 'C&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/NIO'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'NOK',
					name: 'Norwegian krone',
					symbol: '&#107;&#114;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/NOK'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'NPR',
					name: 'Nepalese rupee',
					symbol: '&#8360;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/NPR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'NZD',
					name: 'New Zealand dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/NZD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'OMR',
					name: 'Omani rial',
					symbol: '&#x631;.&#x639;.',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/OMR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'PAB',
					name: 'Panamanian balboa',
					symbol: 'B/.',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/PAB'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'PEN',
					name: 'Sol',
					symbol: 'S/',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/PEN'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'PGK',
					name: 'Papua New Guinean kina',
					symbol: 'K',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/PGK'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'PHP',
					name: 'Philippine peso',
					symbol: '&#8369;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/PHP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'PKR',
					name: 'Pakistani rupee',
					symbol: '&#8360;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/PKR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'PLN',
					name: 'Polish z&#x142;oty',
					symbol: '&#122;&#322;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/PLN'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'PRB',
					name: 'Transnistrian ruble',
					symbol: '&#x440;.',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/PRB'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'PYG',
					name: 'Paraguayan guaran&iacute;',
					symbol: '&#8370;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/PYG'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'QAR',
					name: 'Qatari riyal',
					symbol: '&#x631;.&#x642;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/QAR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'RON',
					name: 'Romanian leu',
					symbol: 'lei',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/RON'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'RSD',
					name: 'Serbian dinar',
					symbol: '&#1088;&#1089;&#1076;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/RSD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'RUB',
					name: 'Russian ruble',
					symbol: '&#8381;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/RUB'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'RWF',
					name: 'Rwandan franc',
					symbol: 'Fr',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/RWF'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SAR',
					name: 'Saudi riyal',
					symbol: '&#x631;.&#x633;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/SAR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SBD',
					name: 'Solomon Islands dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/SBD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SCR',
					name: 'Seychellois rupee',
					symbol: '&#x20a8;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/SCR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SDG',
					name: 'Sudanese pound',
					symbol: '&#x62c;.&#x633;.',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/SDG'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SEK',
					name: 'Swedish krona',
					symbol: '&#107;&#114;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/SEK'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SGD',
					name: 'Singapore dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/SGD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SHP',
					name: 'Saint Helena pound',
					symbol: '&pound;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/SHP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SLL',
					name: 'Sierra Leonean leone',
					symbol: 'Le',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/SLL'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SOS',
					name: 'Somali shilling',
					symbol: 'Sh',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/SOS'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SRD',
					name: 'Surinamese dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/SRD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SSP',
					name: 'South Sudanese pound',
					symbol: '&pound;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/SSP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'STN',
					name: 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra',
					symbol: 'Db',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/STN'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SYP',
					name: 'Syrian pound',
					symbol: '&#x644;.&#x633;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/SYP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'SZL',
					name: 'Swazi lilangeni',
					symbol: 'E',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/SZL'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'THB',
					name: 'Thai baht',
					symbol: '&#3647;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/THB'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'TJS',
					name: 'Tajikistani somoni',
					symbol: '&#x405;&#x41c;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/TJS'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'TMT',
					name: 'Turkmenistan manat',
					symbol: 'm',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/TMT'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'TND',
					name: 'Tunisian dinar',
					symbol: '&#x62f;.&#x62a;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/TND'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'TOP',
					name: 'Tongan pa&#x2bb;anga',
					symbol: 'T&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/TOP'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'TRY',
					name: 'Turkish lira',
					symbol: '&#8378;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/TRY'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'TTD',
					name: 'Trinidad and Tobago dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/TTD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'TWD',
					name: 'New Taiwan dollar',
					symbol: '&#78;&#84;&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/TWD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'TZS',
					name: 'Tanzanian shilling',
					symbol: 'Sh',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/TZS'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'UAH',
					name: 'Ukrainian hryvnia',
					symbol: '&#8372;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/UAH'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'UGX',
					name: 'Ugandan shilling',
					symbol: 'UGX',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/UGX'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'USD',
					name: 'United States (US) dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/USD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'UYU',
					name: 'Uruguayan peso',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/UYU'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'UZS',
					name: 'Uzbekistani som',
					symbol: 'UZS',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/UZS'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'VEF',
					name: 'Venezuelan bol&iacute;var (2008–2018)',
					symbol: 'Bs F',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/VEF'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'VES',
					name: 'Venezuelan bol&iacute;var',
					symbol: 'Bs.',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/VES'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'VND',
					name: 'Vietnamese &#x111;&#x1ed3;ng',
					symbol: '&#8363;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/VND'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'VUV',
					name: 'Vanuatu vatu',
					symbol: 'Vt',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/VUV'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'WST',
					name: 'Samoan t&#x101;l&#x101;',
					symbol: 'T',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/WST'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'XAF',
					name: 'Central African CFA franc',
					symbol: 'CFA',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/XAF'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'XCD',
					name: 'East Caribbean dollar',
					symbol: '&#36;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/XCD'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'XOF',
					name: 'West African CFA franc',
					symbol: 'CFA',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/XOF'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'XPF',
					name: 'CFP franc',
					symbol: 'Fr',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/XPF'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'YER',
					name: 'Yemeni rial',
					symbol: '&#xfdfc;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/YER'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'ZAR',
					name: 'South African rand',
					symbol: '&#82;',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/ZAR'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
		expect( responseJSON ).toEqual(
			expect.arrayContaining( [
				expect.objectContaining( {
					code: 'ZMW',
					name: 'Zambian kwacha',
					symbol: 'ZK',
					_links: {
						self: [
							{
								href: expect.stringContaining(
									'data/currencies/ZMW'
								),
							},
						],
						collection: [
							{
								href: expect.stringContaining(
									'data/currencies'
								),
							},
						],
					},
				} ),
			] )
		);
	} );

	test( 'can view currency data', async ( { request } ) => {
		// call API to retrieve a specific currency data
		const response = await request.get(
			'/wp-json/wc/v3/data/currencies/fkp'
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( false );

		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				code: 'FKP',
				name: 'Falkland Islands pound',
				symbol: '&pound;',
			} )
		);
	} );

	test( 'can view current currency', async ( { request } ) => {
		// call API to retrieve current currency data
		const response = await request.get(
			'/wp-json/wc/v3/data/currencies/current'
		);
		const responseJSON = await response.json();
		expect( response.status() ).toEqual( 200 );
		expect( Array.isArray( responseJSON ) ).toBe( false );

		expect( responseJSON ).toEqual(
			expect.objectContaining( {
				code: 'USD',
				name: 'United States (US) dollar',
				symbol: '&#36;',
			} )
		);
	} );
} );
