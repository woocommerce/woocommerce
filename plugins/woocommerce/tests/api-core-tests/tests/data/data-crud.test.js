const {
	test,
	expect
} = require('@playwright/test');

// 259 countries total
const countryCodes = ["af", "ax", "al", "dz", "as", "ad", "ao", "ai", "aq", "ag", "ar", "am", "aw", "au", "at", "az", "bs", "bh", "bd", "bb", "by", "pw", "be", "bz", "bj", "bm", "bt", "bo", "bq", "ba", "bw", "bv", "br", "io", "bn", "bg", "bf", "bi", "kh", "cm", "ca", "cv", "ky", "cf", "td", "cl", "cn", "cx", "cc", "co", "km", "cg", "cd", "ck", "cr", "hr", "cu", "cw", "cy", "cz", "dk", "dj", "dm", "do", "ec", "eg", "sv", "gq", "er", "ee", "sz", "et", "fk", "fo", "fj", "fi", "fr", "gf", "pf", "tf", "ga", "gm", "ge", "de", "gh", "gi", "gr", "gl", "gd", "gp", "gu", "gt", "gg", "gn", "gw", "gy", "ht", "hm", "hn", "hk", "hu", "is", "in", "id", "ir", "iq", "ie", "im", "il", "it", "ci", "jm", "jp", "je", "jo", "kz", "ke", "ki", "kw", "kg", "la", "lv", "lb", "ls", "lr", "ly", "li", "lt", "lu", "mo", "mg", "mw", "my", "mv", "ml", "mt", "mh", "mq", "mr", "mu", "yt", "mx", "fm", "md", "mc", "mn", "me", "ms", "ma", "mz", "mm", "na", "nr", "np", "nl", "nc", "nz", "ni", "ne", "ng", "nu", "nf", "kp", "mk", "mp", "no", "om", "pk", "ps", "pa", "pg", "py", "pe", "ph", "pn", "pl", "pt", "pr", "qa", "re", "ro", "ru", "rw", "st", "bl", "sh", "kn", "lc", "sx", "mf", "pm", "vc", "ws", "sm", "sa", "sn", "rs", "sc", "sl", "sg", "sk", "si", "sb", "so", "za", "gs", "kr", "ss", "es", "lk", "sd", "sr", "sj", "se", "ch", "sy", "tw", "tj", "tz", "th", "tl", "tg", "tk", "to", "tt", "tn", "tr", "tm", "tc", "tv", "ug", "ua", "ae", "gb", "us", "um", "uy", "uz", "vu", "va", "ve", "vn", "vg", "vi", "wf", "eh", "ye", "zm", "zw"];

/**
 * Tests for the WooCommerce Refunds API.
 *
 * @group api
 * @group data
 *
 */
test.describe('Data API tests', () => {

	test('can list all data', async ({
		request
	}) => {
		// call API to retrieve data values
		const response = await request.get('/wp-json/wc/v3/data');
		const responseJSON = await response.json();
		expect(response.status()).toEqual(200);
		expect(Array.isArray(responseJSON)).toBe(true);

		expect(responseJSON).toEqual(
			expect.arrayContaining([
				expect.objectContaining({
					"slug": "continents",
					"description": "List of supported continents, countries, and states.",
				}),

				expect.objectContaining({
					"slug": "countries",
					"description": "List of supported states in a given country.",
				}),

				expect.objectContaining({
					"slug": "currencies",
					"description": "List of supported currencies.",
				}),

			])
		);
	});

	test('can view all continents', async ({
		request
	}) => {
		// call API to retrieve all continents
		const response = await request.get('/wp-json/wc/v3/data/continents');
		const responseJSON = await response.json();
		expect(response.status()).toEqual(200);
		expect(Array.isArray(responseJSON)).toBe(true);

		expect(responseJSON).toEqual(
			expect.arrayContaining([
				expect.objectContaining({
					"code": "AF",
					"name": "Africa",
					"countries": [{
							"code": "AO",
							"name": "Angolan kwanza",
							"currency_code": "AOA",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "BF",
							"name": "West African CFA franc",
							"currency_code": "XOF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BI",
							"name": "Burundian franc",
							"currency_code": "BIF",
							"currency_pos": "right",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BJ",
							"name": "West African CFA franc",
							"currency_code": "XOF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "BW",
							"name": "Botswana pula",
							"currency_code": "BWP",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "CD",
							"name": "Congolese franc",
							"currency_code": "CDF",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "CF",
							"name": "Central African CFA franc",
							"currency_code": "XAF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "CG",
							"name": "Central African CFA franc",
							"currency_code": "XAF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "CI",
							"name": "West African CFA franc",
							"currency_code": "XOF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "CM",
							"name": "Central African CFA franc",
							"currency_code": "XAF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "CV",
							"name": "Cape Verdean escudo",
							"currency_code": "CVE",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "DJ",
							"name": "Djiboutian franc",
							"currency_code": "DJF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "DZ",
							"name": "Algerian dinar",
							"currency_code": "DZD",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "EG",
							"name": "Egyptian pound",
							"currency_code": "EGP",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "EH",
							"name": "Moroccan dirham",
							"currency_code": "MAD",
							"currency_pos": "left_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "ER",
							"name": "Eritrean nakfa",
							"currency_code": "ERN",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "ET",
							"name": "Ethiopian birr",
							"currency_code": "ETB",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GA",
							"name": "Central African CFA franc",
							"currency_code": "XAF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GH",
							"name": "Ghana cedi",
							"currency_code": "GHS",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "GM",
							"name": "Gambian dalasi",
							"currency_code": "GMD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GN",
							"name": "Guinean franc",
							"currency_code": "GNF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GQ",
							"name": "Central African CFA franc",
							"currency_code": "XAF",
							"currency_pos": "left",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GW",
							"name": "West African CFA franc",
							"currency_code": "XOF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "KE",
							"name": "Kenyan shilling",
							"currency_code": "KES",
							"currency_pos": "left_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "KM",
							"name": "Comorian franc",
							"currency_code": "KMF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "LR",
							"name": "Liberian dollar",
							"currency_code": "LRD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "LS",
							"name": "Lesotho loti",
							"currency_code": "LSL",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "LY",
							"name": "Libyan dinar",
							"currency_code": "LYD",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 3,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MA",
							"name": "Moroccan dirham",
							"currency_code": "MAD",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MG",
							"name": "Malagasy ariary",
							"currency_code": "MGA",
							"currency_pos": "left_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "ML",
							"name": "West African CFA franc",
							"currency_code": "XOF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MR",
							"name": "Mauritanian ouguiya",
							"currency_code": "MRU",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MU",
							"name": "Mauritian rupee",
							"currency_code": "MUR",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MW",
							"name": "Malawian kwacha",
							"currency_code": "MWK",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MZ",
							"name": "Mozambican metical",
							"currency_code": "MZN",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "NA",
							"name": "Namibian dollar",
							"currency_code": "NAD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "NE",
							"name": "West African CFA franc",
							"currency_code": "XOF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "NG",
							"name": "Nigerian naira",
							"currency_code": "NGN",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "RE",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "RW",
							"name": "Rwandan franc",
							"currency_code": "RWF",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SC",
							"name": "Seychellois rupee",
							"currency_code": "SCR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SD",
							"name": "Sudanese pound",
							"currency_code": "SDG",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SH",
							"name": "Saint Helena pound",
							"currency_code": "SHP",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SL",
							"name": "Sierra Leonean leone",
							"currency_code": "SLL",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SN",
							"name": "West African CFA franc",
							"currency_code": "XOF",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([]),
						},
						{
							"code": "SO",
							"name": "Somali shilling",
							"currency_code": "SOS",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SS",
							"name": "South Sudanese pound",
							"currency_code": "SSP",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "ST",
							"name": "São Tomé and Príncipe dobra",
							"currency_code": "STN",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SZ",
							"name": "Swazi lilangeni",
							"currency_code": "SZL",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TD",
							"name": "Central African CFA franc",
							"currency_code": "XAF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TG",
							"name": "West African CFA franc",
							"currency_code": "XOF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TN",
							"name": "Tunisian dinar",
							"currency_code": "TND",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 3,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TZ",
							"name": "Tanzanian shilling",
							"currency_code": "TZS",
							"currency_pos": "left_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "UG",
							"name": "Ugandan shilling",
							"currency_code": "UGX",
							"currency_pos": "left_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "YT",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "ZA",
							"name": "South African rand",
							"currency_code": "ZAR",
							"currency_pos": "left",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "ZM",
							"name": "Zambian kwacha",
							"currency_code": "ZMW",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "ZW",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						}
					],
				})
			]));
		expect(responseJSON).toEqual(
			expect.arrayContaining([
				expect.objectContaining({
					"code": "AN",
					"name": "Antarctica",
					"countries": [{
							"code": "AQ",
							"name": "Antarctica",
							"states": []
						},
						{
							"code": "BV",
							"name": "Bouvet Island",
							"states": []
						},
						{
							"code": "GS",
							"name": "South Georgia/Sandwich Islands",
							"states": []
						},
						{
							"code": "HM",
							"name": "Heard Island and McDonald Islands",
							"states": []
						},
						{
							"code": "TF",
							"name": "French Southern Territories",
							"states": []
						}
					],
				})
			]));
		expect(responseJSON).toEqual(
			expect.arrayContaining([
				expect.objectContaining({
					"code": "AS",
					"name": "Asia",
					"countries": [{
							"code": "AE",
							"name": "United Arab Emirates dirham",
							"currency_code": "AED",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "AF",
							"name": "Afghan afghani",
							"currency_code": "AFN",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "AM",
							"name": "Armenian dram",
							"currency_code": "AMD",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "AZ",
							"name": "Azerbaijani manat",
							"currency_code": "AZN",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BD",
							"name": "Bangladeshi taka",
							"currency_code": "BDT",
							"currency_pos": "right",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "BH",
							"name": "Bahraini dinar",
							"currency_code": "BHD",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 3,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BN",
							"name": "Brunei dollar",
							"currency_code": "BND",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BT",
							"name": "Bhutanese ngultrum",
							"currency_code": "BTN",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "CC",
							"name": "Australian dollar",
							"currency_code": "AUD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "CN",
							"name": "Chinese yuan",
							"currency_code": "CNY",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "CX",
							"name": "Australian dollar",
							"currency_code": "AUD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "CY",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GE",
							"name": "Georgian lari",
							"currency_code": "GEL",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "HK",
							"name": "Hong Kong dollar",
							"currency_code": "HKD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "ID",
							"name": "Indonesian rupiah",
							"currency_code": "IDR",
							"currency_pos": "left",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "IL",
							"name": "Israeli new shekel",
							"currency_code": "ILS",
							"currency_pos": "right_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "IN",
							"name": "Indian rupee",
							"currency_code": "INR",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "IO",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "IQ",
							"name": "Iraqi dinar",
							"currency_code": "IQD",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "IR",
							"name": "Iranian rial",
							"currency_code": "IRR",
							"currency_pos": "left",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "JO",
							"name": "Jordanian dinar",
							"currency_code": "JOD",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 3,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "JP",
							"name": "Japanese yen",
							"currency_code": "JPY",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "KG",
							"name": "Kyrgyzstani som",
							"currency_code": "KGS",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "KH",
							"name": "Cambodian riel",
							"currency_code": "KHR",
							"currency_pos": "right",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "KP",
							"name": "North Korean won",
							"currency_code": "KPW",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "KR",
							"name": "South Korean won",
							"currency_code": "KRW",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "KW",
							"name": "Kuwaiti dinar",
							"currency_code": "KWD",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 3,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "KZ",
							"name": "Kazakhstani tenge",
							"currency_code": "KZT",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "LA",
							"name": "Lao kip",
							"currency_code": "LAK",
							"currency_pos": "left",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "LB",
							"name": "Lebanese pound",
							"currency_code": "LBP",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "LK",
							"name": "Sri Lankan rupee",
							"currency_code": "LKR",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MM",
							"name": "Burmese kyat",
							"currency_code": "MMK",
							"currency_pos": "right_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MN",
							"name": "Mongolian tögrög",
							"currency_code": "MNT",
							"currency_pos": "left_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MO",
							"name": "Macanese pataca",
							"currency_code": "MOP",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MV",
							"name": "Maldivian rufiyaa",
							"currency_code": "MVR",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MY",
							"name": "Malaysian ringgit",
							"currency_code": "MYR",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "NP",
							"name": "Nepalese rupee",
							"currency_code": "NPR",
							"currency_pos": "left_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "OM",
							"name": "Omani rial",
							"currency_code": "OMR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 3,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "PH",
							"name": "Philippine peso",
							"currency_code": "PHP",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "PK",
							"name": "Pakistani rupee",
							"currency_code": "PKR",
							"currency_pos": "left_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "PS",
							"name": "Jordanian dinar",
							"currency_code": "JOD",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 3,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "QA",
							"name": "Qatari riyal",
							"currency_code": "QAR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SA",
							"name": "Saudi riyal",
							"currency_code": "SAR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SG",
							"name": "Singapore dollar",
							"currency_code": "SGD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SY",
							"name": "Syrian pound",
							"currency_code": "SYP",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TH",
							"name": "Thai baht",
							"currency_code": "THB",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "TJ",
							"name": "Tajikistani somoni",
							"currency_code": "TJS",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TL",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TM",
							"name": "Turkmenistan manat",
							"currency_code": "TMT",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TW",
							"name": "New Taiwan dollar",
							"currency_code": "TWD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "UZ",
							"name": "Uzbekistani som",
							"currency_code": "UZS",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "VN",
							"name": "Vietnamese đồng",
							"currency_code": "VND",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "YE",
							"name": "Yemeni rial",
							"currency_code": "YER",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						}
					],
				})
			]));
		expect(responseJSON).toEqual(
			expect.arrayContaining([
				expect.objectContaining({
					"code": "EU",
					"name": "Europe",
					"countries": [{
							"code": "AD",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "AL",
							"name": "Albanian lek",
							"currency_code": "ALL",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": [{
									"code": "AL-01",
									"name": "Berat"
								},
								{
									"code": "AL-09",
									"name": "Dibër"
								},
								{
									"code": "AL-02",
									"name": "Durrës"
								},
								{
									"code": "AL-03",
									"name": "Elbasan"
								},
								{
									"code": "AL-04",
									"name": "Fier"
								},
								{
									"code": "AL-05",
									"name": "Gjirokastër"
								},
								{
									"code": "AL-06",
									"name": "Korçë"
								},
								{
									"code": "AL-07",
									"name": "Kukës"
								},
								{
									"code": "AL-08",
									"name": "Lezhë"
								},
								{
									"code": "AL-10",
									"name": "Shkodër"
								},
								{
									"code": "AL-11",
									"name": "Tirana"
								},
								{
									"code": "AL-12",
									"name": "Vlorë"
								}
							]
						},
						{
							"code": "AT",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "AX",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BA",
							"name": "Bosnia and Herzegovina convertible mark",
							"currency_code": "BAM",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BE",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BG",
							"name": "Bulgarian lev",
							"currency_code": "BGN",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": [{
									"code": "BG-01",
									"name": "Blagoevgrad"
								},
								{
									"code": "BG-02",
									"name": "Burgas"
								},
								{
									"code": "BG-08",
									"name": "Dobrich"
								},
								{
									"code": "BG-07",
									"name": "Gabrovo"
								},
								{
									"code": "BG-26",
									"name": "Haskovo"
								},
								{
									"code": "BG-09",
									"name": "Kardzhali"
								},
								{
									"code": "BG-10",
									"name": "Kyustendil"
								},
								{
									"code": "BG-11",
									"name": "Lovech"
								},
								{
									"code": "BG-12",
									"name": "Montana"
								},
								{
									"code": "BG-13",
									"name": "Pazardzhik"
								},
								{
									"code": "BG-14",
									"name": "Pernik"
								},
								{
									"code": "BG-15",
									"name": "Pleven"
								},
								{
									"code": "BG-16",
									"name": "Plovdiv"
								},
								{
									"code": "BG-17",
									"name": "Razgrad"
								},
								{
									"code": "BG-18",
									"name": "Ruse"
								},
								{
									"code": "BG-27",
									"name": "Shumen"
								},
								{
									"code": "BG-19",
									"name": "Silistra"
								},
								{
									"code": "BG-20",
									"name": "Sliven"
								},
								{
									"code": "BG-21",
									"name": "Smolyan"
								},
								{
									"code": "BG-23",
									"name": "Sofia District"
								},
								{
									"code": "BG-22",
									"name": "Sofia"
								},
								{
									"code": "BG-24",
									"name": "Stara Zagora"
								},
								{
									"code": "BG-25",
									"name": "Targovishte"
								},
								{
									"code": "BG-03",
									"name": "Varna"
								},
								{
									"code": "BG-04",
									"name": "Veliko Tarnovo"
								},
								{
									"code": "BG-05",
									"name": "Vidin"
								},
								{
									"code": "BG-06",
									"name": "Vratsa"
								},
								{
									"code": "BG-28",
									"name": "Yambol"
								}
							]
						},
						{
							"code": "BY",
							"name": "Belarusian ruble",
							"currency_code": "BYN",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "CH",
							"name": "Swiss franc",
							"currency_code": "CHF",
							"currency_pos": "left_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": "'",
							"weight_unit": "kg",
							"states": [{
									"code": "AG",
									"name": "Aargau"
								},
								{
									"code": "AR",
									"name": "Appenzell Ausserrhoden"
								},
								{
									"code": "AI",
									"name": "Appenzell Innerrhoden"
								},
								{
									"code": "BL",
									"name": "Basel-Landschaft"
								},
								{
									"code": "BS",
									"name": "Basel-Stadt"
								},
								{
									"code": "BE",
									"name": "Bern"
								},
								{
									"code": "FR",
									"name": "Fribourg"
								},
								{
									"code": "GE",
									"name": "Geneva"
								},
								{
									"code": "GL",
									"name": "Glarus"
								},
								{
									"code": "GR",
									"name": "Graubünden"
								},
								{
									"code": "JU",
									"name": "Jura"
								},
								{
									"code": "LU",
									"name": "Luzern"
								},
								{
									"code": "NE",
									"name": "Neuchâtel"
								},
								{
									"code": "NW",
									"name": "Nidwalden"
								},
								{
									"code": "OW",
									"name": "Obwalden"
								},
								{
									"code": "SH",
									"name": "Schaffhausen"
								},
								{
									"code": "SZ",
									"name": "Schwyz"
								},
								{
									"code": "SO",
									"name": "Solothurn"
								},
								{
									"code": "SG",
									"name": "St. Gallen"
								},
								{
									"code": "TG",
									"name": "Thurgau"
								},
								{
									"code": "TI",
									"name": "Ticino"
								},
								{
									"code": "UR",
									"name": "Uri"
								},
								{
									"code": "VS",
									"name": "Valais"
								},
								{
									"code": "VD",
									"name": "Vaud"
								},
								{
									"code": "ZG",
									"name": "Zug"
								},
								{
									"code": "ZH",
									"name": "Zürich"
								}
							]
						},
						{
							"code": "CZ",
							"name": "Czech koruna",
							"currency_code": "CZK",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "DE",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": [{
									"code": "DE-BW",
									"name": "Baden-Württemberg"
								},
								{
									"code": "DE-BY",
									"name": "Bavaria"
								},
								{
									"code": "DE-BE",
									"name": "Berlin"
								},
								{
									"code": "DE-BB",
									"name": "Brandenburg"
								},
								{
									"code": "DE-HB",
									"name": "Bremen"
								},
								{
									"code": "DE-HH",
									"name": "Hamburg"
								},
								{
									"code": "DE-HE",
									"name": "Hesse"
								},
								{
									"code": "DE-MV",
									"name": "Mecklenburg-Vorpommern"
								},
								{
									"code": "DE-NI",
									"name": "Lower Saxony"
								},
								{
									"code": "DE-NW",
									"name": "North Rhine-Westphalia"
								},
								{
									"code": "DE-RP",
									"name": "Rhineland-Palatinate"
								},
								{
									"code": "DE-SL",
									"name": "Saarland"
								},
								{
									"code": "DE-SN",
									"name": "Saxony"
								},
								{
									"code": "DE-ST",
									"name": "Saxony-Anhalt"
								},
								{
									"code": "DE-SH",
									"name": "Schleswig-Holstein"
								},
								{
									"code": "DE-TH",
									"name": "Thuringia"
								}
							]
						},
						{
							"code": "DK",
							"name": "Danish krone",
							"currency_code": "DKK",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "EE",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "ES",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": [{
									"code": "C",
									"name": "A Coruña"
								},
								{
									"code": "VI",
									"name": "Araba/Álava"
								},
								{
									"code": "AB",
									"name": "Albacete"
								},
								{
									"code": "A",
									"name": "Alicante"
								},
								{
									"code": "AL",
									"name": "Almería"
								},
								{
									"code": "O",
									"name": "Asturias"
								},
								{
									"code": "AV",
									"name": "Ávila"
								},
								{
									"code": "BA",
									"name": "Badajoz"
								},
								{
									"code": "PM",
									"name": "Baleares"
								},
								{
									"code": "B",
									"name": "Barcelona"
								},
								{
									"code": "BU",
									"name": "Burgos"
								},
								{
									"code": "CC",
									"name": "Cáceres"
								},
								{
									"code": "CA",
									"name": "Cádiz"
								},
								{
									"code": "S",
									"name": "Cantabria"
								},
								{
									"code": "CS",
									"name": "Castellón"
								},
								{
									"code": "CE",
									"name": "Ceuta"
								},
								{
									"code": "CR",
									"name": "Ciudad Real"
								},
								{
									"code": "CO",
									"name": "Córdoba"
								},
								{
									"code": "CU",
									"name": "Cuenca"
								},
								{
									"code": "GI",
									"name": "Girona"
								},
								{
									"code": "GR",
									"name": "Granada"
								},
								{
									"code": "GU",
									"name": "Guadalajara"
								},
								{
									"code": "SS",
									"name": "Gipuzkoa"
								},
								{
									"code": "H",
									"name": "Huelva"
								},
								{
									"code": "HU",
									"name": "Huesca"
								},
								{
									"code": "J",
									"name": "Jaén"
								},
								{
									"code": "LO",
									"name": "La Rioja"
								},
								{
									"code": "GC",
									"name": "Las Palmas"
								},
								{
									"code": "LE",
									"name": "León"
								},
								{
									"code": "L",
									"name": "Lleida"
								},
								{
									"code": "LU",
									"name": "Lugo"
								},
								{
									"code": "M",
									"name": "Madrid"
								},
								{
									"code": "MA",
									"name": "Málaga"
								},
								{
									"code": "ML",
									"name": "Melilla"
								},
								{
									"code": "MU",
									"name": "Murcia"
								},
								{
									"code": "NA",
									"name": "Navarra"
								},
								{
									"code": "OR",
									"name": "Ourense"
								},
								{
									"code": "P",
									"name": "Palencia"
								},
								{
									"code": "PO",
									"name": "Pontevedra"
								},
								{
									"code": "SA",
									"name": "Salamanca"
								},
								{
									"code": "TF",
									"name": "Santa Cruz de Tenerife"
								},
								{
									"code": "SG",
									"name": "Segovia"
								},
								{
									"code": "SE",
									"name": "Sevilla"
								},
								{
									"code": "SO",
									"name": "Soria"
								},
								{
									"code": "T",
									"name": "Tarragona"
								},
								{
									"code": "TE",
									"name": "Teruel"
								},
								{
									"code": "TO",
									"name": "Toledo"
								},
								{
									"code": "V",
									"name": "Valencia"
								},
								{
									"code": "VA",
									"name": "Valladolid"
								},
								{
									"code": "BI",
									"name": "Biscay"
								},
								{
									"code": "ZA",
									"name": "Zamora"
								},
								{
									"code": "Z",
									"name": "Zaragoza"
								}
							]
						},
						{
							"code": "FI",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "FO",
							"name": "Danish krone",
							"currency_code": "DKK",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "FR",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GB",
							"name": "Pound sterling",
							"currency_code": "GBP",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "foot",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "oz",
							"states": []
						},
						{
							"code": "GG",
							"name": "Pound sterling",
							"currency_code": "GBP",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GI",
							"name": "Gibraltar pound",
							"currency_code": "GIP",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GR",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": [{
									"code": "I",
									"name": "Attica"
								},
								{
									"code": "A",
									"name": "East Macedonia and Thrace"
								},
								{
									"code": "B",
									"name": "Central Macedonia"
								},
								{
									"code": "C",
									"name": "West Macedonia"
								},
								{
									"code": "D",
									"name": "Epirus"
								},
								{
									"code": "E",
									"name": "Thessaly"
								},
								{
									"code": "F",
									"name": "Ionian Islands"
								},
								{
									"code": "G",
									"name": "West Greece"
								},
								{
									"code": "H",
									"name": "Central Greece"
								},
								{
									"code": "J",
									"name": "Peloponnese"
								},
								{
									"code": "K",
									"name": "North Aegean"
								},
								{
									"code": "L",
									"name": "South Aegean"
								},
								{
									"code": "M",
									"name": "Crete"
								}
							]
						},
						{
							"code": "HR",
							"name": "Croatian kuna",
							"currency_code": "HRK",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "HU",
							"name": "Hungarian forint",
							"currency_code": "HUF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": [{
									"code": "BK",
									"name": "Bács-Kiskun"
								},
								{
									"code": "BE",
									"name": "Békés"
								},
								{
									"code": "BA",
									"name": "Baranya"
								},
								{
									"code": "BZ",
									"name": "Borsod-Abaúj-Zemplén"
								},
								{
									"code": "BU",
									"name": "Budapest"
								},
								{
									"code": "CS",
									"name": "Csongrád-Csanád"
								},
								{
									"code": "FE",
									"name": "Fejér"
								},
								{
									"code": "GS",
									"name": "Győr-Moson-Sopron"
								},
								{
									"code": "HB",
									"name": "Hajdú-Bihar"
								},
								{
									"code": "HE",
									"name": "Heves"
								},
								{
									"code": "JN",
									"name": "Jász-Nagykun-Szolnok"
								},
								{
									"code": "KE",
									"name": "Komárom-Esztergom"
								},
								{
									"code": "NO",
									"name": "Nógrád"
								},
								{
									"code": "PE",
									"name": "Pest"
								},
								{
									"code": "SO",
									"name": "Somogy"
								},
								{
									"code": "SZ",
									"name": "Szabolcs-Szatmár-Bereg"
								},
								{
									"code": "TO",
									"name": "Tolna"
								},
								{
									"code": "VA",
									"name": "Vas"
								},
								{
									"code": "VE",
									"name": "Veszprém"
								},
								{
									"code": "ZA",
									"name": "Zala"
								}
							]
						},
						{
							"code": "IE",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": [{
									"code": "CW",
									"name": "Carlow"
								},
								{
									"code": "CN",
									"name": "Cavan"
								},
								{
									"code": "CE",
									"name": "Clare"
								},
								{
									"code": "CO",
									"name": "Cork"
								},
								{
									"code": "DL",
									"name": "Donegal"
								},
								{
									"code": "D",
									"name": "Dublin"
								},
								{
									"code": "G",
									"name": "Galway"
								},
								{
									"code": "KY",
									"name": "Kerry"
								},
								{
									"code": "KE",
									"name": "Kildare"
								},
								{
									"code": "KK",
									"name": "Kilkenny"
								},
								{
									"code": "LS",
									"name": "Laois"
								},
								{
									"code": "LM",
									"name": "Leitrim"
								},
								{
									"code": "LK",
									"name": "Limerick"
								},
								{
									"code": "LD",
									"name": "Longford"
								},
								{
									"code": "LH",
									"name": "Louth"
								},
								{
									"code": "MO",
									"name": "Mayo"
								},
								{
									"code": "MH",
									"name": "Meath"
								},
								{
									"code": "MN",
									"name": "Monaghan"
								},
								{
									"code": "OY",
									"name": "Offaly"
								},
								{
									"code": "RN",
									"name": "Roscommon"
								},
								{
									"code": "SO",
									"name": "Sligo"
								},
								{
									"code": "TA",
									"name": "Tipperary"
								},
								{
									"code": "WD",
									"name": "Waterford"
								},
								{
									"code": "WH",
									"name": "Westmeath"
								},
								{
									"code": "WX",
									"name": "Wexford"
								},
								{
									"code": "WW",
									"name": "Wicklow"
								}
							]
						},
						{
							"code": "IM",
							"name": "Pound sterling",
							"currency_code": "GBP",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "IS",
							"name": "Icelandic króna",
							"currency_code": "ISK",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "IT",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": [{
									"code": "AG",
									"name": "Agrigento"
								},
								{
									"code": "AL",
									"name": "Alessandria"
								},
								{
									"code": "AN",
									"name": "Ancona"
								},
								{
									"code": "AO",
									"name": "Aosta"
								},
								{
									"code": "AR",
									"name": "Arezzo"
								},
								{
									"code": "AP",
									"name": "Ascoli Piceno"
								},
								{
									"code": "AT",
									"name": "Asti"
								},
								{
									"code": "AV",
									"name": "Avellino"
								},
								{
									"code": "BA",
									"name": "Bari"
								},
								{
									"code": "BT",
									"name": "Barletta-Andria-Trani"
								},
								{
									"code": "BL",
									"name": "Belluno"
								},
								{
									"code": "BN",
									"name": "Benevento"
								},
								{
									"code": "BG",
									"name": "Bergamo"
								},
								{
									"code": "BI",
									"name": "Biella"
								},
								{
									"code": "BO",
									"name": "Bologna"
								},
								{
									"code": "BZ",
									"name": "Bolzano"
								},
								{
									"code": "BS",
									"name": "Brescia"
								},
								{
									"code": "BR",
									"name": "Brindisi"
								},
								{
									"code": "CA",
									"name": "Cagliari"
								},
								{
									"code": "CL",
									"name": "Caltanissetta"
								},
								{
									"code": "CB",
									"name": "Campobasso"
								},
								{
									"code": "CE",
									"name": "Caserta"
								},
								{
									"code": "CT",
									"name": "Catania"
								},
								{
									"code": "CZ",
									"name": "Catanzaro"
								},
								{
									"code": "CH",
									"name": "Chieti"
								},
								{
									"code": "CO",
									"name": "Como"
								},
								{
									"code": "CS",
									"name": "Cosenza"
								},
								{
									"code": "CR",
									"name": "Cremona"
								},
								{
									"code": "KR",
									"name": "Crotone"
								},
								{
									"code": "CN",
									"name": "Cuneo"
								},
								{
									"code": "EN",
									"name": "Enna"
								},
								{
									"code": "FM",
									"name": "Fermo"
								},
								{
									"code": "FE",
									"name": "Ferrara"
								},
								{
									"code": "FI",
									"name": "Firenze"
								},
								{
									"code": "FG",
									"name": "Foggia"
								},
								{
									"code": "FC",
									"name": "Forlì-Cesena"
								},
								{
									"code": "FR",
									"name": "Frosinone"
								},
								{
									"code": "GE",
									"name": "Genova"
								},
								{
									"code": "GO",
									"name": "Gorizia"
								},
								{
									"code": "GR",
									"name": "Grosseto"
								},
								{
									"code": "IM",
									"name": "Imperia"
								},
								{
									"code": "IS",
									"name": "Isernia"
								},
								{
									"code": "SP",
									"name": "La Spezia"
								},
								{
									"code": "AQ",
									"name": "L'Aquila"
								},
								{
									"code": "LT",
									"name": "Latina"
								},
								{
									"code": "LE",
									"name": "Lecce"
								},
								{
									"code": "LC",
									"name": "Lecco"
								},
								{
									"code": "LI",
									"name": "Livorno"
								},
								{
									"code": "LO",
									"name": "Lodi"
								},
								{
									"code": "LU",
									"name": "Lucca"
								},
								{
									"code": "MC",
									"name": "Macerata"
								},
								{
									"code": "MN",
									"name": "Mantova"
								},
								{
									"code": "MS",
									"name": "Massa-Carrara"
								},
								{
									"code": "MT",
									"name": "Matera"
								},
								{
									"code": "ME",
									"name": "Messina"
								},
								{
									"code": "MI",
									"name": "Milano"
								},
								{
									"code": "MO",
									"name": "Modena"
								},
								{
									"code": "MB",
									"name": "Monza e della Brianza"
								},
								{
									"code": "NA",
									"name": "Napoli"
								},
								{
									"code": "NO",
									"name": "Novara"
								},
								{
									"code": "NU",
									"name": "Nuoro"
								},
								{
									"code": "OR",
									"name": "Oristano"
								},
								{
									"code": "PD",
									"name": "Padova"
								},
								{
									"code": "PA",
									"name": "Palermo"
								},
								{
									"code": "PR",
									"name": "Parma"
								},
								{
									"code": "PV",
									"name": "Pavia"
								},
								{
									"code": "PG",
									"name": "Perugia"
								},
								{
									"code": "PU",
									"name": "Pesaro e Urbino"
								},
								{
									"code": "PE",
									"name": "Pescara"
								},
								{
									"code": "PC",
									"name": "Piacenza"
								},
								{
									"code": "PI",
									"name": "Pisa"
								},
								{
									"code": "PT",
									"name": "Pistoia"
								},
								{
									"code": "PN",
									"name": "Pordenone"
								},
								{
									"code": "PZ",
									"name": "Potenza"
								},
								{
									"code": "PO",
									"name": "Prato"
								},
								{
									"code": "RG",
									"name": "Ragusa"
								},
								{
									"code": "RA",
									"name": "Ravenna"
								},
								{
									"code": "RC",
									"name": "Reggio Calabria"
								},
								{
									"code": "RE",
									"name": "Reggio Emilia"
								},
								{
									"code": "RI",
									"name": "Rieti"
								},
								{
									"code": "RN",
									"name": "Rimini"
								},
								{
									"code": "RM",
									"name": "Roma"
								},
								{
									"code": "RO",
									"name": "Rovigo"
								},
								{
									"code": "SA",
									"name": "Salerno"
								},
								{
									"code": "SS",
									"name": "Sassari"
								},
								{
									"code": "SV",
									"name": "Savona"
								},
								{
									"code": "SI",
									"name": "Siena"
								},
								{
									"code": "SR",
									"name": "Siracusa"
								},
								{
									"code": "SO",
									"name": "Sondrio"
								},
								{
									"code": "SU",
									"name": "Sud Sardegna"
								},
								{
									"code": "TA",
									"name": "Taranto"
								},
								{
									"code": "TE",
									"name": "Teramo"
								},
								{
									"code": "TR",
									"name": "Terni"
								},
								{
									"code": "TO",
									"name": "Torino"
								},
								{
									"code": "TP",
									"name": "Trapani"
								},
								{
									"code": "TN",
									"name": "Trento"
								},
								{
									"code": "TV",
									"name": "Treviso"
								},
								{
									"code": "TS",
									"name": "Trieste"
								},
								{
									"code": "UD",
									"name": "Udine"
								},
								{
									"code": "VA",
									"name": "Varese"
								},
								{
									"code": "VE",
									"name": "Venezia"
								},
								{
									"code": "VB",
									"name": "Verbano-Cusio-Ossola"
								},
								{
									"code": "VC",
									"name": "Vercelli"
								},
								{
									"code": "VR",
									"name": "Verona"
								},
								{
									"code": "VV",
									"name": "Vibo Valentia"
								},
								{
									"code": "VI",
									"name": "Vicenza"
								},
								{
									"code": "VT",
									"name": "Viterbo"
								}
							]
						},
						{
							"code": "JE",
							"name": "Pound sterling",
							"currency_code": "GBP",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "LI",
							"name": "Swiss franc",
							"currency_code": "CHF",
							"currency_pos": "left_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": "'",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "LT",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "LU",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "LV",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MC",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MD",
							"name": "Moldovan leu",
							"currency_code": "MDL",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": [{
									"code": "C",
									"name": "Chișinău"
								},
								{
									"code": "BL",
									"name": "Bălți"
								},
								{
									"code": "AN",
									"name": "Anenii Noi"
								},
								{
									"code": "BS",
									"name": "Basarabeasca"
								},
								{
									"code": "BR",
									"name": "Briceni"
								},
								{
									"code": "CH",
									"name": "Cahul"
								},
								{
									"code": "CT",
									"name": "Cantemir"
								},
								{
									"code": "CL",
									"name": "Călărași"
								},
								{
									"code": "CS",
									"name": "Căușeni"
								},
								{
									"code": "CM",
									"name": "Cimișlia"
								},
								{
									"code": "CR",
									"name": "Criuleni"
								},
								{
									"code": "DN",
									"name": "Dondușeni"
								},
								{
									"code": "DR",
									"name": "Drochia"
								},
								{
									"code": "DB",
									"name": "Dubăsari"
								},
								{
									"code": "ED",
									"name": "Edineț"
								},
								{
									"code": "FL",
									"name": "Fălești"
								},
								{
									"code": "FR",
									"name": "Florești"
								},
								{
									"code": "GE",
									"name": "UTA Găgăuzia"
								},
								{
									"code": "GL",
									"name": "Glodeni"
								},
								{
									"code": "HN",
									"name": "Hîncești"
								},
								{
									"code": "IL",
									"name": "Ialoveni"
								},
								{
									"code": "LV",
									"name": "Leova"
								},
								{
									"code": "NS",
									"name": "Nisporeni"
								},
								{
									"code": "OC",
									"name": "Ocnița"
								},
								{
									"code": "OR",
									"name": "Orhei"
								},
								{
									"code": "RZ",
									"name": "Rezina"
								},
								{
									"code": "RS",
									"name": "Rîșcani"
								},
								{
									"code": "SG",
									"name": "Sîngerei"
								},
								{
									"code": "SR",
									"name": "Soroca"
								},
								{
									"code": "ST",
									"name": "Strășeni"
								},
								{
									"code": "SD",
									"name": "Șoldănești"
								},
								{
									"code": "SV",
									"name": "Ștefan Vodă"
								},
								{
									"code": "TR",
									"name": "Taraclia"
								},
								{
									"code": "TL",
									"name": "Telenești"
								},
								{
									"code": "UN",
									"name": "Ungheni"
								}
							]
						},
						{
							"code": "ME",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MK",
							"name": "Macedonian denar",
							"currency_code": "MKD",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MT",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "NL",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "NO",
							"name": "Norwegian krone",
							"currency_code": "NOK",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "PL",
							"name": "Polish złoty",
							"currency_code": "PLN",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "PT",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "RO",
							"name": "Romanian leu",
							"currency_code": "RON",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": [{
									"code": "AB",
									"name": "Alba"
								},
								{
									"code": "AR",
									"name": "Arad"
								},
								{
									"code": "AG",
									"name": "Argeș"
								},
								{
									"code": "BC",
									"name": "Bacău"
								},
								{
									"code": "BH",
									"name": "Bihor"
								},
								{
									"code": "BN",
									"name": "Bistrița-Năsăud"
								},
								{
									"code": "BT",
									"name": "Botoșani"
								},
								{
									"code": "BR",
									"name": "Brăila"
								},
								{
									"code": "BV",
									"name": "Brașov"
								},
								{
									"code": "B",
									"name": "București"
								},
								{
									"code": "BZ",
									"name": "Buzău"
								},
								{
									"code": "CL",
									"name": "Călărași"
								},
								{
									"code": "CS",
									"name": "Caraș-Severin"
								},
								{
									"code": "CJ",
									"name": "Cluj"
								},
								{
									"code": "CT",
									"name": "Constanța"
								},
								{
									"code": "CV",
									"name": "Covasna"
								},
								{
									"code": "DB",
									"name": "Dâmbovița"
								},
								{
									"code": "DJ",
									"name": "Dolj"
								},
								{
									"code": "GL",
									"name": "Galați"
								},
								{
									"code": "GR",
									"name": "Giurgiu"
								},
								{
									"code": "GJ",
									"name": "Gorj"
								},
								{
									"code": "HR",
									"name": "Harghita"
								},
								{
									"code": "HD",
									"name": "Hunedoara"
								},
								{
									"code": "IL",
									"name": "Ialomița"
								},
								{
									"code": "IS",
									"name": "Iași"
								},
								{
									"code": "IF",
									"name": "Ilfov"
								},
								{
									"code": "MM",
									"name": "Maramureș"
								},
								{
									"code": "MH",
									"name": "Mehedinți"
								},
								{
									"code": "MS",
									"name": "Mureș"
								},
								{
									"code": "NT",
									"name": "Neamț"
								},
								{
									"code": "OT",
									"name": "Olt"
								},
								{
									"code": "PH",
									"name": "Prahova"
								},
								{
									"code": "SJ",
									"name": "Sălaj"
								},
								{
									"code": "SM",
									"name": "Satu Mare"
								},
								{
									"code": "SB",
									"name": "Sibiu"
								},
								{
									"code": "SV",
									"name": "Suceava"
								},
								{
									"code": "TR",
									"name": "Teleorman"
								},
								{
									"code": "TM",
									"name": "Timiș"
								},
								{
									"code": "TL",
									"name": "Tulcea"
								},
								{
									"code": "VL",
									"name": "Vâlcea"
								},
								{
									"code": "VS",
									"name": "Vaslui"
								},
								{
									"code": "VN",
									"name": "Vrancea"
								}
							]
						},
						{
							"code": "RS",
							"name": "Serbian dinar",
							"currency_code": "RSD",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": [{
									"code": "RS00",
									"name": "Belgrade"
								},
								{
									"code": "RS14",
									"name": "Bor"
								},
								{
									"code": "RS11",
									"name": "Braničevo"
								},
								{
									"code": "RS02",
									"name": "Central Banat"
								},
								{
									"code": "RS10",
									"name": "Danube"
								},
								{
									"code": "RS23",
									"name": "Jablanica"
								},
								{
									"code": "RS09",
									"name": "Kolubara"
								},
								{
									"code": "RS08",
									"name": "Mačva"
								},
								{
									"code": "RS17",
									"name": "Morava"
								},
								{
									"code": "RS20",
									"name": "Nišava"
								},
								{
									"code": "RS01",
									"name": "North Bačka"
								},
								{
									"code": "RS03",
									"name": "North Banat"
								},
								{
									"code": "RS24",
									"name": "Pčinja"
								},
								{
									"code": "RS22",
									"name": "Pirot"
								},
								{
									"code": "RS13",
									"name": "Pomoravlje"
								},
								{
									"code": "RS19",
									"name": "Rasina"
								},
								{
									"code": "RS18",
									"name": "Raška"
								},
								{
									"code": "RS06",
									"name": "South Bačka"
								},
								{
									"code": "RS04",
									"name": "South Banat"
								},
								{
									"code": "RS07",
									"name": "Srem"
								},
								{
									"code": "RS12",
									"name": "Šumadija"
								},
								{
									"code": "RS21",
									"name": "Toplica"
								},
								{
									"code": "RS05",
									"name": "West Bačka"
								},
								{
									"code": "RS15",
									"name": "Zaječar"
								},
								{
									"code": "RS16",
									"name": "Zlatibor"
								},
								{
									"code": "RS25",
									"name": "Kosovo"
								},
								{
									"code": "RS26",
									"name": "Peć"
								},
								{
									"code": "RS27",
									"name": "Prizren"
								},
								{
									"code": "RS28",
									"name": "Kosovska Mitrovica"
								},
								{
									"code": "RS29",
									"name": "Kosovo-Pomoravlje"
								},
								{
									"code": "RSKM",
									"name": "Kosovo-Metohija"
								},
								{
									"code": "RSVO",
									"name": "Vojvodina"
								}
							]
						},
						{
							"code": "RU",
							"name": "Russian ruble",
							"currency_code": "RUB",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SE",
							"name": "Swedish krona",
							"currency_code": "SEK",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SI",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SJ",
							"name": "Norwegian krone",
							"currency_code": "NOK",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SK",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SM",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TR",
							"name": "Turkish lira",
							"currency_code": "TRY",
							"currency_pos": "left",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": [{
									"code": "TR01",
									"name": "Adana"
								},
								{
									"code": "TR02",
									"name": "Adıyaman"
								},
								{
									"code": "TR03",
									"name": "Afyon"
								},
								{
									"code": "TR04",
									"name": "Ağrı"
								},
								{
									"code": "TR05",
									"name": "Amasya"
								},
								{
									"code": "TR06",
									"name": "Ankara"
								},
								{
									"code": "TR07",
									"name": "Antalya"
								},
								{
									"code": "TR08",
									"name": "Artvin"
								},
								{
									"code": "TR09",
									"name": "Aydın"
								},
								{
									"code": "TR10",
									"name": "Balıkesir"
								},
								{
									"code": "TR11",
									"name": "Bilecik"
								},
								{
									"code": "TR12",
									"name": "Bingöl"
								},
								{
									"code": "TR13",
									"name": "Bitlis"
								},
								{
									"code": "TR14",
									"name": "Bolu"
								},
								{
									"code": "TR15",
									"name": "Burdur"
								},
								{
									"code": "TR16",
									"name": "Bursa"
								},
								{
									"code": "TR17",
									"name": "Çanakkale"
								},
								{
									"code": "TR18",
									"name": "Çankırı"
								},
								{
									"code": "TR19",
									"name": "Çorum"
								},
								{
									"code": "TR20",
									"name": "Denizli"
								},
								{
									"code": "TR21",
									"name": "Diyarbakır"
								},
								{
									"code": "TR22",
									"name": "Edirne"
								},
								{
									"code": "TR23",
									"name": "Elazığ"
								},
								{
									"code": "TR24",
									"name": "Erzincan"
								},
								{
									"code": "TR25",
									"name": "Erzurum"
								},
								{
									"code": "TR26",
									"name": "Eskişehir"
								},
								{
									"code": "TR27",
									"name": "Gaziantep"
								},
								{
									"code": "TR28",
									"name": "Giresun"
								},
								{
									"code": "TR29",
									"name": "Gümüşhane"
								},
								{
									"code": "TR30",
									"name": "Hakkari"
								},
								{
									"code": "TR31",
									"name": "Hatay"
								},
								{
									"code": "TR32",
									"name": "Isparta"
								},
								{
									"code": "TR33",
									"name": "İçel"
								},
								{
									"code": "TR34",
									"name": "İstanbul"
								},
								{
									"code": "TR35",
									"name": "İzmir"
								},
								{
									"code": "TR36",
									"name": "Kars"
								},
								{
									"code": "TR37",
									"name": "Kastamonu"
								},
								{
									"code": "TR38",
									"name": "Kayseri"
								},
								{
									"code": "TR39",
									"name": "Kırklareli"
								},
								{
									"code": "TR40",
									"name": "Kırşehir"
								},
								{
									"code": "TR41",
									"name": "Kocaeli"
								},
								{
									"code": "TR42",
									"name": "Konya"
								},
								{
									"code": "TR43",
									"name": "Kütahya"
								},
								{
									"code": "TR44",
									"name": "Malatya"
								},
								{
									"code": "TR45",
									"name": "Manisa"
								},
								{
									"code": "TR46",
									"name": "Kahramanmaraş"
								},
								{
									"code": "TR47",
									"name": "Mardin"
								},
								{
									"code": "TR48",
									"name": "Muğla"
								},
								{
									"code": "TR49",
									"name": "Muş"
								},
								{
									"code": "TR50",
									"name": "Nevşehir"
								},
								{
									"code": "TR51",
									"name": "Niğde"
								},
								{
									"code": "TR52",
									"name": "Ordu"
								},
								{
									"code": "TR53",
									"name": "Rize"
								},
								{
									"code": "TR54",
									"name": "Sakarya"
								},
								{
									"code": "TR55",
									"name": "Samsun"
								},
								{
									"code": "TR56",
									"name": "Siirt"
								},
								{
									"code": "TR57",
									"name": "Sinop"
								},
								{
									"code": "TR58",
									"name": "Sivas"
								},
								{
									"code": "TR59",
									"name": "Tekirdağ"
								},
								{
									"code": "TR60",
									"name": "Tokat"
								},
								{
									"code": "TR61",
									"name": "Trabzon"
								},
								{
									"code": "TR62",
									"name": "Tunceli"
								},
								{
									"code": "TR63",
									"name": "Şanlıurfa"
								},
								{
									"code": "TR64",
									"name": "Uşak"
								},
								{
									"code": "TR65",
									"name": "Van"
								},
								{
									"code": "TR66",
									"name": "Yozgat"
								},
								{
									"code": "TR67",
									"name": "Zonguldak"
								},
								{
									"code": "TR68",
									"name": "Aksaray"
								},
								{
									"code": "TR69",
									"name": "Bayburt"
								},
								{
									"code": "TR70",
									"name": "Karaman"
								},
								{
									"code": "TR71",
									"name": "Kırıkkale"
								},
								{
									"code": "TR72",
									"name": "Batman"
								},
								{
									"code": "TR73",
									"name": "Şırnak"
								},
								{
									"code": "TR74",
									"name": "Bartın"
								},
								{
									"code": "TR75",
									"name": "Ardahan"
								},
								{
									"code": "TR76",
									"name": "Iğdır"
								},
								{
									"code": "TR77",
									"name": "Yalova"
								},
								{
									"code": "TR78",
									"name": "Karabük"
								},
								{
									"code": "TR79",
									"name": "Kilis"
								},
								{
									"code": "TR80",
									"name": "Osmaniye"
								},
								{
									"code": "TR81",
									"name": "Düzce"
								}
							]
						},
						{
							"code": "UA",
							"name": "Ukrainian hryvnia",
							"currency_code": "UAH",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "VA",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						}
					],
				})
			]));
		expect(responseJSON).toEqual(
			expect.arrayContaining([
				expect.objectContaining({
					"code": "NA",
					"name": "North America",
					"countries": [{
							"code": "AG",
							"name": "East Caribbean dollar",
							"currency_code": "XCD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "AI",
							"name": "East Caribbean dollar",
							"currency_code": "XCD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "AW",
							"name": "Aruban florin",
							"currency_code": "AWG",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BB",
							"name": "Barbadian dollar",
							"currency_code": "BBD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BL",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BM",
							"name": "Bermudian dollar",
							"currency_code": "BMD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BQ",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BS",
							"name": "Bahamian dollar",
							"currency_code": "BSD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "BZ",
							"name": "Belize dollar",
							"currency_code": "BZD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "CA",
							"name": "Canadian dollar",
							"currency_code": "CAD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "CR",
							"name": "Costa Rican colón",
							"currency_code": "CRC",
							"currency_pos": "left",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "CU",
							"name": "Cuban convertible peso",
							"currency_code": "CUC",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "CW",
							"name": "Netherlands Antillean guilder",
							"currency_code": "ANG",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "DM",
							"name": "East Caribbean dollar",
							"currency_code": "XCD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "DO",
							"name": "Dominican peso",
							"currency_code": "DOP",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "GD",
							"name": "East Caribbean dollar",
							"currency_code": "XCD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GL",
							"name": "Danish krone",
							"currency_code": "DKK",
							"currency_pos": "left",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GP",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GT",
							"name": "Guatemalan quetzal",
							"currency_code": "GTQ",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "HN",
							"name": "Honduran lempira",
							"currency_code": "HNL",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "HT",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "JM",
							"name": "Jamaican dollar",
							"currency_code": "JMD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "KN",
							"name": "East Caribbean dollar",
							"currency_code": "XCD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "KY",
							"name": "Cayman Islands dollar",
							"currency_code": "KYD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "LC",
							"name": "East Caribbean dollar",
							"currency_code": "XCD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MF",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MQ",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MS",
							"name": "East Caribbean dollar",
							"currency_code": "XCD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MX",
							"name": "Mexican peso",
							"currency_code": "MXN",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "NI",
							"name": "Nicaraguan córdoba",
							"currency_code": "NIO",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "PA",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "PM",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "PR",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SV",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "SX",
							"name": "Netherlands Antillean guilder",
							"currency_code": "ANG",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TC",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TT",
							"name": "Trinidad and Tobago dollar",
							"currency_code": "TTD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "US",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "foot",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "oz",
							"states": expect.arrayContaining([])
						},
						{
							"code": "VC",
							"name": "East Caribbean dollar",
							"currency_code": "XCD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "VG",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "VI",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						}
					],
				})
			]));
		expect(responseJSON).toEqual(
			expect.arrayContaining([
				expect.objectContaining({
					"code": "OC",
					"name": "Oceania",
					"countries": [{
							"code": "AS",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "AU",
							"name": "Australian dollar",
							"currency_code": "AUD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "CK",
							"name": "New Zealand dollar",
							"currency_code": "NZD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "FJ",
							"name": "Fijian dollar",
							"currency_code": "FJD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "FM",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GU",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "KI",
							"name": "Australian dollar",
							"currency_code": "AUD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MH",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "MP",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "NC",
							"name": "CFP franc",
							"currency_code": "XPF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "NF",
							"name": "Australian dollar",
							"currency_code": "AUD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "NR",
							"name": "Australian dollar",
							"currency_code": "AUD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "NU",
							"name": "New Zealand dollar",
							"currency_code": "NZD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "NZ",
							"name": "New Zealand dollar",
							"currency_code": "NZD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([]),
						},
						{
							"code": "PF",
							"name": "CFP franc",
							"currency_code": "XPF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "PG",
							"name": "Papua New Guinean kina",
							"currency_code": "PGK",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "PN",
							"name": "New Zealand dollar",
							"currency_code": "NZD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "PW",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "SB",
							"name": "Solomon Islands dollar",
							"currency_code": "SBD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TK",
							"name": "New Zealand dollar",
							"currency_code": "NZD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TO",
							"name": "Tongan paʻanga",
							"currency_code": "TOP",
							"currency_pos": "left_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "TV",
							"name": "Australian dollar",
							"currency_code": "AUD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "UM",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "VU",
							"name": "Vanuatu vatu",
							"currency_code": "VUV",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "WF",
							"name": "CFP franc",
							"currency_code": "XPF",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "WS",
							"name": "Samoan tālā",
							"currency_code": "WST",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						}
					],
				})
			]));
		expect(responseJSON).toEqual(
			expect.arrayContaining([
				expect.objectContaining({
					"code": "SA",
					"name": "South America",
					"countries": [{
							"code": "AR",
							"name": "Argentine peso",
							"currency_code": "ARS",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "BO",
							"name": "Bolivian boliviano",
							"currency_code": "BOB",
							"currency_pos": "left",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "BR",
							"name": "Brazilian real",
							"currency_code": "BRL",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "CL",
							"name": "Chilean peso",
							"currency_code": "CLP",
							"currency_pos": "left",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "CO",
							"name": "Colombian peso",
							"currency_code": "COP",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "EC",
							"name": "United States (US) dollar",
							"currency_code": "USD",
							"currency_pos": "left",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "FK",
							"name": "Falkland Islands pound",
							"currency_code": "FKP",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GF",
							"name": "Euro",
							"currency_code": "EUR",
							"currency_pos": "right_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": " ",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "GY",
							"name": "Guyanese dollar",
							"currency_code": "GYD",
							"currency_pos": "left",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "PE",
							"name": "Sol",
							"currency_code": "PEN",
							"currency_pos": "left_space",
							"decimal_sep": ".",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ",",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "PY",
							"name": "Paraguayan guaraní",
							"currency_code": "PYG",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 0,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "SR",
							"name": "Surinamese dollar",
							"currency_code": "SRD",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": []
						},
						{
							"code": "UY",
							"name": "Uruguayan peso",
							"currency_code": "UYU",
							"currency_pos": "left_space",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						},
						{
							"code": "VE",
							"name": "Bolívar soberano",
							"currency_code": "VES",
							"currency_pos": "left",
							"decimal_sep": ",",
							"dimension_unit": "cm",
							"num_decimals": 2,
							"thousand_sep": ".",
							"weight_unit": "kg",
							"states": expect.arrayContaining([])
						}
					],
				})
			])
		);
	});

	test('can view continent data', async ({
		request
	}) => {
		// call API to retrieve a specific continent data
		const response = await request.get('/wp-json/wc/v3/data/continents/eu');
		const responseJSON = await response.json();
		expect(response.status()).toEqual(200);
		expect(Array.isArray(responseJSON)).toBe(false);

		expect(responseJSON).toEqual(
			expect.objectContaining({
				"code": "EU",
				"name": "Europe",
				"countries": [{
						"code": "AD",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "AL",
						"name": "Albanian lek",
						"currency_code": "ALL",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 0,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "AT",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "left_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "AX",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "BA",
						"name": "Bosnia and Herzegovina convertible mark",
						"currency_code": "BAM",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "BE",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "left_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "BG",
						"name": "Bulgarian lev",
						"currency_code": "BGN",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "BY",
						"name": "Belarusian ruble",
						"currency_code": "BYN",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "CH",
						"name": "Swiss franc",
						"currency_code": "CHF",
						"currency_pos": "left_space",
						"decimal_sep": ".",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": "'",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "CZ",
						"name": "Czech koruna",
						"currency_code": "CZK",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 0,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "DE",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "DK",
						"name": "Danish krone",
						"currency_code": "DKK",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "EE",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "ES",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "FI",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "FO",
						"name": "Danish krone",
						"currency_code": "DKK",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "FR",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "GB",
						"name": "Pound sterling",
						"currency_code": "GBP",
						"currency_pos": "left",
						"decimal_sep": ".",
						"dimension_unit": "foot",
						"num_decimals": 2,
						"thousand_sep": ",",
						"weight_unit": "oz",
						"states": []
					},
					{
						"code": "GG",
						"name": "Pound sterling",
						"currency_code": "GBP",
						"currency_pos": "left",
						"decimal_sep": ".",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ",",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "GI",
						"name": "Gibraltar pound",
						"currency_code": "GIP",
						"currency_pos": "left",
						"decimal_sep": ".",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ",",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "GR",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "HR",
						"name": "Croatian kuna",
						"currency_code": "HRK",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "HU",
						"name": "Hungarian forint",
						"currency_code": "HUF",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 0,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "IE",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "left",
						"decimal_sep": ".",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ",",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "IM",
						"name": "Pound sterling",
						"currency_code": "GBP",
						"currency_pos": "left",
						"decimal_sep": ".",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ",",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "IS",
						"name": "Icelandic króna",
						"currency_code": "ISK",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 0,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "IT",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "JE",
						"name": "Pound sterling",
						"currency_code": "GBP",
						"currency_pos": "left",
						"decimal_sep": ".",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ",",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "LI",
						"name": "Swiss franc",
						"currency_code": "CHF",
						"currency_pos": "left_space",
						"decimal_sep": ".",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": "'",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "LT",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "LU",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "LV",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "MC",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "MD",
						"name": "Moldovan leu",
						"currency_code": "MDL",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "ME",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "MK",
						"name": "Macedonian denar",
						"currency_code": "MKD",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "MT",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "left",
						"decimal_sep": ".",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ",",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "NL",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "left_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "NO",
						"name": "Norwegian krone",
						"currency_code": "NOK",
						"currency_pos": "left_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 0,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "PL",
						"name": "Polish złoty",
						"currency_code": "PLN",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "PT",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "RO",
						"name": "Romanian leu",
						"currency_code": "RON",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "RS",
						"name": "Serbian dinar",
						"currency_code": "RSD",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 0,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "RU",
						"name": "Russian ruble",
						"currency_code": "RUB",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "SE",
						"name": "Swedish krona",
						"currency_code": "SEK",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 0,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "SI",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "SJ",
						"name": "Norwegian krone",
						"currency_code": "NOK",
						"currency_pos": "left_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 0,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "SK",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "SM",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					},
					{
						"code": "TR",
						"name": "Turkish lira",
						"currency_code": "TRY",
						"currency_pos": "left",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "UA",
						"name": "Ukrainian hryvnia",
						"currency_code": "UAH",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": " ",
						"weight_unit": "kg",
						"states": expect.arrayContaining([])
					},
					{
						"code": "VA",
						"name": "Euro",
						"currency_code": "EUR",
						"currency_pos": "right_space",
						"decimal_sep": ",",
						"dimension_unit": "cm",
						"num_decimals": 2,
						"thousand_sep": ".",
						"weight_unit": "kg",
						"states": []
					}
				],
			})
		);
	});

	test('can view all countries', async ({
		request
	}) => {
		// call API to retrieve all countries
		const response = await request.get('/wp-json/wc/v3/data/countries');
		const responseJSON = await response.json();
		expect(response.status()).toEqual(200);
		expect(Array.isArray(responseJSON)).toBe(true);

		// loop through all the countries and validate against the expected data
		for (const country of countryCodes) {
			const countryData = require(`../../data/countries/${country}.json`);
			expect(responseJSON).toEqual(expect.arrayContaining([
				expect.objectContaining({
					"code": countryData.code,
					"name": countryData.name,
					"states": countryData.states,
					"_links": {
						"self": [{
							"href": expect.stringContaining(`/wp-json/wc/v3/data/countries/${country}`)
						}],
						"collection": [{
							"href": expect.stringContaining('/wp-json/wc/v3/data/countries')
						}]
					}
				})
			]));
		}

	});

	test('can view country data', async ({
		request
	}) => {
		// call API to retrieve a specific country data
		const response = await request.get('/wp-json/wc/v3/data/countries/au');
		const responseJSON = await response.json();
		expect(response.status()).toEqual(200);
		expect(Array.isArray(responseJSON)).toBe(false);

		expect(responseJSON).toEqual(
			expect.objectContaining({
				"code": "AU",
				"name": "Australia",
				"states": [{
						"code": "ACT",
						"name": "Australian Capital Territory"
					},
					{
						"code": "NSW",
						"name": "New South Wales"
					},
					{
						"code": "NT",
						"name": "Northern Territory"
					},
					{
						"code": "QLD",
						"name": "Queensland"
					},
					{
						"code": "SA",
						"name": "South Australia"
					},
					{
						"code": "TAS",
						"name": "Tasmania"
					},
					{
						"code": "VIC",
						"name": "Victoria"
					},
					{
						"code": "WA",
						"name": "Western Australia"
					}
				],
			})
		);
	});

	test('can view all currencies', async ({
		request
	}) => {
		// call API to retrieve all currencies
		const response = await request.get('/wp-json/wc/v3/data/currencies');
		const responseJSON = await response.json();
		expect(response.status()).toEqual(200);
		expect(Array.isArray(responseJSON)).toBe(true);

		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AED",
				"name": "United Arab Emirates dirham",
				"symbol": "&#x62f;.&#x625;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/AED")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AFN",
				"name": "Afghan afghani",
				"symbol": "&#x60b;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/AFN")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ALL",
				"name": "Albanian lek",
				"symbol": "L",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/ALL")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AMD",
				"name": "Armenian dram",
				"symbol": "AMD",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/AMD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ANG",
				"name": "Netherlands Antillean guilder",
				"symbol": "&fnof;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/ANG")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AOA",
				"name": "Angolan kwanza",
				"symbol": "Kz",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/AOA")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ARS",
				"name": "Argentine peso",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/ARS")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AUD",
				"name": "Australian dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/AUD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AWG",
				"name": "Aruban florin",
				"symbol": "Afl.",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/AWG")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AZN",
				"name": "Azerbaijani manat",
				"symbol": "AZN",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/AZN")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BAM",
				"name": "Bosnia and Herzegovina convertible mark",
				"symbol": "KM",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BAM")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BBD",
				"name": "Barbadian dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BBD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BDT",
				"name": "Bangladeshi taka",
				"symbol": "&#2547;&nbsp;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BDT")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BGN",
				"name": "Bulgarian lev",
				"symbol": "&#1083;&#1074;.",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BGN")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BHD",
				"name": "Bahraini dinar",
				"symbol": ".&#x62f;.&#x628;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BHD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BIF",
				"name": "Burundian franc",
				"symbol": "Fr",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BIF")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BMD",
				"name": "Bermudian dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BMD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BND",
				"name": "Brunei dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BND")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BOB",
				"name": "Bolivian boliviano",
				"symbol": "Bs.",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BOB")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BRL",
				"name": "Brazilian real",
				"symbol": "&#82;&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BRL")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BSD",
				"name": "Bahamian dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BSD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BTC",
				"name": "Bitcoin",
				"symbol": "&#3647;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BTC")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BTN",
				"name": "Bhutanese ngultrum",
				"symbol": "Nu.",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BTN")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BWP",
				"name": "Botswana pula",
				"symbol": "P",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BWP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BYR",
				"name": "Belarusian ruble (old)",
				"symbol": "Br",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BYR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BYN",
				"name": "Belarusian ruble",
				"symbol": "Br",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BYN")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BZD",
				"name": "Belize dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/BZD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CAD",
				"name": "Canadian dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/CAD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CDF",
				"name": "Congolese franc",
				"symbol": "Fr",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/CDF")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CHF",
				"name": "Swiss franc",
				"symbol": "&#67;&#72;&#70;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/CHF")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CLP",
				"name": "Chilean peso",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/CLP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CNY",
				"name": "Chinese yuan",
				"symbol": "&yen;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/CNY")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "COP",
				"name": "Colombian peso",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/COP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CRC",
				"name": "Costa Rican col&oacute;n",
				"symbol": "&#x20a1;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/CRC")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CUC",
				"name": "Cuban convertible peso",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/CUC")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CUP",
				"name": "Cuban peso",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/CUP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CVE",
				"name": "Cape Verdean escudo",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/CVE")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CZK",
				"name": "Czech koruna",
				"symbol": "&#75;&#269;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/CZK")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "DJF",
				"name": "Djiboutian franc",
				"symbol": "Fr",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/DJF")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "DKK",
				"name": "Danish krone",
				"symbol": "kr.",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/DKK")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "DOP",
				"name": "Dominican peso",
				"symbol": "RD&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/DOP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "DZD",
				"name": "Algerian dinar",
				"symbol": "&#x62f;.&#x62c;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/DZD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "EGP",
				"name": "Egyptian pound",
				"symbol": "EGP",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/EGP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ERN",
				"name": "Eritrean nakfa",
				"symbol": "Nfk",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/ERN")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ETB",
				"name": "Ethiopian birr",
				"symbol": "Br",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/ETB")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "EUR",
				"name": "Euro",
				"symbol": "&euro;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/EUR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "FJD",
				"name": "Fijian dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/FJD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "FKP",
				"name": "Falkland Islands pound",
				"symbol": "&pound;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/FKP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GBP",
				"name": "Pound sterling",
				"symbol": "&pound;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/GBP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GEL",
				"name": "Georgian lari",
				"symbol": "&#x20be;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/GEL")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GGP",
				"name": "Guernsey pound",
				"symbol": "&pound;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/GGP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GHS",
				"name": "Ghana cedi",
				"symbol": "&#x20b5;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/GHS")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GIP",
				"name": "Gibraltar pound",
				"symbol": "&pound;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/GIP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GMD",
				"name": "Gambian dalasi",
				"symbol": "D",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/GMD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GNF",
				"name": "Guinean franc",
				"symbol": "Fr",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/GNF")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GTQ",
				"name": "Guatemalan quetzal",
				"symbol": "Q",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/GTQ")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GYD",
				"name": "Guyanese dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/GYD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "HKD",
				"name": "Hong Kong dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/HKD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "HNL",
				"name": "Honduran lempira",
				"symbol": "L",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/HNL")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "HRK",
				"name": "Croatian kuna",
				"symbol": "kn",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/HRK")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "HTG",
				"name": "Haitian gourde",
				"symbol": "G",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/HTG")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "HUF",
				"name": "Hungarian forint",
				"symbol": "&#70;&#116;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/HUF")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IDR",
				"name": "Indonesian rupiah",
				"symbol": "Rp",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/IDR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ILS",
				"name": "Israeli new shekel",
				"symbol": "&#8362;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/ILS")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IMP",
				"name": "Manx pound",
				"symbol": "&pound;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/IMP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "INR",
				"name": "Indian rupee",
				"symbol": "&#8377;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/INR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IQD",
				"name": "Iraqi dinar",
				"symbol": "&#x62f;.&#x639;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/IQD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IRR",
				"name": "Iranian rial",
				"symbol": "&#xfdfc;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/IRR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IRT",
				"name": "Iranian toman",
				"symbol": "&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/IRT")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ISK",
				"name": "Icelandic kr&oacute;na",
				"symbol": "kr.",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/ISK")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "JEP",
				"name": "Jersey pound",
				"symbol": "&pound;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/JEP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "JMD",
				"name": "Jamaican dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/JMD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "JOD",
				"name": "Jordanian dinar",
				"symbol": "&#x62f;.&#x627;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/JOD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "JPY",
				"name": "Japanese yen",
				"symbol": "&yen;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/JPY")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KES",
				"name": "Kenyan shilling",
				"symbol": "KSh",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/KES")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KGS",
				"name": "Kyrgyzstani som",
				"symbol": "&#x441;&#x43e;&#x43c;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/KGS")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KHR",
				"name": "Cambodian riel",
				"symbol": "&#x17db;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/KHR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KMF",
				"name": "Comorian franc",
				"symbol": "Fr",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/KMF")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KPW",
				"name": "North Korean won",
				"symbol": "&#x20a9;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/KPW")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KRW",
				"name": "South Korean won",
				"symbol": "&#8361;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/KRW")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KWD",
				"name": "Kuwaiti dinar",
				"symbol": "&#x62f;.&#x643;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/KWD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KYD",
				"name": "Cayman Islands dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/KYD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KZT",
				"name": "Kazakhstani tenge",
				"symbol": "&#8376;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/KZT")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LAK",
				"name": "Lao kip",
				"symbol": "&#8365;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/LAK")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LBP",
				"name": "Lebanese pound",
				"symbol": "&#x644;.&#x644;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/LBP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LKR",
				"name": "Sri Lankan rupee",
				"symbol": "&#xdbb;&#xdd4;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/LKR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LRD",
				"name": "Liberian dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/LRD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LSL",
				"name": "Lesotho loti",
				"symbol": "L",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/LSL")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LYD",
				"name": "Libyan dinar",
				//"symbol": "&#x62f;.&#x644;",
				"symbol": expect.stringContaining("&#x62f"),
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/LYD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MAD",
				"name": "Moroccan dirham",
				"symbol": "&#x62f;.&#x645;.",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MAD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MDL",
				"name": "Moldovan leu",
				"symbol": "MDL",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MDL")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MGA",
				"name": "Malagasy ariary",
				"symbol": "Ar",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MGA")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MKD",
				"name": "Macedonian denar",
				"symbol": "&#x434;&#x435;&#x43d;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MKD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MMK",
				"name": "Burmese kyat",
				"symbol": "Ks",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MMK")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MNT",
				"name": "Mongolian t&ouml;gr&ouml;g",
				"symbol": "&#x20ae;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MNT")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MOP",
				"name": "Macanese pataca",
				"symbol": "P",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MOP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MRU",
				"name": "Mauritanian ouguiya",
				"symbol": "UM",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MRU")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MUR",
				"name": "Mauritian rupee",
				"symbol": "&#x20a8;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MUR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MVR",
				"name": "Maldivian rufiyaa",
				"symbol": ".&#x783;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MVR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MWK",
				"name": "Malawian kwacha",
				"symbol": "MK",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MWK")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MXN",
				"name": "Mexican peso",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MXN")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MYR",
				"name": "Malaysian ringgit",
				"symbol": "&#82;&#77;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MYR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MZN",
				"name": "Mozambican metical",
				"symbol": "MT",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/MZN")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NAD",
				"name": "Namibian dollar",
				"symbol": "N&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/NAD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NGN",
				"name": "Nigerian naira",
				"symbol": "&#8358;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/NGN")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NIO",
				"name": "Nicaraguan c&oacute;rdoba",
				"symbol": "C&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/NIO")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NOK",
				"name": "Norwegian krone",
				"symbol": "&#107;&#114;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/NOK")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NPR",
				"name": "Nepalese rupee",
				"symbol": "&#8360;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/NPR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NZD",
				"name": "New Zealand dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/NZD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "OMR",
				"name": "Omani rial",
				"symbol": "&#x631;.&#x639;.",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/OMR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PAB",
				"name": "Panamanian balboa",
				"symbol": "B/.",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/PAB")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PEN",
				"name": "Sol",
				"symbol": "S/",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/PEN")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PGK",
				"name": "Papua New Guinean kina",
				"symbol": "K",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/PGK")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PHP",
				"name": "Philippine peso",
				"symbol": "&#8369;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/PHP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PKR",
				"name": "Pakistani rupee",
				"symbol": "&#8360;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/PKR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PLN",
				"name": "Polish z&#x142;oty",
				"symbol": "&#122;&#322;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/PLN")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PRB",
				"name": "Transnistrian ruble",
				"symbol": "&#x440;.",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/PRB")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PYG",
				"name": "Paraguayan guaran&iacute;",
				"symbol": "&#8370;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/PYG")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "QAR",
				"name": "Qatari riyal",
				"symbol": "&#x631;.&#x642;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/QAR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "RON",
				"name": "Romanian leu",
				"symbol": "lei",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/RON")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "RSD",
				"name": "Serbian dinar",
				"symbol": "&#1088;&#1089;&#1076;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/RSD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "RUB",
				"name": "Russian ruble",
				"symbol": "&#8381;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/RUB")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "RWF",
				"name": "Rwandan franc",
				"symbol": "Fr",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/RWF")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SAR",
				"name": "Saudi riyal",
				"symbol": "&#x631;.&#x633;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/SAR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SBD",
				"name": "Solomon Islands dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/SBD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SCR",
				"name": "Seychellois rupee",
				"symbol": "&#x20a8;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/SCR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SDG",
				"name": "Sudanese pound",
				"symbol": "&#x62c;.&#x633;.",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/SDG")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SEK",
				"name": "Swedish krona",
				"symbol": "&#107;&#114;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/SEK")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SGD",
				"name": "Singapore dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/SGD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SHP",
				"name": "Saint Helena pound",
				"symbol": "&pound;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/SHP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SLL",
				"name": "Sierra Leonean leone",
				"symbol": "Le",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/SLL")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SOS",
				"name": "Somali shilling",
				"symbol": "Sh",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/SOS")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SRD",
				"name": "Surinamese dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/SRD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SSP",
				"name": "South Sudanese pound",
				"symbol": "&pound;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/SSP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "STN",
				"name": "S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra",
				"symbol": "Db",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/STN")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SYP",
				"name": "Syrian pound",
				"symbol": "&#x644;.&#x633;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/SYP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SZL",
				"name": "Swazi lilangeni",
				"symbol": "E",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/SZL")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "THB",
				"name": "Thai baht",
				"symbol": "&#3647;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/THB")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TJS",
				"name": "Tajikistani somoni",
				"symbol": "&#x405;&#x41c;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/TJS")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TMT",
				"name": "Turkmenistan manat",
				"symbol": "m",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/TMT")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TND",
				"name": "Tunisian dinar",
				"symbol": "&#x62f;.&#x62a;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/TND")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TOP",
				"name": "Tongan pa&#x2bb;anga",
				"symbol": "T&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/TOP")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TRY",
				"name": "Turkish lira",
				"symbol": "&#8378;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/TRY")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TTD",
				"name": "Trinidad and Tobago dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/TTD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TWD",
				"name": "New Taiwan dollar",
				"symbol": "&#78;&#84;&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/TWD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TZS",
				"name": "Tanzanian shilling",
				"symbol": "Sh",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/TZS")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "UAH",
				"name": "Ukrainian hryvnia",
				"symbol": "&#8372;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/UAH")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "UGX",
				"name": "Ugandan shilling",
				"symbol": "UGX",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/UGX")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "USD",
				"name": "United States (US) dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/USD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "UYU",
				"name": "Uruguayan peso",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/UYU")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "UZS",
				"name": "Uzbekistani som",
				"symbol": "UZS",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/UZS")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "VEF",
				"name": "Venezuelan bol&iacute;var",
				"symbol": "Bs F",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/VEF")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "VES",
				"name": "Bol&iacute;var soberano",
				"symbol": "Bs.S",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/VES")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "VND",
				"name": "Vietnamese &#x111;&#x1ed3;ng",
				"symbol": "&#8363;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/VND")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "VUV",
				"name": "Vanuatu vatu",
				"symbol": "Vt",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/VUV")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "WST",
				"name": "Samoan t&#x101;l&#x101;",
				"symbol": "T",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/WST")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "XAF",
				"name": "Central African CFA franc",
				"symbol": "CFA",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/XAF")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "XCD",
				"name": "East Caribbean dollar",
				"symbol": "&#36;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/XCD")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "XOF",
				"name": "West African CFA franc",
				"symbol": "CFA",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/XOF")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "XPF",
				"name": "CFP franc",
				"symbol": "Fr",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/XPF")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "YER",
				"name": "Yemeni rial",
				"symbol": "&#xfdfc;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/YER")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ZAR",
				"name": "South African rand",
				"symbol": "&#82;",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/ZAR")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ZMW",
				"name": "Zambian kwacha",
				"symbol": "ZK",
				"_links": {
					"self": [{
						"href": expect.stringContaining("data/currencies/ZMW")
					}],
					"collection": [{
						"href": expect.stringContaining("data/currencies")
					}]
				}
			})
		]));
	});

	test('can view currency data', async ({
		request
	}) => {
		// call API to retrieve a specific currency data
		const response = await request.get('/wp-json/wc/v3/data/currencies/fkp');
		const responseJSON = await response.json();
		expect(response.status()).toEqual(200);
		expect(Array.isArray(responseJSON)).toBe(false);

		expect(responseJSON).toEqual(
			expect.objectContaining({
				"code": "FKP",
				"name": "Falkland Islands pound",
				"symbol": "&pound;"
			}));
	});

	test('can view current currency', async ({
		request
	}) => {
		// call API to retrieve current currency data
		const response = await request.get('/wp-json/wc/v3/data/currencies/current');
		const responseJSON = await response.json();
		expect(response.status()).toEqual(200);
		expect(Array.isArray(responseJSON)).toBe(false);

		expect(responseJSON).toEqual(
			expect.objectContaining({
				"code": "USD",
				"name": "United States (US) dollar",
				"symbol": "&#36;",
			}));
	});
});
