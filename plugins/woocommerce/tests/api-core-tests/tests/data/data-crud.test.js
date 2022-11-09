const {
	test,
	expect
} = require('@playwright/test');
const exp = require('constants');
const {
	refund
} = require('../../data');

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
							"states": [{
									"code": "BGO",
									"name": "Bengo"
								},
								{
									"code": "BLU",
									"name": "Benguela"
								},
								{
									"code": "BIE",
									"name": "Bié"
								},
								{
									"code": "CAB",
									"name": "Cabinda"
								},
								{
									"code": "CNN",
									"name": "Cunene"
								},
								{
									"code": "HUA",
									"name": "Huambo"
								},
								{
									"code": "HUI",
									"name": "Huíla"
								},
								{
									"code": "CCU",
									"name": "Kuando Kubango"
								},
								{
									"code": "CNO",
									"name": "Kwanza-Norte"
								},
								{
									"code": "CUS",
									"name": "Kwanza-Sul"
								},
								{
									"code": "LUA",
									"name": "Luanda"
								},
								{
									"code": "LNO",
									"name": "Lunda-Norte"
								},
								{
									"code": "LSU",
									"name": "Lunda-Sul"
								},
								{
									"code": "MAL",
									"name": "Malanje"
								},
								{
									"code": "MOX",
									"name": "Moxico"
								},
								{
									"code": "NAM",
									"name": "Namibe"
								},
								{
									"code": "UIG",
									"name": "Uíge"
								},
								{
									"code": "ZAI",
									"name": "Zaire"
								}
							]
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
							"states": [{
									"code": "AL",
									"name": "Alibori"
								},
								{
									"code": "AK",
									"name": "Atakora"
								},
								{
									"code": "AQ",
									"name": "Atlantique"
								},
								{
									"code": "BO",
									"name": "Borgou"
								},
								{
									"code": "CO",
									"name": "Collines"
								},
								{
									"code": "KO",
									"name": "Kouffo"
								},
								{
									"code": "DO",
									"name": "Donga"
								},
								{
									"code": "LI",
									"name": "Littoral"
								},
								{
									"code": "MO",
									"name": "Mono"
								},
								{
									"code": "OU",
									"name": "Ouémé"
								},
								{
									"code": "PL",
									"name": "Plateau"
								},
								{
									"code": "ZO",
									"name": "Zou"
								}
							]
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
							"states": [{
									"code": "DZ-01",
									"name": "Adrar"
								},
								{
									"code": "DZ-02",
									"name": "Chlef"
								},
								{
									"code": "DZ-03",
									"name": "Laghouat"
								},
								{
									"code": "DZ-04",
									"name": "Oum El Bouaghi"
								},
								{
									"code": "DZ-05",
									"name": "Batna"
								},
								{
									"code": "DZ-06",
									"name": "Béjaïa"
								},
								{
									"code": "DZ-07",
									"name": "Biskra"
								},
								{
									"code": "DZ-08",
									"name": "Béchar"
								},
								{
									"code": "DZ-09",
									"name": "Blida"
								},
								{
									"code": "DZ-10",
									"name": "Bouira"
								},
								{
									"code": "DZ-11",
									"name": "Tamanghasset"
								},
								{
									"code": "DZ-12",
									"name": "Tébessa"
								},
								{
									"code": "DZ-13",
									"name": "Tlemcen"
								},
								{
									"code": "DZ-14",
									"name": "Tiaret"
								},
								{
									"code": "DZ-15",
									"name": "Tizi Ouzou"
								},
								{
									"code": "DZ-16",
									"name": "Algiers"
								},
								{
									"code": "DZ-17",
									"name": "Djelfa"
								},
								{
									"code": "DZ-18",
									"name": "Jijel"
								},
								{
									"code": "DZ-19",
									"name": "Sétif"
								},
								{
									"code": "DZ-20",
									"name": "Saïda"
								},
								{
									"code": "DZ-21",
									"name": "Skikda"
								},
								{
									"code": "DZ-22",
									"name": "Sidi Bel Abbès"
								},
								{
									"code": "DZ-23",
									"name": "Annaba"
								},
								{
									"code": "DZ-24",
									"name": "Guelma"
								},
								{
									"code": "DZ-25",
									"name": "Constantine"
								},
								{
									"code": "DZ-26",
									"name": "Médéa"
								},
								{
									"code": "DZ-27",
									"name": "Mostaganem"
								},
								{
									"code": "DZ-28",
									"name": "M’Sila"
								},
								{
									"code": "DZ-29",
									"name": "Mascara"
								},
								{
									"code": "DZ-30",
									"name": "Ouargla"
								},
								{
									"code": "DZ-31",
									"name": "Oran"
								},
								{
									"code": "DZ-32",
									"name": "El Bayadh"
								},
								{
									"code": "DZ-33",
									"name": "Illizi"
								},
								{
									"code": "DZ-34",
									"name": "Bordj Bou Arréridj"
								},
								{
									"code": "DZ-35",
									"name": "Boumerdès"
								},
								{
									"code": "DZ-36",
									"name": "El Tarf"
								},
								{
									"code": "DZ-37",
									"name": "Tindouf"
								},
								{
									"code": "DZ-38",
									"name": "Tissemsilt"
								},
								{
									"code": "DZ-39",
									"name": "El Oued"
								},
								{
									"code": "DZ-40",
									"name": "Khenchela"
								},
								{
									"code": "DZ-41",
									"name": "Souk Ahras"
								},
								{
									"code": "DZ-42",
									"name": "Tipasa"
								},
								{
									"code": "DZ-43",
									"name": "Mila"
								},
								{
									"code": "DZ-44",
									"name": "Aïn Defla"
								},
								{
									"code": "DZ-45",
									"name": "Naama"
								},
								{
									"code": "DZ-46",
									"name": "Aïn Témouchent"
								},
								{
									"code": "DZ-47",
									"name": "Ghardaïa"
								},
								{
									"code": "DZ-48",
									"name": "Relizane"
								}
							]
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
							"states": [{
									"code": "EGALX",
									"name": "Alexandria"
								},
								{
									"code": "EGASN",
									"name": "Aswan"
								},
								{
									"code": "EGAST",
									"name": "Asyut"
								},
								{
									"code": "EGBA",
									"name": "Red Sea"
								},
								{
									"code": "EGBH",
									"name": "Beheira"
								},
								{
									"code": "EGBNS",
									"name": "Beni Suef"
								},
								{
									"code": "EGC",
									"name": "Cairo"
								},
								{
									"code": "EGDK",
									"name": "Dakahlia"
								},
								{
									"code": "EGDT",
									"name": "Damietta"
								},
								{
									"code": "EGFYM",
									"name": "Faiyum"
								},
								{
									"code": "EGGH",
									"name": "Gharbia"
								},
								{
									"code": "EGGZ",
									"name": "Giza"
								},
								{
									"code": "EGIS",
									"name": "Ismailia"
								},
								{
									"code": "EGJS",
									"name": "South Sinai"
								},
								{
									"code": "EGKB",
									"name": "Qalyubia"
								},
								{
									"code": "EGKFS",
									"name": "Kafr el-Sheikh"
								},
								{
									"code": "EGKN",
									"name": "Qena"
								},
								{
									"code": "EGLX",
									"name": "Luxor"
								},
								{
									"code": "EGMN",
									"name": "Minya"
								},
								{
									"code": "EGMNF",
									"name": "Monufia"
								},
								{
									"code": "EGMT",
									"name": "Matrouh"
								},
								{
									"code": "EGPTS",
									"name": "Port Said"
								},
								{
									"code": "EGSHG",
									"name": "Sohag"
								},
								{
									"code": "EGSHR",
									"name": "Al Sharqia"
								},
								{
									"code": "EGSIN",
									"name": "North Sinai"
								},
								{
									"code": "EGSUZ",
									"name": "Suez"
								},
								{
									"code": "EGWAD",
									"name": "New Valley"
								}
							]
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
							"states": [{
									"code": "AF",
									"name": "Ahafo"
								},
								{
									"code": "AH",
									"name": "Ashanti"
								},
								{
									"code": "BA",
									"name": "Brong-Ahafo"
								},
								{
									"code": "BO",
									"name": "Bono"
								},
								{
									"code": "BE",
									"name": "Bono East"
								},
								{
									"code": "CP",
									"name": "Central"
								},
								{
									"code": "EP",
									"name": "Eastern"
								},
								{
									"code": "AA",
									"name": "Greater Accra"
								},
								{
									"code": "NE",
									"name": "North East"
								},
								{
									"code": "NP",
									"name": "Northern"
								},
								{
									"code": "OT",
									"name": "Oti"
								},
								{
									"code": "SV",
									"name": "Savannah"
								},
								{
									"code": "UE",
									"name": "Upper East"
								},
								{
									"code": "UW",
									"name": "Upper West"
								},
								{
									"code": "TV",
									"name": "Volta"
								},
								{
									"code": "WP",
									"name": "Western"
								},
								{
									"code": "WN",
									"name": "Western North"
								}
							]
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
							"states": [{
									"code": "KE01",
									"name": "Baringo"
								},
								{
									"code": "KE02",
									"name": "Bomet"
								},
								{
									"code": "KE03",
									"name": "Bungoma"
								},
								{
									"code": "KE04",
									"name": "Busia"
								},
								{
									"code": "KE05",
									"name": "Elgeyo-Marakwet"
								},
								{
									"code": "KE06",
									"name": "Embu"
								},
								{
									"code": "KE07",
									"name": "Garissa"
								},
								{
									"code": "KE08",
									"name": "Homa Bay"
								},
								{
									"code": "KE09",
									"name": "Isiolo"
								},
								{
									"code": "KE10",
									"name": "Kajiado"
								},
								{
									"code": "KE11",
									"name": "Kakamega"
								},
								{
									"code": "KE12",
									"name": "Kericho"
								},
								{
									"code": "KE13",
									"name": "Kiambu"
								},
								{
									"code": "KE14",
									"name": "Kilifi"
								},
								{
									"code": "KE15",
									"name": "Kirinyaga"
								},
								{
									"code": "KE16",
									"name": "Kisii"
								},
								{
									"code": "KE17",
									"name": "Kisumu"
								},
								{
									"code": "KE18",
									"name": "Kitui"
								},
								{
									"code": "KE19",
									"name": "Kwale"
								},
								{
									"code": "KE20",
									"name": "Laikipia"
								},
								{
									"code": "KE21",
									"name": "Lamu"
								},
								{
									"code": "KE22",
									"name": "Machakos"
								},
								{
									"code": "KE23",
									"name": "Makueni"
								},
								{
									"code": "KE24",
									"name": "Mandera"
								},
								{
									"code": "KE25",
									"name": "Marsabit"
								},
								{
									"code": "KE26",
									"name": "Meru"
								},
								{
									"code": "KE27",
									"name": "Migori"
								},
								{
									"code": "KE28",
									"name": "Mombasa"
								},
								{
									"code": "KE29",
									"name": "Murang’a"
								},
								{
									"code": "KE30",
									"name": "Nairobi County"
								},
								{
									"code": "KE31",
									"name": "Nakuru"
								},
								{
									"code": "KE32",
									"name": "Nandi"
								},
								{
									"code": "KE33",
									"name": "Narok"
								},
								{
									"code": "KE34",
									"name": "Nyamira"
								},
								{
									"code": "KE35",
									"name": "Nyandarua"
								},
								{
									"code": "KE36",
									"name": "Nyeri"
								},
								{
									"code": "KE37",
									"name": "Samburu"
								},
								{
									"code": "KE38",
									"name": "Siaya"
								},
								{
									"code": "KE39",
									"name": "Taita-Taveta"
								},
								{
									"code": "KE40",
									"name": "Tana River"
								},
								{
									"code": "KE41",
									"name": "Tharaka-Nithi"
								},
								{
									"code": "KE42",
									"name": "Trans Nzoia"
								},
								{
									"code": "KE43",
									"name": "Turkana"
								},
								{
									"code": "KE44",
									"name": "Uasin Gishu"
								},
								{
									"code": "KE45",
									"name": "Vihiga"
								},
								{
									"code": "KE46",
									"name": "Wajir"
								},
								{
									"code": "KE47",
									"name": "West Pokot"
								}
							]
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
							"states": [{
									"code": "BM",
									"name": "Bomi"
								},
								{
									"code": "BN",
									"name": "Bong"
								},
								{
									"code": "GA",
									"name": "Gbarpolu"
								},
								{
									"code": "GB",
									"name": "Grand Bassa"
								},
								{
									"code": "GC",
									"name": "Grand Cape Mount"
								},
								{
									"code": "GG",
									"name": "Grand Gedeh"
								},
								{
									"code": "GK",
									"name": "Grand Kru"
								},
								{
									"code": "LO",
									"name": "Lofa"
								},
								{
									"code": "MA",
									"name": "Margibi"
								},
								{
									"code": "MY",
									"name": "Maryland"
								},
								{
									"code": "MO",
									"name": "Montserrado"
								},
								{
									"code": "NM",
									"name": "Nimba"
								},
								{
									"code": "RV",
									"name": "Rivercess"
								},
								{
									"code": "RG",
									"name": "River Gee"
								},
								{
									"code": "SN",
									"name": "Sinoe"
								}
							]
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
							"states": [{
									"code": "MZP",
									"name": "Cabo Delgado"
								},
								{
									"code": "MZG",
									"name": "Gaza"
								},
								{
									"code": "MZI",
									"name": "Inhambane"
								},
								{
									"code": "MZB",
									"name": "Manica"
								},
								{
									"code": "MZL",
									"name": "Maputo Province"
								},
								{
									"code": "MZMPM",
									"name": "Maputo"
								},
								{
									"code": "MZN",
									"name": "Nampula"
								},
								{
									"code": "MZA",
									"name": "Niassa"
								},
								{
									"code": "MZS",
									"name": "Sofala"
								},
								{
									"code": "MZT",
									"name": "Tete"
								},
								{
									"code": "MZQ",
									"name": "Zambézia"
								}
							]
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
							"states": [{
									"code": "ER",
									"name": "Erongo"
								},
								{
									"code": "HA",
									"name": "Hardap"
								},
								{
									"code": "KA",
									"name": "Karas"
								},
								{
									"code": "KE",
									"name": "Kavango East"
								},
								{
									"code": "KW",
									"name": "Kavango West"
								},
								{
									"code": "KH",
									"name": "Khomas"
								},
								{
									"code": "KU",
									"name": "Kunene"
								},
								{
									"code": "OW",
									"name": "Ohangwena"
								},
								{
									"code": "OH",
									"name": "Omaheke"
								},
								{
									"code": "OS",
									"name": "Omusati"
								},
								{
									"code": "ON",
									"name": "Oshana"
								},
								{
									"code": "OT",
									"name": "Oshikoto"
								},
								{
									"code": "OD",
									"name": "Otjozondjupa"
								},
								{
									"code": "CA",
									"name": "Zambezi"
								}
							]
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
							"states": [{
									"code": "AB",
									"name": "Abia"
								},
								{
									"code": "FC",
									"name": "Abuja"
								},
								{
									"code": "AD",
									"name": "Adamawa"
								},
								{
									"code": "AK",
									"name": "Akwa Ibom"
								},
								{
									"code": "AN",
									"name": "Anambra"
								},
								{
									"code": "BA",
									"name": "Bauchi"
								},
								{
									"code": "BY",
									"name": "Bayelsa"
								},
								{
									"code": "BE",
									"name": "Benue"
								},
								{
									"code": "BO",
									"name": "Borno"
								},
								{
									"code": "CR",
									"name": "Cross River"
								},
								{
									"code": "DE",
									"name": "Delta"
								},
								{
									"code": "EB",
									"name": "Ebonyi"
								},
								{
									"code": "ED",
									"name": "Edo"
								},
								{
									"code": "EK",
									"name": "Ekiti"
								},
								{
									"code": "EN",
									"name": "Enugu"
								},
								{
									"code": "GO",
									"name": "Gombe"
								},
								{
									"code": "IM",
									"name": "Imo"
								},
								{
									"code": "JI",
									"name": "Jigawa"
								},
								{
									"code": "KD",
									"name": "Kaduna"
								},
								{
									"code": "KN",
									"name": "Kano"
								},
								{
									"code": "KT",
									"name": "Katsina"
								},
								{
									"code": "KE",
									"name": "Kebbi"
								},
								{
									"code": "KO",
									"name": "Kogi"
								},
								{
									"code": "KW",
									"name": "Kwara"
								},
								{
									"code": "LA",
									"name": "Lagos"
								},
								{
									"code": "NA",
									"name": "Nasarawa"
								},
								{
									"code": "NI",
									"name": "Niger"
								},
								{
									"code": "OG",
									"name": "Ogun"
								},
								{
									"code": "ON",
									"name": "Ondo"
								},
								{
									"code": "OS",
									"name": "Osun"
								},
								{
									"code": "OY",
									"name": "Oyo"
								},
								{
									"code": "PL",
									"name": "Plateau"
								},
								{
									"code": "RI",
									"name": "Rivers"
								},
								{
									"code": "SO",
									"name": "Sokoto"
								},
								{
									"code": "TA",
									"name": "Taraba"
								},
								{
									"code": "YO",
									"name": "Yobe"
								},
								{
									"code": "ZA",
									"name": "Zamfara"
								}
							]
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
							"states": [{
									"code": "TZ01",
									"name": "Arusha"
								},
								{
									"code": "TZ02",
									"name": "Dar es Salaam"
								},
								{
									"code": "TZ03",
									"name": "Dodoma"
								},
								{
									"code": "TZ04",
									"name": "Iringa"
								},
								{
									"code": "TZ05",
									"name": "Kagera"
								},
								{
									"code": "TZ06",
									"name": "Pemba North"
								},
								{
									"code": "TZ07",
									"name": "Zanzibar North"
								},
								{
									"code": "TZ08",
									"name": "Kigoma"
								},
								{
									"code": "TZ09",
									"name": "Kilimanjaro"
								},
								{
									"code": "TZ10",
									"name": "Pemba South"
								},
								{
									"code": "TZ11",
									"name": "Zanzibar South"
								},
								{
									"code": "TZ12",
									"name": "Lindi"
								},
								{
									"code": "TZ13",
									"name": "Mara"
								},
								{
									"code": "TZ14",
									"name": "Mbeya"
								},
								{
									"code": "TZ15",
									"name": "Zanzibar West"
								},
								{
									"code": "TZ16",
									"name": "Morogoro"
								},
								{
									"code": "TZ17",
									"name": "Mtwara"
								},
								{
									"code": "TZ18",
									"name": "Mwanza"
								},
								{
									"code": "TZ19",
									"name": "Coast"
								},
								{
									"code": "TZ20",
									"name": "Rukwa"
								},
								{
									"code": "TZ21",
									"name": "Ruvuma"
								},
								{
									"code": "TZ22",
									"name": "Shinyanga"
								},
								{
									"code": "TZ23",
									"name": "Singida"
								},
								{
									"code": "TZ24",
									"name": "Tabora"
								},
								{
									"code": "TZ25",
									"name": "Tanga"
								},
								{
									"code": "TZ26",
									"name": "Manyara"
								},
								{
									"code": "TZ27",
									"name": "Geita"
								},
								{
									"code": "TZ28",
									"name": "Katavi"
								},
								{
									"code": "TZ29",
									"name": "Njombe"
								},
								{
									"code": "TZ30",
									"name": "Simiyu"
								}
							]
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
							"states": [{
									"code": "UG314",
									"name": "Abim"
								},
								{
									"code": "UG301",
									"name": "Adjumani"
								},
								{
									"code": "UG322",
									"name": "Agago"
								},
								{
									"code": "UG323",
									"name": "Alebtong"
								},
								{
									"code": "UG315",
									"name": "Amolatar"
								},
								{
									"code": "UG324",
									"name": "Amudat"
								},
								{
									"code": "UG216",
									"name": "Amuria"
								},
								{
									"code": "UG316",
									"name": "Amuru"
								},
								{
									"code": "UG302",
									"name": "Apac"
								},
								{
									"code": "UG303",
									"name": "Arua"
								},
								{
									"code": "UG217",
									"name": "Budaka"
								},
								{
									"code": "UG218",
									"name": "Bududa"
								},
								{
									"code": "UG201",
									"name": "Bugiri"
								},
								{
									"code": "UG235",
									"name": "Bugweri"
								},
								{
									"code": "UG420",
									"name": "Buhweju"
								},
								{
									"code": "UG117",
									"name": "Buikwe"
								},
								{
									"code": "UG219",
									"name": "Bukedea"
								},
								{
									"code": "UG118",
									"name": "Bukomansimbi"
								},
								{
									"code": "UG220",
									"name": "Bukwa"
								},
								{
									"code": "UG225",
									"name": "Bulambuli"
								},
								{
									"code": "UG416",
									"name": "Buliisa"
								},
								{
									"code": "UG401",
									"name": "Bundibugyo"
								},
								{
									"code": "UG430",
									"name": "Bunyangabu"
								},
								{
									"code": "UG402",
									"name": "Bushenyi"
								},
								{
									"code": "UG202",
									"name": "Busia"
								},
								{
									"code": "UG221",
									"name": "Butaleja"
								},
								{
									"code": "UG119",
									"name": "Butambala"
								},
								{
									"code": "UG233",
									"name": "Butebo"
								},
								{
									"code": "UG120",
									"name": "Buvuma"
								},
								{
									"code": "UG226",
									"name": "Buyende"
								},
								{
									"code": "UG317",
									"name": "Dokolo"
								},
								{
									"code": "UG121",
									"name": "Gomba"
								},
								{
									"code": "UG304",
									"name": "Gulu"
								},
								{
									"code": "UG403",
									"name": "Hoima"
								},
								{
									"code": "UG417",
									"name": "Ibanda"
								},
								{
									"code": "UG203",
									"name": "Iganga"
								},
								{
									"code": "UG418",
									"name": "Isingiro"
								},
								{
									"code": "UG204",
									"name": "Jinja"
								},
								{
									"code": "UG318",
									"name": "Kaabong"
								},
								{
									"code": "UG404",
									"name": "Kabale"
								},
								{
									"code": "UG405",
									"name": "Kabarole"
								},
								{
									"code": "UG213",
									"name": "Kaberamaido"
								},
								{
									"code": "UG427",
									"name": "Kagadi"
								},
								{
									"code": "UG428",
									"name": "Kakumiro"
								},
								{
									"code": "UG101",
									"name": "Kalangala"
								},
								{
									"code": "UG222",
									"name": "Kaliro"
								},
								{
									"code": "UG122",
									"name": "Kalungu"
								},
								{
									"code": "UG102",
									"name": "Kampala"
								},
								{
									"code": "UG205",
									"name": "Kamuli"
								},
								{
									"code": "UG413",
									"name": "Kamwenge"
								},
								{
									"code": "UG414",
									"name": "Kanungu"
								},
								{
									"code": "UG206",
									"name": "Kapchorwa"
								},
								{
									"code": "UG236",
									"name": "Kapelebyong"
								},
								{
									"code": "UG126",
									"name": "Kasanda"
								},
								{
									"code": "UG406",
									"name": "Kasese"
								},
								{
									"code": "UG207",
									"name": "Katakwi"
								},
								{
									"code": "UG112",
									"name": "Kayunga"
								},
								{
									"code": "UG407",
									"name": "Kibaale"
								},
								{
									"code": "UG103",
									"name": "Kiboga"
								},
								{
									"code": "UG227",
									"name": "Kibuku"
								},
								{
									"code": "UG432",
									"name": "Kikuube"
								},
								{
									"code": "UG419",
									"name": "Kiruhura"
								},
								{
									"code": "UG421",
									"name": "Kiryandongo"
								},
								{
									"code": "UG408",
									"name": "Kisoro"
								},
								{
									"code": "UG305",
									"name": "Kitgum"
								},
								{
									"code": "UG319",
									"name": "Koboko"
								},
								{
									"code": "UG325",
									"name": "Kole"
								},
								{
									"code": "UG306",
									"name": "Kotido"
								},
								{
									"code": "UG208",
									"name": "Kumi"
								},
								{
									"code": "UG333",
									"name": "Kwania"
								},
								{
									"code": "UG228",
									"name": "Kween"
								},
								{
									"code": "UG123",
									"name": "Kyankwanzi"
								},
								{
									"code": "UG422",
									"name": "Kyegegwa"
								},
								{
									"code": "UG415",
									"name": "Kyenjojo"
								},
								{
									"code": "UG125",
									"name": "Kyotera"
								},
								{
									"code": "UG326",
									"name": "Lamwo"
								},
								{
									"code": "UG307",
									"name": "Lira"
								},
								{
									"code": "UG229",
									"name": "Luuka"
								},
								{
									"code": "UG104",
									"name": "Luwero"
								},
								{
									"code": "UG124",
									"name": "Lwengo"
								},
								{
									"code": "UG114",
									"name": "Lyantonde"
								},
								{
									"code": "UG223",
									"name": "Manafwa"
								},
								{
									"code": "UG320",
									"name": "Maracha"
								},
								{
									"code": "UG105",
									"name": "Masaka"
								},
								{
									"code": "UG409",
									"name": "Masindi"
								},
								{
									"code": "UG214",
									"name": "Mayuge"
								},
								{
									"code": "UG209",
									"name": "Mbale"
								},
								{
									"code": "UG410",
									"name": "Mbarara"
								},
								{
									"code": "UG423",
									"name": "Mitooma"
								},
								{
									"code": "UG115",
									"name": "Mityana"
								},
								{
									"code": "UG308",
									"name": "Moroto"
								},
								{
									"code": "UG309",
									"name": "Moyo"
								},
								{
									"code": "UG106",
									"name": "Mpigi"
								},
								{
									"code": "UG107",
									"name": "Mubende"
								},
								{
									"code": "UG108",
									"name": "Mukono"
								},
								{
									"code": "UG334",
									"name": "Nabilatuk"
								},
								{
									"code": "UG311",
									"name": "Nakapiripirit"
								},
								{
									"code": "UG116",
									"name": "Nakaseke"
								},
								{
									"code": "UG109",
									"name": "Nakasongola"
								},
								{
									"code": "UG230",
									"name": "Namayingo"
								},
								{
									"code": "UG234",
									"name": "Namisindwa"
								},
								{
									"code": "UG224",
									"name": "Namutumba"
								},
								{
									"code": "UG327",
									"name": "Napak"
								},
								{
									"code": "UG310",
									"name": "Nebbi"
								},
								{
									"code": "UG231",
									"name": "Ngora"
								},
								{
									"code": "UG424",
									"name": "Ntoroko"
								},
								{
									"code": "UG411",
									"name": "Ntungamo"
								},
								{
									"code": "UG328",
									"name": "Nwoya"
								},
								{
									"code": "UG331",
									"name": "Omoro"
								},
								{
									"code": "UG329",
									"name": "Otuke"
								},
								{
									"code": "UG321",
									"name": "Oyam"
								},
								{
									"code": "UG312",
									"name": "Pader"
								},
								{
									"code": "UG332",
									"name": "Pakwach"
								},
								{
									"code": "UG210",
									"name": "Pallisa"
								},
								{
									"code": "UG110",
									"name": "Rakai"
								},
								{
									"code": "UG429",
									"name": "Rubanda"
								},
								{
									"code": "UG425",
									"name": "Rubirizi"
								},
								{
									"code": "UG431",
									"name": "Rukiga"
								},
								{
									"code": "UG412",
									"name": "Rukungiri"
								},
								{
									"code": "UG111",
									"name": "Sembabule"
								},
								{
									"code": "UG232",
									"name": "Serere"
								},
								{
									"code": "UG426",
									"name": "Sheema"
								},
								{
									"code": "UG215",
									"name": "Sironko"
								},
								{
									"code": "UG211",
									"name": "Soroti"
								},
								{
									"code": "UG212",
									"name": "Tororo"
								},
								{
									"code": "UG113",
									"name": "Wakiso"
								},
								{
									"code": "UG313",
									"name": "Yumbe"
								},
								{
									"code": "UG330",
									"name": "Zombo"
								}
							]
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
							"states": [{
									"code": "EC",
									"name": "Eastern Cape"
								},
								{
									"code": "FS",
									"name": "Free State"
								},
								{
									"code": "GP",
									"name": "Gauteng"
								},
								{
									"code": "KZN",
									"name": "KwaZulu-Natal"
								},
								{
									"code": "LP",
									"name": "Limpopo"
								},
								{
									"code": "MP",
									"name": "Mpumalanga"
								},
								{
									"code": "NC",
									"name": "Northern Cape"
								},
								{
									"code": "NW",
									"name": "North West"
								},
								{
									"code": "WC",
									"name": "Western Cape"
								}
							]
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
							"states": [{
									"code": "ZM-01",
									"name": "Western"
								},
								{
									"code": "ZM-02",
									"name": "Central"
								},
								{
									"code": "ZM-03",
									"name": "Eastern"
								},
								{
									"code": "ZM-04",
									"name": "Luapula"
								},
								{
									"code": "ZM-05",
									"name": "Northern"
								},
								{
									"code": "ZM-06",
									"name": "North-Western"
								},
								{
									"code": "ZM-07",
									"name": "Southern"
								},
								{
									"code": "ZM-08",
									"name": "Copperbelt"
								},
								{
									"code": "ZM-09",
									"name": "Lusaka"
								},
								{
									"code": "ZM-10",
									"name": "Muchinga"
								}
							]
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
							"states": [{
									"code": "BD-05",
									"name": "Bagerhat"
								},
								{
									"code": "BD-01",
									"name": "Bandarban"
								},
								{
									"code": "BD-02",
									"name": "Barguna"
								},
								{
									"code": "BD-06",
									"name": "Barishal"
								},
								{
									"code": "BD-07",
									"name": "Bhola"
								},
								{
									"code": "BD-03",
									"name": "Bogura"
								},
								{
									"code": "BD-04",
									"name": "Brahmanbaria"
								},
								{
									"code": "BD-09",
									"name": "Chandpur"
								},
								{
									"code": "BD-10",
									"name": "Chattogram"
								},
								{
									"code": "BD-12",
									"name": "Chuadanga"
								},
								{
									"code": "BD-11",
									"name": "Cox's Bazar"
								},
								{
									"code": "BD-08",
									"name": "Cumilla"
								},
								{
									"code": "BD-13",
									"name": "Dhaka"
								},
								{
									"code": "BD-14",
									"name": "Dinajpur"
								},
								{
									"code": "BD-15",
									"name": "Faridpur "
								},
								{
									"code": "BD-16",
									"name": "Feni"
								},
								{
									"code": "BD-19",
									"name": "Gaibandha"
								},
								{
									"code": "BD-18",
									"name": "Gazipur"
								},
								{
									"code": "BD-17",
									"name": "Gopalganj"
								},
								{
									"code": "BD-20",
									"name": "Habiganj"
								},
								{
									"code": "BD-21",
									"name": "Jamalpur"
								},
								{
									"code": "BD-22",
									"name": "Jashore"
								},
								{
									"code": "BD-25",
									"name": "Jhalokati"
								},
								{
									"code": "BD-23",
									"name": "Jhenaidah"
								},
								{
									"code": "BD-24",
									"name": "Joypurhat"
								},
								{
									"code": "BD-29",
									"name": "Khagrachhari"
								},
								{
									"code": "BD-27",
									"name": "Khulna"
								},
								{
									"code": "BD-26",
									"name": "Kishoreganj"
								},
								{
									"code": "BD-28",
									"name": "Kurigram"
								},
								{
									"code": "BD-30",
									"name": "Kushtia"
								},
								{
									"code": "BD-31",
									"name": "Lakshmipur"
								},
								{
									"code": "BD-32",
									"name": "Lalmonirhat"
								},
								{
									"code": "BD-36",
									"name": "Madaripur"
								},
								{
									"code": "BD-37",
									"name": "Magura"
								},
								{
									"code": "BD-33",
									"name": "Manikganj "
								},
								{
									"code": "BD-39",
									"name": "Meherpur"
								},
								{
									"code": "BD-38",
									"name": "Moulvibazar"
								},
								{
									"code": "BD-35",
									"name": "Munshiganj"
								},
								{
									"code": "BD-34",
									"name": "Mymensingh"
								},
								{
									"code": "BD-48",
									"name": "Naogaon"
								},
								{
									"code": "BD-43",
									"name": "Narail"
								},
								{
									"code": "BD-40",
									"name": "Narayanganj"
								},
								{
									"code": "BD-42",
									"name": "Narsingdi"
								},
								{
									"code": "BD-44",
									"name": "Natore"
								},
								{
									"code": "BD-45",
									"name": "Nawabganj"
								},
								{
									"code": "BD-41",
									"name": "Netrakona"
								},
								{
									"code": "BD-46",
									"name": "Nilphamari"
								},
								{
									"code": "BD-47",
									"name": "Noakhali"
								},
								{
									"code": "BD-49",
									"name": "Pabna"
								},
								{
									"code": "BD-52",
									"name": "Panchagarh"
								},
								{
									"code": "BD-51",
									"name": "Patuakhali"
								},
								{
									"code": "BD-50",
									"name": "Pirojpur"
								},
								{
									"code": "BD-53",
									"name": "Rajbari"
								},
								{
									"code": "BD-54",
									"name": "Rajshahi"
								},
								{
									"code": "BD-56",
									"name": "Rangamati"
								},
								{
									"code": "BD-55",
									"name": "Rangpur"
								},
								{
									"code": "BD-58",
									"name": "Satkhira"
								},
								{
									"code": "BD-62",
									"name": "Shariatpur"
								},
								{
									"code": "BD-57",
									"name": "Sherpur"
								},
								{
									"code": "BD-59",
									"name": "Sirajganj"
								},
								{
									"code": "BD-61",
									"name": "Sunamganj"
								},
								{
									"code": "BD-60",
									"name": "Sylhet"
								},
								{
									"code": "BD-63",
									"name": "Tangail"
								},
								{
									"code": "BD-64",
									"name": "Thakurgaon"
								}
							]
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
							"states": [{
									"code": "CN1",
									"name": "Yunnan / 云南"
								},
								{
									"code": "CN2",
									"name": "Beijing / 北京"
								},
								{
									"code": "CN3",
									"name": "Tianjin / 天津"
								},
								{
									"code": "CN4",
									"name": "Hebei / 河北"
								},
								{
									"code": "CN5",
									"name": "Shanxi / 山西"
								},
								{
									"code": "CN6",
									"name": "Inner Mongolia / 內蒙古"
								},
								{
									"code": "CN7",
									"name": "Liaoning / 辽宁"
								},
								{
									"code": "CN8",
									"name": "Jilin / 吉林"
								},
								{
									"code": "CN9",
									"name": "Heilongjiang / 黑龙江"
								},
								{
									"code": "CN10",
									"name": "Shanghai / 上海"
								},
								{
									"code": "CN11",
									"name": "Jiangsu / 江苏"
								},
								{
									"code": "CN12",
									"name": "Zhejiang / 浙江"
								},
								{
									"code": "CN13",
									"name": "Anhui / 安徽"
								},
								{
									"code": "CN14",
									"name": "Fujian / 福建"
								},
								{
									"code": "CN15",
									"name": "Jiangxi / 江西"
								},
								{
									"code": "CN16",
									"name": "Shandong / 山东"
								},
								{
									"code": "CN17",
									"name": "Henan / 河南"
								},
								{
									"code": "CN18",
									"name": "Hubei / 湖北"
								},
								{
									"code": "CN19",
									"name": "Hunan / 湖南"
								},
								{
									"code": "CN20",
									"name": "Guangdong / 广东"
								},
								{
									"code": "CN21",
									"name": "Guangxi Zhuang / 广西壮族"
								},
								{
									"code": "CN22",
									"name": "Hainan / 海南"
								},
								{
									"code": "CN23",
									"name": "Chongqing / 重庆"
								},
								{
									"code": "CN24",
									"name": "Sichuan / 四川"
								},
								{
									"code": "CN25",
									"name": "Guizhou / 贵州"
								},
								{
									"code": "CN26",
									"name": "Shaanxi / 陕西"
								},
								{
									"code": "CN27",
									"name": "Gansu / 甘肃"
								},
								{
									"code": "CN28",
									"name": "Qinghai / 青海"
								},
								{
									"code": "CN29",
									"name": "Ningxia Hui / 宁夏"
								},
								{
									"code": "CN30",
									"name": "Macao / 澳门"
								},
								{
									"code": "CN31",
									"name": "Tibet / 西藏"
								},
								{
									"code": "CN32",
									"name": "Xinjiang / 新疆"
								}
							]
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
							"states": [{
									"code": "HONG KONG",
									"name": "Hong Kong Island"
								},
								{
									"code": "KOWLOON",
									"name": "Kowloon"
								},
								{
									"code": "NEW TERRITORIES",
									"name": "New Territories"
								}
							]
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
							"states": [{
									"code": "AC",
									"name": "Daerah Istimewa Aceh"
								},
								{
									"code": "SU",
									"name": "Sumatera Utara"
								},
								{
									"code": "SB",
									"name": "Sumatera Barat"
								},
								{
									"code": "RI",
									"name": "Riau"
								},
								{
									"code": "KR",
									"name": "Kepulauan Riau"
								},
								{
									"code": "JA",
									"name": "Jambi"
								},
								{
									"code": "SS",
									"name": "Sumatera Selatan"
								},
								{
									"code": "BB",
									"name": "Bangka Belitung"
								},
								{
									"code": "BE",
									"name": "Bengkulu"
								},
								{
									"code": "LA",
									"name": "Lampung"
								},
								{
									"code": "JK",
									"name": "DKI Jakarta"
								},
								{
									"code": "JB",
									"name": "Jawa Barat"
								},
								{
									"code": "BT",
									"name": "Banten"
								},
								{
									"code": "JT",
									"name": "Jawa Tengah"
								},
								{
									"code": "JI",
									"name": "Jawa Timur"
								},
								{
									"code": "YO",
									"name": "Daerah Istimewa Yogyakarta"
								},
								{
									"code": "BA",
									"name": "Bali"
								},
								{
									"code": "NB",
									"name": "Nusa Tenggara Barat"
								},
								{
									"code": "NT",
									"name": "Nusa Tenggara Timur"
								},
								{
									"code": "KB",
									"name": "Kalimantan Barat"
								},
								{
									"code": "KT",
									"name": "Kalimantan Tengah"
								},
								{
									"code": "KI",
									"name": "Kalimantan Timur"
								},
								{
									"code": "KS",
									"name": "Kalimantan Selatan"
								},
								{
									"code": "KU",
									"name": "Kalimantan Utara"
								},
								{
									"code": "SA",
									"name": "Sulawesi Utara"
								},
								{
									"code": "ST",
									"name": "Sulawesi Tengah"
								},
								{
									"code": "SG",
									"name": "Sulawesi Tenggara"
								},
								{
									"code": "SR",
									"name": "Sulawesi Barat"
								},
								{
									"code": "SN",
									"name": "Sulawesi Selatan"
								},
								{
									"code": "GO",
									"name": "Gorontalo"
								},
								{
									"code": "MA",
									"name": "Maluku"
								},
								{
									"code": "MU",
									"name": "Maluku Utara"
								},
								{
									"code": "PA",
									"name": "Papua"
								},
								{
									"code": "PB",
									"name": "Papua Barat"
								}
							]
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
							"states": [{
									"code": "AP",
									"name": "Andhra Pradesh"
								},
								{
									"code": "AR",
									"name": "Arunachal Pradesh"
								},
								{
									"code": "AS",
									"name": "Assam"
								},
								{
									"code": "BR",
									"name": "Bihar"
								},
								{
									"code": "CT",
									"name": "Chhattisgarh"
								},
								{
									"code": "GA",
									"name": "Goa"
								},
								{
									"code": "GJ",
									"name": "Gujarat"
								},
								{
									"code": "HR",
									"name": "Haryana"
								},
								{
									"code": "HP",
									"name": "Himachal Pradesh"
								},
								{
									"code": "JK",
									"name": "Jammu and Kashmir"
								},
								{
									"code": "JH",
									"name": "Jharkhand"
								},
								{
									"code": "KA",
									"name": "Karnataka"
								},
								{
									"code": "KL",
									"name": "Kerala"
								},
								{
									"code": "LA",
									"name": "Ladakh"
								},
								{
									"code": "MP",
									"name": "Madhya Pradesh"
								},
								{
									"code": "MH",
									"name": "Maharashtra"
								},
								{
									"code": "MN",
									"name": "Manipur"
								},
								{
									"code": "ML",
									"name": "Meghalaya"
								},
								{
									"code": "MZ",
									"name": "Mizoram"
								},
								{
									"code": "NL",
									"name": "Nagaland"
								},
								{
									"code": "OR",
									"name": "Odisha"
								},
								{
									"code": "PB",
									"name": "Punjab"
								},
								{
									"code": "RJ",
									"name": "Rajasthan"
								},
								{
									"code": "SK",
									"name": "Sikkim"
								},
								{
									"code": "TN",
									"name": "Tamil Nadu"
								},
								{
									"code": "TS",
									"name": "Telangana"
								},
								{
									"code": "TR",
									"name": "Tripura"
								},
								{
									"code": "UK",
									"name": "Uttarakhand"
								},
								{
									"code": "UP",
									"name": "Uttar Pradesh"
								},
								{
									"code": "WB",
									"name": "West Bengal"
								},
								{
									"code": "AN",
									"name": "Andaman and Nicobar Islands"
								},
								{
									"code": "CH",
									"name": "Chandigarh"
								},
								{
									"code": "DN",
									"name": "Dadra and Nagar Haveli"
								},
								{
									"code": "DD",
									"name": "Daman and Diu"
								},
								{
									"code": "DL",
									"name": "Delhi"
								},
								{
									"code": "LD",
									"name": "Lakshadeep"
								},
								{
									"code": "PY",
									"name": "Pondicherry (Puducherry)"
								}
							]
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
							"states": [{
									"code": "KHZ",
									"name": "Khuzestan (خوزستان)"
								},
								{
									"code": "THR",
									"name": "Tehran (تهران)"
								},
								{
									"code": "ILM",
									"name": "Ilaam (ایلام)"
								},
								{
									"code": "BHR",
									"name": "Bushehr (بوشهر)"
								},
								{
									"code": "ADL",
									"name": "Ardabil (اردبیل)"
								},
								{
									"code": "ESF",
									"name": "Isfahan (اصفهان)"
								},
								{
									"code": "YZD",
									"name": "Yazd (یزد)"
								},
								{
									"code": "KRH",
									"name": "Kermanshah (کرمانشاه)"
								},
								{
									"code": "KRN",
									"name": "Kerman (کرمان)"
								},
								{
									"code": "HDN",
									"name": "Hamadan (همدان)"
								},
								{
									"code": "GZN",
									"name": "Ghazvin (قزوین)"
								},
								{
									"code": "ZJN",
									"name": "Zanjan (زنجان)"
								},
								{
									"code": "LRS",
									"name": "Luristan (لرستان)"
								},
								{
									"code": "ABZ",
									"name": "Alborz (البرز)"
								},
								{
									"code": "EAZ",
									"name": "East Azarbaijan (آذربایجان شرقی)"
								},
								{
									"code": "WAZ",
									"name": "West Azarbaijan (آذربایجان غربی)"
								},
								{
									"code": "CHB",
									"name": "Chaharmahal and Bakhtiari (چهارمحال و بختیاری)"
								},
								{
									"code": "SKH",
									"name": "South Khorasan (خراسان جنوبی)"
								},
								{
									"code": "RKH",
									"name": "Razavi Khorasan (خراسان رضوی)"
								},
								{
									"code": "NKH",
									"name": "North Khorasan (خراسان شمالی)"
								},
								{
									"code": "SMN",
									"name": "Semnan (سمنان)"
								},
								{
									"code": "FRS",
									"name": "Fars (فارس)"
								},
								{
									"code": "QHM",
									"name": "Qom (قم)"
								},
								{
									"code": "KRD",
									"name": "Kurdistan / کردستان)"
								},
								{
									"code": "KBD",
									"name": "Kohgiluyeh and BoyerAhmad (کهگیلوییه و بویراحمد)"
								},
								{
									"code": "GLS",
									"name": "Golestan (گلستان)"
								},
								{
									"code": "GIL",
									"name": "Gilan (گیلان)"
								},
								{
									"code": "MZN",
									"name": "Mazandaran (مازندران)"
								},
								{
									"code": "MKZ",
									"name": "Markazi (مرکزی)"
								},
								{
									"code": "HRZ",
									"name": "Hormozgan (هرمزگان)"
								},
								{
									"code": "SBN",
									"name": "Sistan and Baluchestan (سیستان و بلوچستان)"
								}
							]
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
							"states": [{
									"code": "JP01",
									"name": "Hokkaido"
								},
								{
									"code": "JP02",
									"name": "Aomori"
								},
								{
									"code": "JP03",
									"name": "Iwate"
								},
								{
									"code": "JP04",
									"name": "Miyagi"
								},
								{
									"code": "JP05",
									"name": "Akita"
								},
								{
									"code": "JP06",
									"name": "Yamagata"
								},
								{
									"code": "JP07",
									"name": "Fukushima"
								},
								{
									"code": "JP08",
									"name": "Ibaraki"
								},
								{
									"code": "JP09",
									"name": "Tochigi"
								},
								{
									"code": "JP10",
									"name": "Gunma"
								},
								{
									"code": "JP11",
									"name": "Saitama"
								},
								{
									"code": "JP12",
									"name": "Chiba"
								},
								{
									"code": "JP13",
									"name": "Tokyo"
								},
								{
									"code": "JP14",
									"name": "Kanagawa"
								},
								{
									"code": "JP15",
									"name": "Niigata"
								},
								{
									"code": "JP16",
									"name": "Toyama"
								},
								{
									"code": "JP17",
									"name": "Ishikawa"
								},
								{
									"code": "JP18",
									"name": "Fukui"
								},
								{
									"code": "JP19",
									"name": "Yamanashi"
								},
								{
									"code": "JP20",
									"name": "Nagano"
								},
								{
									"code": "JP21",
									"name": "Gifu"
								},
								{
									"code": "JP22",
									"name": "Shizuoka"
								},
								{
									"code": "JP23",
									"name": "Aichi"
								},
								{
									"code": "JP24",
									"name": "Mie"
								},
								{
									"code": "JP25",
									"name": "Shiga"
								},
								{
									"code": "JP26",
									"name": "Kyoto"
								},
								{
									"code": "JP27",
									"name": "Osaka"
								},
								{
									"code": "JP28",
									"name": "Hyogo"
								},
								{
									"code": "JP29",
									"name": "Nara"
								},
								{
									"code": "JP30",
									"name": "Wakayama"
								},
								{
									"code": "JP31",
									"name": "Tottori"
								},
								{
									"code": "JP32",
									"name": "Shimane"
								},
								{
									"code": "JP33",
									"name": "Okayama"
								},
								{
									"code": "JP34",
									"name": "Hiroshima"
								},
								{
									"code": "JP35",
									"name": "Yamaguchi"
								},
								{
									"code": "JP36",
									"name": "Tokushima"
								},
								{
									"code": "JP37",
									"name": "Kagawa"
								},
								{
									"code": "JP38",
									"name": "Ehime"
								},
								{
									"code": "JP39",
									"name": "Kochi"
								},
								{
									"code": "JP40",
									"name": "Fukuoka"
								},
								{
									"code": "JP41",
									"name": "Saga"
								},
								{
									"code": "JP42",
									"name": "Nagasaki"
								},
								{
									"code": "JP43",
									"name": "Kumamoto"
								},
								{
									"code": "JP44",
									"name": "Oita"
								},
								{
									"code": "JP45",
									"name": "Miyazaki"
								},
								{
									"code": "JP46",
									"name": "Kagoshima"
								},
								{
									"code": "JP47",
									"name": "Okinawa"
								}
							]
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
							"states": [{
									"code": "AT",
									"name": "Attapeu"
								},
								{
									"code": "BK",
									"name": "Bokeo"
								},
								{
									"code": "BL",
									"name": "Bolikhamsai"
								},
								{
									"code": "CH",
									"name": "Champasak"
								},
								{
									"code": "HO",
									"name": "Houaphanh"
								},
								{
									"code": "KH",
									"name": "Khammouane"
								},
								{
									"code": "LM",
									"name": "Luang Namtha"
								},
								{
									"code": "LP",
									"name": "Luang Prabang"
								},
								{
									"code": "OU",
									"name": "Oudomxay"
								},
								{
									"code": "PH",
									"name": "Phongsaly"
								},
								{
									"code": "SL",
									"name": "Salavan"
								},
								{
									"code": "SV",
									"name": "Savannakhet"
								},
								{
									"code": "VI",
									"name": "Vientiane Province"
								},
								{
									"code": "VT",
									"name": "Vientiane"
								},
								{
									"code": "XA",
									"name": "Sainyabuli"
								},
								{
									"code": "XE",
									"name": "Sekong"
								},
								{
									"code": "XI",
									"name": "Xiangkhouang"
								},
								{
									"code": "XS",
									"name": "Xaisomboun"
								}
							]
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
							"states": [{
									"code": "JHR",
									"name": "Johor"
								},
								{
									"code": "KDH",
									"name": "Kedah"
								},
								{
									"code": "KTN",
									"name": "Kelantan"
								},
								{
									"code": "LBN",
									"name": "Labuan"
								},
								{
									"code": "MLK",
									"name": "Malacca (Melaka)"
								},
								{
									"code": "NSN",
									"name": "Negeri Sembilan"
								},
								{
									"code": "PHG",
									"name": "Pahang"
								},
								{
									"code": "PNG",
									"name": "Penang (Pulau Pinang)"
								},
								{
									"code": "PRK",
									"name": "Perak"
								},
								{
									"code": "PLS",
									"name": "Perlis"
								},
								{
									"code": "SBH",
									"name": "Sabah"
								},
								{
									"code": "SWK",
									"name": "Sarawak"
								},
								{
									"code": "SGR",
									"name": "Selangor"
								},
								{
									"code": "TRG",
									"name": "Terengganu"
								},
								{
									"code": "PJY",
									"name": "Putrajaya"
								},
								{
									"code": "KUL",
									"name": "Kuala Lumpur"
								}
							]
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
							"states": [{
									"code": "BAG",
									"name": "Bagmati"
								},
								{
									"code": "BHE",
									"name": "Bheri"
								},
								{
									"code": "DHA",
									"name": "Dhaulagiri"
								},
								{
									"code": "GAN",
									"name": "Gandaki"
								},
								{
									"code": "JAN",
									"name": "Janakpur"
								},
								{
									"code": "KAR",
									"name": "Karnali"
								},
								{
									"code": "KOS",
									"name": "Koshi"
								},
								{
									"code": "LUM",
									"name": "Lumbini"
								},
								{
									"code": "MAH",
									"name": "Mahakali"
								},
								{
									"code": "MEC",
									"name": "Mechi"
								},
								{
									"code": "NAR",
									"name": "Narayani"
								},
								{
									"code": "RAP",
									"name": "Rapti"
								},
								{
									"code": "SAG",
									"name": "Sagarmatha"
								},
								{
									"code": "SET",
									"name": "Seti"
								}
							]
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
							"states": [{
									"code": "ABR",
									"name": "Abra"
								},
								{
									"code": "AGN",
									"name": "Agusan del Norte"
								},
								{
									"code": "AGS",
									"name": "Agusan del Sur"
								},
								{
									"code": "AKL",
									"name": "Aklan"
								},
								{
									"code": "ALB",
									"name": "Albay"
								},
								{
									"code": "ANT",
									"name": "Antique"
								},
								{
									"code": "APA",
									"name": "Apayao"
								},
								{
									"code": "AUR",
									"name": "Aurora"
								},
								{
									"code": "BAS",
									"name": "Basilan"
								},
								{
									"code": "BAN",
									"name": "Bataan"
								},
								{
									"code": "BTN",
									"name": "Batanes"
								},
								{
									"code": "BTG",
									"name": "Batangas"
								},
								{
									"code": "BEN",
									"name": "Benguet"
								},
								{
									"code": "BIL",
									"name": "Biliran"
								},
								{
									"code": "BOH",
									"name": "Bohol"
								},
								{
									"code": "BUK",
									"name": "Bukidnon"
								},
								{
									"code": "BUL",
									"name": "Bulacan"
								},
								{
									"code": "CAG",
									"name": "Cagayan"
								},
								{
									"code": "CAN",
									"name": "Camarines Norte"
								},
								{
									"code": "CAS",
									"name": "Camarines Sur"
								},
								{
									"code": "CAM",
									"name": "Camiguin"
								},
								{
									"code": "CAP",
									"name": "Capiz"
								},
								{
									"code": "CAT",
									"name": "Catanduanes"
								},
								{
									"code": "CAV",
									"name": "Cavite"
								},
								{
									"code": "CEB",
									"name": "Cebu"
								},
								{
									"code": "COM",
									"name": "Compostela Valley"
								},
								{
									"code": "NCO",
									"name": "Cotabato"
								},
								{
									"code": "DAV",
									"name": "Davao del Norte"
								},
								{
									"code": "DAS",
									"name": "Davao del Sur"
								},
								{
									"code": "DAC",
									"name": "Davao Occidental"
								},
								{
									"code": "DAO",
									"name": "Davao Oriental"
								},
								{
									"code": "DIN",
									"name": "Dinagat Islands"
								},
								{
									"code": "EAS",
									"name": "Eastern Samar"
								},
								{
									"code": "GUI",
									"name": "Guimaras"
								},
								{
									"code": "IFU",
									"name": "Ifugao"
								},
								{
									"code": "ILN",
									"name": "Ilocos Norte"
								},
								{
									"code": "ILS",
									"name": "Ilocos Sur"
								},
								{
									"code": "ILI",
									"name": "Iloilo"
								},
								{
									"code": "ISA",
									"name": "Isabela"
								},
								{
									"code": "KAL",
									"name": "Kalinga"
								},
								{
									"code": "LUN",
									"name": "La Union"
								},
								{
									"code": "LAG",
									"name": "Laguna"
								},
								{
									"code": "LAN",
									"name": "Lanao del Norte"
								},
								{
									"code": "LAS",
									"name": "Lanao del Sur"
								},
								{
									"code": "LEY",
									"name": "Leyte"
								},
								{
									"code": "MAG",
									"name": "Maguindanao"
								},
								{
									"code": "MAD",
									"name": "Marinduque"
								},
								{
									"code": "MAS",
									"name": "Masbate"
								},
								{
									"code": "MSC",
									"name": "Misamis Occidental"
								},
								{
									"code": "MSR",
									"name": "Misamis Oriental"
								},
								{
									"code": "MOU",
									"name": "Mountain Province"
								},
								{
									"code": "NEC",
									"name": "Negros Occidental"
								},
								{
									"code": "NER",
									"name": "Negros Oriental"
								},
								{
									"code": "NSA",
									"name": "Northern Samar"
								},
								{
									"code": "NUE",
									"name": "Nueva Ecija"
								},
								{
									"code": "NUV",
									"name": "Nueva Vizcaya"
								},
								{
									"code": "MDC",
									"name": "Occidental Mindoro"
								},
								{
									"code": "MDR",
									"name": "Oriental Mindoro"
								},
								{
									"code": "PLW",
									"name": "Palawan"
								},
								{
									"code": "PAM",
									"name": "Pampanga"
								},
								{
									"code": "PAN",
									"name": "Pangasinan"
								},
								{
									"code": "QUE",
									"name": "Quezon"
								},
								{
									"code": "QUI",
									"name": "Quirino"
								},
								{
									"code": "RIZ",
									"name": "Rizal"
								},
								{
									"code": "ROM",
									"name": "Romblon"
								},
								{
									"code": "WSA",
									"name": "Samar"
								},
								{
									"code": "SAR",
									"name": "Sarangani"
								},
								{
									"code": "SIQ",
									"name": "Siquijor"
								},
								{
									"code": "SOR",
									"name": "Sorsogon"
								},
								{
									"code": "SCO",
									"name": "South Cotabato"
								},
								{
									"code": "SLE",
									"name": "Southern Leyte"
								},
								{
									"code": "SUK",
									"name": "Sultan Kudarat"
								},
								{
									"code": "SLU",
									"name": "Sulu"
								},
								{
									"code": "SUN",
									"name": "Surigao del Norte"
								},
								{
									"code": "SUR",
									"name": "Surigao del Sur"
								},
								{
									"code": "TAR",
									"name": "Tarlac"
								},
								{
									"code": "TAW",
									"name": "Tawi-Tawi"
								},
								{
									"code": "ZMB",
									"name": "Zambales"
								},
								{
									"code": "ZAN",
									"name": "Zamboanga del Norte"
								},
								{
									"code": "ZAS",
									"name": "Zamboanga del Sur"
								},
								{
									"code": "ZSI",
									"name": "Zamboanga Sibugay"
								},
								{
									"code": "00",
									"name": "Metro Manila"
								}
							]
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
							"states": [{
									"code": "JK",
									"name": "Azad Kashmir"
								},
								{
									"code": "BA",
									"name": "Balochistan"
								},
								{
									"code": "TA",
									"name": "FATA"
								},
								{
									"code": "GB",
									"name": "Gilgit Baltistan"
								},
								{
									"code": "IS",
									"name": "Islamabad Capital Territory"
								},
								{
									"code": "KP",
									"name": "Khyber Pakhtunkhwa"
								},
								{
									"code": "PB",
									"name": "Punjab"
								},
								{
									"code": "SD",
									"name": "Sindh"
								}
							]
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
							"states": [{
									"code": "TH-37",
									"name": "Amnat Charoen"
								},
								{
									"code": "TH-15",
									"name": "Ang Thong"
								},
								{
									"code": "TH-14",
									"name": "Ayutthaya"
								},
								{
									"code": "TH-10",
									"name": "Bangkok"
								},
								{
									"code": "TH-38",
									"name": "Bueng Kan"
								},
								{
									"code": "TH-31",
									"name": "Buri Ram"
								},
								{
									"code": "TH-24",
									"name": "Chachoengsao"
								},
								{
									"code": "TH-18",
									"name": "Chai Nat"
								},
								{
									"code": "TH-36",
									"name": "Chaiyaphum"
								},
								{
									"code": "TH-22",
									"name": "Chanthaburi"
								},
								{
									"code": "TH-50",
									"name": "Chiang Mai"
								},
								{
									"code": "TH-57",
									"name": "Chiang Rai"
								},
								{
									"code": "TH-20",
									"name": "Chonburi"
								},
								{
									"code": "TH-86",
									"name": "Chumphon"
								},
								{
									"code": "TH-46",
									"name": "Kalasin"
								},
								{
									"code": "TH-62",
									"name": "Kamphaeng Phet"
								},
								{
									"code": "TH-71",
									"name": "Kanchanaburi"
								},
								{
									"code": "TH-40",
									"name": "Khon Kaen"
								},
								{
									"code": "TH-81",
									"name": "Krabi"
								},
								{
									"code": "TH-52",
									"name": "Lampang"
								},
								{
									"code": "TH-51",
									"name": "Lamphun"
								},
								{
									"code": "TH-42",
									"name": "Loei"
								},
								{
									"code": "TH-16",
									"name": "Lopburi"
								},
								{
									"code": "TH-58",
									"name": "Mae Hong Son"
								},
								{
									"code": "TH-44",
									"name": "Maha Sarakham"
								},
								{
									"code": "TH-49",
									"name": "Mukdahan"
								},
								{
									"code": "TH-26",
									"name": "Nakhon Nayok"
								},
								{
									"code": "TH-73",
									"name": "Nakhon Pathom"
								},
								{
									"code": "TH-48",
									"name": "Nakhon Phanom"
								},
								{
									"code": "TH-30",
									"name": "Nakhon Ratchasima"
								},
								{
									"code": "TH-60",
									"name": "Nakhon Sawan"
								},
								{
									"code": "TH-80",
									"name": "Nakhon Si Thammarat"
								},
								{
									"code": "TH-55",
									"name": "Nan"
								},
								{
									"code": "TH-96",
									"name": "Narathiwat"
								},
								{
									"code": "TH-39",
									"name": "Nong Bua Lam Phu"
								},
								{
									"code": "TH-43",
									"name": "Nong Khai"
								},
								{
									"code": "TH-12",
									"name": "Nonthaburi"
								},
								{
									"code": "TH-13",
									"name": "Pathum Thani"
								},
								{
									"code": "TH-94",
									"name": "Pattani"
								},
								{
									"code": "TH-82",
									"name": "Phang Nga"
								},
								{
									"code": "TH-93",
									"name": "Phatthalung"
								},
								{
									"code": "TH-56",
									"name": "Phayao"
								},
								{
									"code": "TH-67",
									"name": "Phetchabun"
								},
								{
									"code": "TH-76",
									"name": "Phetchaburi"
								},
								{
									"code": "TH-66",
									"name": "Phichit"
								},
								{
									"code": "TH-65",
									"name": "Phitsanulok"
								},
								{
									"code": "TH-54",
									"name": "Phrae"
								},
								{
									"code": "TH-83",
									"name": "Phuket"
								},
								{
									"code": "TH-25",
									"name": "Prachin Buri"
								},
								{
									"code": "TH-77",
									"name": "Prachuap Khiri Khan"
								},
								{
									"code": "TH-85",
									"name": "Ranong"
								},
								{
									"code": "TH-70",
									"name": "Ratchaburi"
								},
								{
									"code": "TH-21",
									"name": "Rayong"
								},
								{
									"code": "TH-45",
									"name": "Roi Et"
								},
								{
									"code": "TH-27",
									"name": "Sa Kaeo"
								},
								{
									"code": "TH-47",
									"name": "Sakon Nakhon"
								},
								{
									"code": "TH-11",
									"name": "Samut Prakan"
								},
								{
									"code": "TH-74",
									"name": "Samut Sakhon"
								},
								{
									"code": "TH-75",
									"name": "Samut Songkhram"
								},
								{
									"code": "TH-19",
									"name": "Saraburi"
								},
								{
									"code": "TH-91",
									"name": "Satun"
								},
								{
									"code": "TH-17",
									"name": "Sing Buri"
								},
								{
									"code": "TH-33",
									"name": "Sisaket"
								},
								{
									"code": "TH-90",
									"name": "Songkhla"
								},
								{
									"code": "TH-64",
									"name": "Sukhothai"
								},
								{
									"code": "TH-72",
									"name": "Suphan Buri"
								},
								{
									"code": "TH-84",
									"name": "Surat Thani"
								},
								{
									"code": "TH-32",
									"name": "Surin"
								},
								{
									"code": "TH-63",
									"name": "Tak"
								},
								{
									"code": "TH-92",
									"name": "Trang"
								},
								{
									"code": "TH-23",
									"name": "Trat"
								},
								{
									"code": "TH-34",
									"name": "Ubon Ratchathani"
								},
								{
									"code": "TH-41",
									"name": "Udon Thani"
								},
								{
									"code": "TH-61",
									"name": "Uthai Thani"
								},
								{
									"code": "TH-53",
									"name": "Uttaradit"
								},
								{
									"code": "TH-95",
									"name": "Yala"
								},
								{
									"code": "TH-35",
									"name": "Yasothon"
								}
							]
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
							"states": [{
									"code": "AB",
									"name": "Alberta"
								},
								{
									"code": "BC",
									"name": "British Columbia"
								},
								{
									"code": "MB",
									"name": "Manitoba"
								},
								{
									"code": "NB",
									"name": "New Brunswick"
								},
								{
									"code": "NL",
									"name": "Newfoundland and Labrador"
								},
								{
									"code": "NT",
									"name": "Northwest Territories"
								},
								{
									"code": "NS",
									"name": "Nova Scotia"
								},
								{
									"code": "NU",
									"name": "Nunavut"
								},
								{
									"code": "ON",
									"name": "Ontario"
								},
								{
									"code": "PE",
									"name": "Prince Edward Island"
								},
								{
									"code": "QC",
									"name": "Quebec"
								},
								{
									"code": "SK",
									"name": "Saskatchewan"
								},
								{
									"code": "YT",
									"name": "Yukon Territory"
								}
							]
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
							"states": [{
									"code": "CR-A",
									"name": "Alajuela"
								},
								{
									"code": "CR-C",
									"name": "Cartago"
								},
								{
									"code": "CR-G",
									"name": "Guanacaste"
								},
								{
									"code": "CR-H",
									"name": "Heredia"
								},
								{
									"code": "CR-L",
									"name": "Limón"
								},
								{
									"code": "CR-P",
									"name": "Puntarenas"
								},
								{
									"code": "CR-SJ",
									"name": "San José"
								}
							]
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
							"states": [{
									"code": "DO-01",
									"name": "Distrito Nacional"
								},
								{
									"code": "DO-02",
									"name": "Azua"
								},
								{
									"code": "DO-03",
									"name": "Baoruco"
								},
								{
									"code": "DO-04",
									"name": "Barahona"
								},
								{
									"code": "DO-33",
									"name": "Cibao Nordeste"
								},
								{
									"code": "DO-34",
									"name": "Cibao Noroeste"
								},
								{
									"code": "DO-35",
									"name": "Cibao Norte"
								},
								{
									"code": "DO-36",
									"name": "Cibao Sur"
								},
								{
									"code": "DO-05",
									"name": "Dajabón"
								},
								{
									"code": "DO-06",
									"name": "Duarte"
								},
								{
									"code": "DO-08",
									"name": "El Seibo"
								},
								{
									"code": "DO-37",
									"name": "El Valle"
								},
								{
									"code": "DO-07",
									"name": "Elías Piña"
								},
								{
									"code": "DO-38",
									"name": "Enriquillo"
								},
								{
									"code": "DO-09",
									"name": "Espaillat"
								},
								{
									"code": "DO-30",
									"name": "Hato Mayor"
								},
								{
									"code": "DO-19",
									"name": "Hermanas Mirabal"
								},
								{
									"code": "DO-39",
									"name": "Higüamo"
								},
								{
									"code": "DO-10",
									"name": "Independencia"
								},
								{
									"code": "DO-11",
									"name": "La Altagracia"
								},
								{
									"code": "DO-12",
									"name": "La Romana"
								},
								{
									"code": "DO-13",
									"name": "La Vega"
								},
								{
									"code": "DO-14",
									"name": "María Trinidad Sánchez"
								},
								{
									"code": "DO-28",
									"name": "Monseñor Nouel"
								},
								{
									"code": "DO-15",
									"name": "Monte Cristi"
								},
								{
									"code": "DO-29",
									"name": "Monte Plata"
								},
								{
									"code": "DO-40",
									"name": "Ozama"
								},
								{
									"code": "DO-16",
									"name": "Pedernales"
								},
								{
									"code": "DO-17",
									"name": "Peravia"
								},
								{
									"code": "DO-18",
									"name": "Puerto Plata"
								},
								{
									"code": "DO-20",
									"name": "Samaná"
								},
								{
									"code": "DO-21",
									"name": "San Cristóbal"
								},
								{
									"code": "DO-31",
									"name": "San José de Ocoa"
								},
								{
									"code": "DO-22",
									"name": "San Juan"
								},
								{
									"code": "DO-23",
									"name": "San Pedro de Macorís"
								},
								{
									"code": "DO-24",
									"name": "Sánchez Ramírez"
								},
								{
									"code": "DO-25",
									"name": "Santiago"
								},
								{
									"code": "DO-26",
									"name": "Santiago Rodríguez"
								},
								{
									"code": "DO-32",
									"name": "Santo Domingo"
								},
								{
									"code": "DO-41",
									"name": "Valdesia"
								},
								{
									"code": "DO-27",
									"name": "Valverde"
								},
								{
									"code": "DO-42",
									"name": "Yuma"
								}
							]
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
							"states": [{
									"code": "GT-AV",
									"name": "Alta Verapaz"
								},
								{
									"code": "GT-BV",
									"name": "Baja Verapaz"
								},
								{
									"code": "GT-CM",
									"name": "Chimaltenango"
								},
								{
									"code": "GT-CQ",
									"name": "Chiquimula"
								},
								{
									"code": "GT-PR",
									"name": "El Progreso"
								},
								{
									"code": "GT-ES",
									"name": "Escuintla"
								},
								{
									"code": "GT-GU",
									"name": "Guatemala"
								},
								{
									"code": "GT-HU",
									"name": "Huehuetenango"
								},
								{
									"code": "GT-IZ",
									"name": "Izabal"
								},
								{
									"code": "GT-JA",
									"name": "Jalapa"
								},
								{
									"code": "GT-JU",
									"name": "Jutiapa"
								},
								{
									"code": "GT-PE",
									"name": "Petén"
								},
								{
									"code": "GT-QZ",
									"name": "Quetzaltenango"
								},
								{
									"code": "GT-QC",
									"name": "Quiché"
								},
								{
									"code": "GT-RE",
									"name": "Retalhuleu"
								},
								{
									"code": "GT-SA",
									"name": "Sacatepéquez"
								},
								{
									"code": "GT-SM",
									"name": "San Marcos"
								},
								{
									"code": "GT-SR",
									"name": "Santa Rosa"
								},
								{
									"code": "GT-SO",
									"name": "Sololá"
								},
								{
									"code": "GT-SU",
									"name": "Suchitepéquez"
								},
								{
									"code": "GT-TO",
									"name": "Totonicapán"
								},
								{
									"code": "GT-ZA",
									"name": "Zacapa"
								}
							]
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
							"states": [{
									"code": "HN-AT",
									"name": "Atlántida"
								},
								{
									"code": "HN-IB",
									"name": "Bay Islands"
								},
								{
									"code": "HN-CH",
									"name": "Choluteca"
								},
								{
									"code": "HN-CL",
									"name": "Colón"
								},
								{
									"code": "HN-CM",
									"name": "Comayagua"
								},
								{
									"code": "HN-CP",
									"name": "Copán"
								},
								{
									"code": "HN-CR",
									"name": "Cortés"
								},
								{
									"code": "HN-EP",
									"name": "El Paraíso"
								},
								{
									"code": "HN-FM",
									"name": "Francisco Morazán"
								},
								{
									"code": "HN-GD",
									"name": "Gracias a Dios"
								},
								{
									"code": "HN-IN",
									"name": "Intibucá"
								},
								{
									"code": "HN-LE",
									"name": "Lempira"
								},
								{
									"code": "HN-LP",
									"name": "La Paz"
								},
								{
									"code": "HN-OC",
									"name": "Ocotepeque"
								},
								{
									"code": "HN-OL",
									"name": "Olancho"
								},
								{
									"code": "HN-SB",
									"name": "Santa Bárbara"
								},
								{
									"code": "HN-VA",
									"name": "Valle"
								},
								{
									"code": "HN-YO",
									"name": "Yoro"
								}
							]
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
							"states": [{
									"code": "JM-01",
									"name": "Kingston"
								},
								{
									"code": "JM-02",
									"name": "Saint Andrew"
								},
								{
									"code": "JM-03",
									"name": "Saint Thomas"
								},
								{
									"code": "JM-04",
									"name": "Portland"
								},
								{
									"code": "JM-05",
									"name": "Saint Mary"
								},
								{
									"code": "JM-06",
									"name": "Saint Ann"
								},
								{
									"code": "JM-07",
									"name": "Trelawny"
								},
								{
									"code": "JM-08",
									"name": "Saint James"
								},
								{
									"code": "JM-09",
									"name": "Hanover"
								},
								{
									"code": "JM-10",
									"name": "Westmoreland"
								},
								{
									"code": "JM-11",
									"name": "Saint Elizabeth"
								},
								{
									"code": "JM-12",
									"name": "Manchester"
								},
								{
									"code": "JM-13",
									"name": "Clarendon"
								},
								{
									"code": "JM-14",
									"name": "Saint Catherine"
								}
							]
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
							"states": [{
									"code": "DF",
									"name": "Ciudad de México"
								},
								{
									"code": "JA",
									"name": "Jalisco"
								},
								{
									"code": "NL",
									"name": "Nuevo León"
								},
								{
									"code": "AG",
									"name": "Aguascalientes"
								},
								{
									"code": "BC",
									"name": "Baja California"
								},
								{
									"code": "BS",
									"name": "Baja California Sur"
								},
								{
									"code": "CM",
									"name": "Campeche"
								},
								{
									"code": "CS",
									"name": "Chiapas"
								},
								{
									"code": "CH",
									"name": "Chihuahua"
								},
								{
									"code": "CO",
									"name": "Coahuila"
								},
								{
									"code": "CL",
									"name": "Colima"
								},
								{
									"code": "DG",
									"name": "Durango"
								},
								{
									"code": "GT",
									"name": "Guanajuato"
								},
								{
									"code": "GR",
									"name": "Guerrero"
								},
								{
									"code": "HG",
									"name": "Hidalgo"
								},
								{
									"code": "MX",
									"name": "Estado de México"
								},
								{
									"code": "MI",
									"name": "Michoacán"
								},
								{
									"code": "MO",
									"name": "Morelos"
								},
								{
									"code": "NA",
									"name": "Nayarit"
								},
								{
									"code": "OA",
									"name": "Oaxaca"
								},
								{
									"code": "PU",
									"name": "Puebla"
								},
								{
									"code": "QT",
									"name": "Querétaro"
								},
								{
									"code": "QR",
									"name": "Quintana Roo"
								},
								{
									"code": "SL",
									"name": "San Luis Potosí"
								},
								{
									"code": "SI",
									"name": "Sinaloa"
								},
								{
									"code": "SO",
									"name": "Sonora"
								},
								{
									"code": "TB",
									"name": "Tabasco"
								},
								{
									"code": "TM",
									"name": "Tamaulipas"
								},
								{
									"code": "TL",
									"name": "Tlaxcala"
								},
								{
									"code": "VE",
									"name": "Veracruz"
								},
								{
									"code": "YU",
									"name": "Yucatán"
								},
								{
									"code": "ZA",
									"name": "Zacatecas"
								}
							]
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
							"states": [{
									"code": "NI-AN",
									"name": "Atlántico Norte"
								},
								{
									"code": "NI-AS",
									"name": "Atlántico Sur"
								},
								{
									"code": "NI-BO",
									"name": "Boaco"
								},
								{
									"code": "NI-CA",
									"name": "Carazo"
								},
								{
									"code": "NI-CI",
									"name": "Chinandega"
								},
								{
									"code": "NI-CO",
									"name": "Chontales"
								},
								{
									"code": "NI-ES",
									"name": "Estelí"
								},
								{
									"code": "NI-GR",
									"name": "Granada"
								},
								{
									"code": "NI-JI",
									"name": "Jinotega"
								},
								{
									"code": "NI-LE",
									"name": "León"
								},
								{
									"code": "NI-MD",
									"name": "Madriz"
								},
								{
									"code": "NI-MN",
									"name": "Managua"
								},
								{
									"code": "NI-MS",
									"name": "Masaya"
								},
								{
									"code": "NI-MT",
									"name": "Matagalpa"
								},
								{
									"code": "NI-NS",
									"name": "Nueva Segovia"
								},
								{
									"code": "NI-RI",
									"name": "Rivas"
								},
								{
									"code": "NI-SJ",
									"name": "Río San Juan"
								}
							]
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
							"states": [{
									"code": "PA-1",
									"name": "Bocas del Toro"
								},
								{
									"code": "PA-2",
									"name": "Coclé"
								},
								{
									"code": "PA-3",
									"name": "Colón"
								},
								{
									"code": "PA-4",
									"name": "Chiriquí"
								},
								{
									"code": "PA-5",
									"name": "Darién"
								},
								{
									"code": "PA-6",
									"name": "Herrera"
								},
								{
									"code": "PA-7",
									"name": "Los Santos"
								},
								{
									"code": "PA-8",
									"name": "Panamá"
								},
								{
									"code": "PA-9",
									"name": "Veraguas"
								},
								{
									"code": "PA-10",
									"name": "West Panamá"
								},
								{
									"code": "PA-EM",
									"name": "Emberá"
								},
								{
									"code": "PA-KY",
									"name": "Guna Yala"
								},
								{
									"code": "PA-NB",
									"name": "Ngöbe-Buglé"
								}
							]
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
							"states": [{
									"code": "SV-AH",
									"name": "Ahuachapán"
								},
								{
									"code": "SV-CA",
									"name": "Cabañas"
								},
								{
									"code": "SV-CH",
									"name": "Chalatenango"
								},
								{
									"code": "SV-CU",
									"name": "Cuscatlán"
								},
								{
									"code": "SV-LI",
									"name": "La Libertad"
								},
								{
									"code": "SV-MO",
									"name": "Morazán"
								},
								{
									"code": "SV-PA",
									"name": "La Paz"
								},
								{
									"code": "SV-SA",
									"name": "Santa Ana"
								},
								{
									"code": "SV-SM",
									"name": "San Miguel"
								},
								{
									"code": "SV-SO",
									"name": "Sonsonate"
								},
								{
									"code": "SV-SS",
									"name": "San Salvador"
								},
								{
									"code": "SV-SV",
									"name": "San Vicente"
								},
								{
									"code": "SV-UN",
									"name": "La Unión"
								},
								{
									"code": "SV-US",
									"name": "Usulután"
								}
							]
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
							"states": [{
									"code": "AL",
									"name": "Alabama"
								},
								{
									"code": "AK",
									"name": "Alaska"
								},
								{
									"code": "AZ",
									"name": "Arizona"
								},
								{
									"code": "AR",
									"name": "Arkansas"
								},
								{
									"code": "CA",
									"name": "California"
								},
								{
									"code": "CO",
									"name": "Colorado"
								},
								{
									"code": "CT",
									"name": "Connecticut"
								},
								{
									"code": "DE",
									"name": "Delaware"
								},
								{
									"code": "DC",
									"name": "District Of Columbia"
								},
								{
									"code": "FL",
									"name": "Florida"
								},
								{
									"code": "GA",
									"name": "Georgia"
								},
								{
									"code": "HI",
									"name": "Hawaii"
								},
								{
									"code": "ID",
									"name": "Idaho"
								},
								{
									"code": "IL",
									"name": "Illinois"
								},
								{
									"code": "IN",
									"name": "Indiana"
								},
								{
									"code": "IA",
									"name": "Iowa"
								},
								{
									"code": "KS",
									"name": "Kansas"
								},
								{
									"code": "KY",
									"name": "Kentucky"
								},
								{
									"code": "LA",
									"name": "Louisiana"
								},
								{
									"code": "ME",
									"name": "Maine"
								},
								{
									"code": "MD",
									"name": "Maryland"
								},
								{
									"code": "MA",
									"name": "Massachusetts"
								},
								{
									"code": "MI",
									"name": "Michigan"
								},
								{
									"code": "MN",
									"name": "Minnesota"
								},
								{
									"code": "MS",
									"name": "Mississippi"
								},
								{
									"code": "MO",
									"name": "Missouri"
								},
								{
									"code": "MT",
									"name": "Montana"
								},
								{
									"code": "NE",
									"name": "Nebraska"
								},
								{
									"code": "NV",
									"name": "Nevada"
								},
								{
									"code": "NH",
									"name": "New Hampshire"
								},
								{
									"code": "NJ",
									"name": "New Jersey"
								},
								{
									"code": "NM",
									"name": "New Mexico"
								},
								{
									"code": "NY",
									"name": "New York"
								},
								{
									"code": "NC",
									"name": "North Carolina"
								},
								{
									"code": "ND",
									"name": "North Dakota"
								},
								{
									"code": "OH",
									"name": "Ohio"
								},
								{
									"code": "OK",
									"name": "Oklahoma"
								},
								{
									"code": "OR",
									"name": "Oregon"
								},
								{
									"code": "PA",
									"name": "Pennsylvania"
								},
								{
									"code": "RI",
									"name": "Rhode Island"
								},
								{
									"code": "SC",
									"name": "South Carolina"
								},
								{
									"code": "SD",
									"name": "South Dakota"
								},
								{
									"code": "TN",
									"name": "Tennessee"
								},
								{
									"code": "TX",
									"name": "Texas"
								},
								{
									"code": "UT",
									"name": "Utah"
								},
								{
									"code": "VT",
									"name": "Vermont"
								},
								{
									"code": "VA",
									"name": "Virginia"
								},
								{
									"code": "WA",
									"name": "Washington"
								},
								{
									"code": "WV",
									"name": "West Virginia"
								},
								{
									"code": "WI",
									"name": "Wisconsin"
								},
								{
									"code": "WY",
									"name": "Wyoming"
								},
								{
									"code": "AA",
									"name": "Armed Forces (AA)"
								},
								{
									"code": "AE",
									"name": "Armed Forces (AE)"
								},
								{
									"code": "AP",
									"name": "Armed Forces (AP)"
								}
							]
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
							]
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
							"states": [{
									"code": 81,
									"name": "Baker Island"
								},
								{
									"code": 84,
									"name": "Howland Island"
								},
								{
									"code": 86,
									"name": "Jarvis Island"
								},
								{
									"code": 67,
									"name": "Johnston Atoll"
								},
								{
									"code": 89,
									"name": "Kingman Reef"
								},
								{
									"code": 71,
									"name": "Midway Atoll"
								},
								{
									"code": 76,
									"name": "Navassa Island"
								},
								{
									"code": 95,
									"name": "Palmyra Atoll"
								},
								{
									"code": 79,
									"name": "Wake Island"
								}
							]
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
							"states": [{
									"code": "C",
									"name": "Ciudad Autónoma de Buenos Aires"
								},
								{
									"code": "B",
									"name": "Buenos Aires"
								},
								{
									"code": "K",
									"name": "Catamarca"
								},
								{
									"code": "H",
									"name": "Chaco"
								},
								{
									"code": "U",
									"name": "Chubut"
								},
								{
									"code": "X",
									"name": "Córdoba"
								},
								{
									"code": "W",
									"name": "Corrientes"
								},
								{
									"code": "E",
									"name": "Entre Ríos"
								},
								{
									"code": "P",
									"name": "Formosa"
								},
								{
									"code": "Y",
									"name": "Jujuy"
								},
								{
									"code": "L",
									"name": "La Pampa"
								},
								{
									"code": "F",
									"name": "La Rioja"
								},
								{
									"code": "M",
									"name": "Mendoza"
								},
								{
									"code": "N",
									"name": "Misiones"
								},
								{
									"code": "Q",
									"name": "Neuquén"
								},
								{
									"code": "R",
									"name": "Río Negro"
								},
								{
									"code": "A",
									"name": "Salta"
								},
								{
									"code": "J",
									"name": "San Juan"
								},
								{
									"code": "D",
									"name": "San Luis"
								},
								{
									"code": "Z",
									"name": "Santa Cruz"
								},
								{
									"code": "S",
									"name": "Santa Fe"
								},
								{
									"code": "G",
									"name": "Santiago del Estero"
								},
								{
									"code": "V",
									"name": "Tierra del Fuego"
								},
								{
									"code": "T",
									"name": "Tucumán"
								}
							]
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
							"states": [{
									"code": "BO-B",
									"name": "Beni"
								},
								{
									"code": "BO-H",
									"name": "Chuquisaca"
								},
								{
									"code": "BO-C",
									"name": "Cochabamba"
								},
								{
									"code": "BO-L",
									"name": "La Paz"
								},
								{
									"code": "BO-O",
									"name": "Oruro"
								},
								{
									"code": "BO-N",
									"name": "Pando"
								},
								{
									"code": "BO-P",
									"name": "Potosí"
								},
								{
									"code": "BO-S",
									"name": "Santa Cruz"
								},
								{
									"code": "BO-T",
									"name": "Tarija"
								}
							]
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
							"states": [{
									"code": "AC",
									"name": "Acre"
								},
								{
									"code": "AL",
									"name": "Alagoas"
								},
								{
									"code": "AP",
									"name": "Amapá"
								},
								{
									"code": "AM",
									"name": "Amazonas"
								},
								{
									"code": "BA",
									"name": "Bahia"
								},
								{
									"code": "CE",
									"name": "Ceará"
								},
								{
									"code": "DF",
									"name": "Distrito Federal"
								},
								{
									"code": "ES",
									"name": "Espírito Santo"
								},
								{
									"code": "GO",
									"name": "Goiás"
								},
								{
									"code": "MA",
									"name": "Maranhão"
								},
								{
									"code": "MT",
									"name": "Mato Grosso"
								},
								{
									"code": "MS",
									"name": "Mato Grosso do Sul"
								},
								{
									"code": "MG",
									"name": "Minas Gerais"
								},
								{
									"code": "PA",
									"name": "Pará"
								},
								{
									"code": "PB",
									"name": "Paraíba"
								},
								{
									"code": "PR",
									"name": "Paraná"
								},
								{
									"code": "PE",
									"name": "Pernambuco"
								},
								{
									"code": "PI",
									"name": "Piauí"
								},
								{
									"code": "RJ",
									"name": "Rio de Janeiro"
								},
								{
									"code": "RN",
									"name": "Rio Grande do Norte"
								},
								{
									"code": "RS",
									"name": "Rio Grande do Sul"
								},
								{
									"code": "RO",
									"name": "Rondônia"
								},
								{
									"code": "RR",
									"name": "Roraima"
								},
								{
									"code": "SC",
									"name": "Santa Catarina"
								},
								{
									"code": "SP",
									"name": "São Paulo"
								},
								{
									"code": "SE",
									"name": "Sergipe"
								},
								{
									"code": "TO",
									"name": "Tocantins"
								}
							]
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
							"states": [{
									"code": "CL-AI",
									"name": "Aisén del General Carlos Ibañez del Campo"
								},
								{
									"code": "CL-AN",
									"name": "Antofagasta"
								},
								{
									"code": "CL-AP",
									"name": "Arica y Parinacota"
								},
								{
									"code": "CL-AR",
									"name": "La Araucanía"
								},
								{
									"code": "CL-AT",
									"name": "Atacama"
								},
								{
									"code": "CL-BI",
									"name": "Biobío"
								},
								{
									"code": "CL-CO",
									"name": "Coquimbo"
								},
								{
									"code": "CL-LI",
									"name": "Libertador General Bernardo O'Higgins"
								},
								{
									"code": "CL-LL",
									"name": "Los Lagos"
								},
								{
									"code": "CL-LR",
									"name": "Los Ríos"
								},
								{
									"code": "CL-MA",
									"name": "Magallanes"
								},
								{
									"code": "CL-ML",
									"name": "Maule"
								},
								{
									"code": "CL-NB",
									"name": "Ñuble"
								},
								{
									"code": "CL-RM",
									"name": "Región Metropolitana de Santiago"
								},
								{
									"code": "CL-TA",
									"name": "Tarapacá"
								},
								{
									"code": "CL-VS",
									"name": "Valparaíso"
								}
							]
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
							"states": [{
									"code": "CO-AMA",
									"name": "Amazonas"
								},
								{
									"code": "CO-ANT",
									"name": "Antioquia"
								},
								{
									"code": "CO-ARA",
									"name": "Arauca"
								},
								{
									"code": "CO-ATL",
									"name": "Atlántico"
								},
								{
									"code": "CO-BOL",
									"name": "Bolívar"
								},
								{
									"code": "CO-BOY",
									"name": "Boyacá"
								},
								{
									"code": "CO-CAL",
									"name": "Caldas"
								},
								{
									"code": "CO-CAQ",
									"name": "Caquetá"
								},
								{
									"code": "CO-CAS",
									"name": "Casanare"
								},
								{
									"code": "CO-CAU",
									"name": "Cauca"
								},
								{
									"code": "CO-CES",
									"name": "Cesar"
								},
								{
									"code": "CO-CHO",
									"name": "Chocó"
								},
								{
									"code": "CO-COR",
									"name": "Córdoba"
								},
								{
									"code": "CO-CUN",
									"name": "Cundinamarca"
								},
								{
									"code": "CO-DC",
									"name": "Capital District"
								},
								{
									"code": "CO-GUA",
									"name": "Guainía"
								},
								{
									"code": "CO-GUV",
									"name": "Guaviare"
								},
								{
									"code": "CO-HUI",
									"name": "Huila"
								},
								{
									"code": "CO-LAG",
									"name": "La Guajira"
								},
								{
									"code": "CO-MAG",
									"name": "Magdalena"
								},
								{
									"code": "CO-MET",
									"name": "Meta"
								},
								{
									"code": "CO-NAR",
									"name": "Nariño"
								},
								{
									"code": "CO-NSA",
									"name": "Norte de Santander"
								},
								{
									"code": "CO-PUT",
									"name": "Putumayo"
								},
								{
									"code": "CO-QUI",
									"name": "Quindío"
								},
								{
									"code": "CO-RIS",
									"name": "Risaralda"
								},
								{
									"code": "CO-SAN",
									"name": "Santander"
								},
								{
									"code": "CO-SAP",
									"name": "San Andrés & Providencia"
								},
								{
									"code": "CO-SUC",
									"name": "Sucre"
								},
								{
									"code": "CO-TOL",
									"name": "Tolima"
								},
								{
									"code": "CO-VAC",
									"name": "Valle del Cauca"
								},
								{
									"code": "CO-VAU",
									"name": "Vaupés"
								},
								{
									"code": "CO-VID",
									"name": "Vichada"
								}
							]
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
							"states": [{
									"code": "EC-A",
									"name": "Azuay"
								},
								{
									"code": "EC-B",
									"name": "Bolívar"
								},
								{
									"code": "EC-F",
									"name": "Cañar"
								},
								{
									"code": "EC-C",
									"name": "Carchi"
								},
								{
									"code": "EC-H",
									"name": "Chimborazo"
								},
								{
									"code": "EC-X",
									"name": "Cotopaxi"
								},
								{
									"code": "EC-O",
									"name": "El Oro"
								},
								{
									"code": "EC-E",
									"name": "Esmeraldas"
								},
								{
									"code": "EC-W",
									"name": "Galápagos"
								},
								{
									"code": "EC-G",
									"name": "Guayas"
								},
								{
									"code": "EC-I",
									"name": "Imbabura"
								},
								{
									"code": "EC-L",
									"name": "Loja"
								},
								{
									"code": "EC-R",
									"name": "Los Ríos"
								},
								{
									"code": "EC-M",
									"name": "Manabí"
								},
								{
									"code": "EC-S",
									"name": "Morona-Santiago"
								},
								{
									"code": "EC-N",
									"name": "Napo"
								},
								{
									"code": "EC-D",
									"name": "Orellana"
								},
								{
									"code": "EC-Y",
									"name": "Pastaza"
								},
								{
									"code": "EC-P",
									"name": "Pichincha"
								},
								{
									"code": "EC-SE",
									"name": "Santa Elena"
								},
								{
									"code": "EC-SD",
									"name": "Santo Domingo de los Tsáchilas"
								},
								{
									"code": "EC-U",
									"name": "Sucumbíos"
								},
								{
									"code": "EC-T",
									"name": "Tungurahua"
								},
								{
									"code": "EC-Z",
									"name": "Zamora-Chinchipe"
								}
							]
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
							"states": [{
									"code": "CAL",
									"name": "El Callao"
								},
								{
									"code": "LMA",
									"name": "Municipalidad Metropolitana de Lima"
								},
								{
									"code": "AMA",
									"name": "Amazonas"
								},
								{
									"code": "ANC",
									"name": "Ancash"
								},
								{
									"code": "APU",
									"name": "Apurímac"
								},
								{
									"code": "ARE",
									"name": "Arequipa"
								},
								{
									"code": "AYA",
									"name": "Ayacucho"
								},
								{
									"code": "CAJ",
									"name": "Cajamarca"
								},
								{
									"code": "CUS",
									"name": "Cusco"
								},
								{
									"code": "HUV",
									"name": "Huancavelica"
								},
								{
									"code": "HUC",
									"name": "Huánuco"
								},
								{
									"code": "ICA",
									"name": "Ica"
								},
								{
									"code": "JUN",
									"name": "Junín"
								},
								{
									"code": "LAL",
									"name": "La Libertad"
								},
								{
									"code": "LAM",
									"name": "Lambayeque"
								},
								{
									"code": "LIM",
									"name": "Lima"
								},
								{
									"code": "LOR",
									"name": "Loreto"
								},
								{
									"code": "MDD",
									"name": "Madre de Dios"
								},
								{
									"code": "MOQ",
									"name": "Moquegua"
								},
								{
									"code": "PAS",
									"name": "Pasco"
								},
								{
									"code": "PIU",
									"name": "Piura"
								},
								{
									"code": "PUN",
									"name": "Puno"
								},
								{
									"code": "SAM",
									"name": "San Martín"
								},
								{
									"code": "TAC",
									"name": "Tacna"
								},
								{
									"code": "TUM",
									"name": "Tumbes"
								},
								{
									"code": "UCA",
									"name": "Ucayali"
								}
							]
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
							"states": [{
									"code": "PY-ASU",
									"name": "Asunción"
								},
								{
									"code": "PY-1",
									"name": "Concepción"
								},
								{
									"code": "PY-2",
									"name": "San Pedro"
								},
								{
									"code": "PY-3",
									"name": "Cordillera"
								},
								{
									"code": "PY-4",
									"name": "Guairá"
								},
								{
									"code": "PY-5",
									"name": "Caaguazú"
								},
								{
									"code": "PY-6",
									"name": "Caazapá"
								},
								{
									"code": "PY-7",
									"name": "Itapúa"
								},
								{
									"code": "PY-8",
									"name": "Misiones"
								},
								{
									"code": "PY-9",
									"name": "Paraguarí"
								},
								{
									"code": "PY-10",
									"name": "Alto Paraná"
								},
								{
									"code": "PY-11",
									"name": "Central"
								},
								{
									"code": "PY-12",
									"name": "Ñeembucú"
								},
								{
									"code": "PY-13",
									"name": "Amambay"
								},
								{
									"code": "PY-14",
									"name": "Canindeyú"
								},
								{
									"code": "PY-15",
									"name": "Presidente Hayes"
								},
								{
									"code": "PY-16",
									"name": "Alto Paraguay"
								},
								{
									"code": "PY-17",
									"name": "Boquerón"
								}
							]
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
							"states": [{
									"code": "UY-AR",
									"name": "Artigas"
								},
								{
									"code": "UY-CA",
									"name": "Canelones"
								},
								{
									"code": "UY-CL",
									"name": "Cerro Largo"
								},
								{
									"code": "UY-CO",
									"name": "Colonia"
								},
								{
									"code": "UY-DU",
									"name": "Durazno"
								},
								{
									"code": "UY-FS",
									"name": "Flores"
								},
								{
									"code": "UY-FD",
									"name": "Florida"
								},
								{
									"code": "UY-LA",
									"name": "Lavalleja"
								},
								{
									"code": "UY-MA",
									"name": "Maldonado"
								},
								{
									"code": "UY-MO",
									"name": "Montevideo"
								},
								{
									"code": "UY-PA",
									"name": "Paysandú"
								},
								{
									"code": "UY-RN",
									"name": "Río Negro"
								},
								{
									"code": "UY-RV",
									"name": "Rivera"
								},
								{
									"code": "UY-RO",
									"name": "Rocha"
								},
								{
									"code": "UY-SA",
									"name": "Salto"
								},
								{
									"code": "UY-SJ",
									"name": "San José"
								},
								{
									"code": "UY-SO",
									"name": "Soriano"
								},
								{
									"code": "UY-TA",
									"name": "Tacuarembó"
								},
								{
									"code": "UY-TT",
									"name": "Treinta y Tres"
								}
							]
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
							"states": [{
									"code": "VE-A",
									"name": "Capital"
								},
								{
									"code": "VE-B",
									"name": "Anzoátegui"
								},
								{
									"code": "VE-C",
									"name": "Apure"
								},
								{
									"code": "VE-D",
									"name": "Aragua"
								},
								{
									"code": "VE-E",
									"name": "Barinas"
								},
								{
									"code": "VE-F",
									"name": "Bolívar"
								},
								{
									"code": "VE-G",
									"name": "Carabobo"
								},
								{
									"code": "VE-H",
									"name": "Cojedes"
								},
								{
									"code": "VE-I",
									"name": "Falcón"
								},
								{
									"code": "VE-J",
									"name": "Guárico"
								},
								{
									"code": "VE-K",
									"name": "Lara"
								},
								{
									"code": "VE-L",
									"name": "Mérida"
								},
								{
									"code": "VE-M",
									"name": "Miranda"
								},
								{
									"code": "VE-N",
									"name": "Monagas"
								},
								{
									"code": "VE-O",
									"name": "Nueva Esparta"
								},
								{
									"code": "VE-P",
									"name": "Portuguesa"
								},
								{
									"code": "VE-R",
									"name": "Sucre"
								},
								{
									"code": "VE-S",
									"name": "Táchira"
								},
								{
									"code": "VE-T",
									"name": "Trujillo"
								},
								{
									"code": "VE-U",
									"name": "Yaracuy"
								},
								{
									"code": "VE-V",
									"name": "Zulia"
								},
								{
									"code": "VE-W",
									"name": "Federal Dependencies"
								},
								{
									"code": "VE-X",
									"name": "La Guaira (Vargas)"
								},
								{
									"code": "VE-Y",
									"name": "Delta Amacuro"
								},
								{
									"code": "VE-Z",
									"name": "Amazonas"
								}
							]
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

		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AF",
				"name": "Afghanistan",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AX",
				"name": "Åland Islands",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AL",
				"name": "Albania",
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
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "DZ",
				"name": "Algeria",
				"states": [{
						"code": "DZ-01",
						"name": "Adrar"
					},
					{
						"code": "DZ-02",
						"name": "Chlef"
					},
					{
						"code": "DZ-03",
						"name": "Laghouat"
					},
					{
						"code": "DZ-04",
						"name": "Oum El Bouaghi"
					},
					{
						"code": "DZ-05",
						"name": "Batna"
					},
					{
						"code": "DZ-06",
						"name": "Béjaïa"
					},
					{
						"code": "DZ-07",
						"name": "Biskra"
					},
					{
						"code": "DZ-08",
						"name": "Béchar"
					},
					{
						"code": "DZ-09",
						"name": "Blida"
					},
					{
						"code": "DZ-10",
						"name": "Bouira"
					},
					{
						"code": "DZ-11",
						"name": "Tamanghasset"
					},
					{
						"code": "DZ-12",
						"name": "Tébessa"
					},
					{
						"code": "DZ-13",
						"name": "Tlemcen"
					},
					{
						"code": "DZ-14",
						"name": "Tiaret"
					},
					{
						"code": "DZ-15",
						"name": "Tizi Ouzou"
					},
					{
						"code": "DZ-16",
						"name": "Algiers"
					},
					{
						"code": "DZ-17",
						"name": "Djelfa"
					},
					{
						"code": "DZ-18",
						"name": "Jijel"
					},
					{
						"code": "DZ-19",
						"name": "Sétif"
					},
					{
						"code": "DZ-20",
						"name": "Saïda"
					},
					{
						"code": "DZ-21",
						"name": "Skikda"
					},
					{
						"code": "DZ-22",
						"name": "Sidi Bel Abbès"
					},
					{
						"code": "DZ-23",
						"name": "Annaba"
					},
					{
						"code": "DZ-24",
						"name": "Guelma"
					},
					{
						"code": "DZ-25",
						"name": "Constantine"
					},
					{
						"code": "DZ-26",
						"name": "Médéa"
					},
					{
						"code": "DZ-27",
						"name": "Mostaganem"
					},
					{
						"code": "DZ-28",
						"name": "M’Sila"
					},
					{
						"code": "DZ-29",
						"name": "Mascara"
					},
					{
						"code": "DZ-30",
						"name": "Ouargla"
					},
					{
						"code": "DZ-31",
						"name": "Oran"
					},
					{
						"code": "DZ-32",
						"name": "El Bayadh"
					},
					{
						"code": "DZ-33",
						"name": "Illizi"
					},
					{
						"code": "DZ-34",
						"name": "Bordj Bou Arréridj"
					},
					{
						"code": "DZ-35",
						"name": "Boumerdès"
					},
					{
						"code": "DZ-36",
						"name": "El Tarf"
					},
					{
						"code": "DZ-37",
						"name": "Tindouf"
					},
					{
						"code": "DZ-38",
						"name": "Tissemsilt"
					},
					{
						"code": "DZ-39",
						"name": "El Oued"
					},
					{
						"code": "DZ-40",
						"name": "Khenchela"
					},
					{
						"code": "DZ-41",
						"name": "Souk Ahras"
					},
					{
						"code": "DZ-42",
						"name": "Tipasa"
					},
					{
						"code": "DZ-43",
						"name": "Mila"
					},
					{
						"code": "DZ-44",
						"name": "Aïn Defla"
					},
					{
						"code": "DZ-45",
						"name": "Naama"
					},
					{
						"code": "DZ-46",
						"name": "Aïn Témouchent"
					},
					{
						"code": "DZ-47",
						"name": "Ghardaïa"
					},
					{
						"code": "DZ-48",
						"name": "Relizane"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AS",
				"name": "American Samoa",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AD",
				"name": "Andorra",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AO",
				"name": "Angola",
				"states": [{
						"code": "BGO",
						"name": "Bengo"
					},
					{
						"code": "BLU",
						"name": "Benguela"
					},
					{
						"code": "BIE",
						"name": "Bié"
					},
					{
						"code": "CAB",
						"name": "Cabinda"
					},
					{
						"code": "CNN",
						"name": "Cunene"
					},
					{
						"code": "HUA",
						"name": "Huambo"
					},
					{
						"code": "HUI",
						"name": "Huíla"
					},
					{
						"code": "CCU",
						"name": "Kuando Kubango"
					},
					{
						"code": "CNO",
						"name": "Kwanza-Norte"
					},
					{
						"code": "CUS",
						"name": "Kwanza-Sul"
					},
					{
						"code": "LUA",
						"name": "Luanda"
					},
					{
						"code": "LNO",
						"name": "Lunda-Norte"
					},
					{
						"code": "LSU",
						"name": "Lunda-Sul"
					},
					{
						"code": "MAL",
						"name": "Malanje"
					},
					{
						"code": "MOX",
						"name": "Moxico"
					},
					{
						"code": "NAM",
						"name": "Namibe"
					},
					{
						"code": "UIG",
						"name": "Uíge"
					},
					{
						"code": "ZAI",
						"name": "Zaire"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AI",
				"name": "Anguilla",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AQ",
				"name": "Antarctica",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AG",
				"name": "Antigua and Barbuda",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AR",
				"name": "Argentina",
				"states": [{
						"code": "C",
						"name": "Ciudad Autónoma de Buenos Aires"
					},
					{
						"code": "B",
						"name": "Buenos Aires"
					},
					{
						"code": "K",
						"name": "Catamarca"
					},
					{
						"code": "H",
						"name": "Chaco"
					},
					{
						"code": "U",
						"name": "Chubut"
					},
					{
						"code": "X",
						"name": "Córdoba"
					},
					{
						"code": "W",
						"name": "Corrientes"
					},
					{
						"code": "E",
						"name": "Entre Ríos"
					},
					{
						"code": "P",
						"name": "Formosa"
					},
					{
						"code": "Y",
						"name": "Jujuy"
					},
					{
						"code": "L",
						"name": "La Pampa"
					},
					{
						"code": "F",
						"name": "La Rioja"
					},
					{
						"code": "M",
						"name": "Mendoza"
					},
					{
						"code": "N",
						"name": "Misiones"
					},
					{
						"code": "Q",
						"name": "Neuquén"
					},
					{
						"code": "R",
						"name": "Río Negro"
					},
					{
						"code": "A",
						"name": "Salta"
					},
					{
						"code": "J",
						"name": "San Juan"
					},
					{
						"code": "D",
						"name": "San Luis"
					},
					{
						"code": "Z",
						"name": "Santa Cruz"
					},
					{
						"code": "S",
						"name": "Santa Fe"
					},
					{
						"code": "G",
						"name": "Santiago del Estero"
					},
					{
						"code": "V",
						"name": "Tierra del Fuego"
					},
					{
						"code": "T",
						"name": "Tucumán"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AM",
				"name": "Armenia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AW",
				"name": "Aruba",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
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
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AT",
				"name": "Austria",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AZ",
				"name": "Azerbaijan",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BS",
				"name": "Bahamas",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BH",
				"name": "Bahrain",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BD",
				"name": "Bangladesh",
				"states": [{
						"code": "BD-05",
						"name": "Bagerhat"
					},
					{
						"code": "BD-01",
						"name": "Bandarban"
					},
					{
						"code": "BD-02",
						"name": "Barguna"
					},
					{
						"code": "BD-06",
						"name": "Barishal"
					},
					{
						"code": "BD-07",
						"name": "Bhola"
					},
					{
						"code": "BD-03",
						"name": "Bogura"
					},
					{
						"code": "BD-04",
						"name": "Brahmanbaria"
					},
					{
						"code": "BD-09",
						"name": "Chandpur"
					},
					{
						"code": "BD-10",
						"name": "Chattogram"
					},
					{
						"code": "BD-12",
						"name": "Chuadanga"
					},
					{
						"code": "BD-11",
						"name": "Cox's Bazar"
					},
					{
						"code": "BD-08",
						"name": "Cumilla"
					},
					{
						"code": "BD-13",
						"name": "Dhaka"
					},
					{
						"code": "BD-14",
						"name": "Dinajpur"
					},
					{
						"code": "BD-15",
						"name": "Faridpur "
					},
					{
						"code": "BD-16",
						"name": "Feni"
					},
					{
						"code": "BD-19",
						"name": "Gaibandha"
					},
					{
						"code": "BD-18",
						"name": "Gazipur"
					},
					{
						"code": "BD-17",
						"name": "Gopalganj"
					},
					{
						"code": "BD-20",
						"name": "Habiganj"
					},
					{
						"code": "BD-21",
						"name": "Jamalpur"
					},
					{
						"code": "BD-22",
						"name": "Jashore"
					},
					{
						"code": "BD-25",
						"name": "Jhalokati"
					},
					{
						"code": "BD-23",
						"name": "Jhenaidah"
					},
					{
						"code": "BD-24",
						"name": "Joypurhat"
					},
					{
						"code": "BD-29",
						"name": "Khagrachhari"
					},
					{
						"code": "BD-27",
						"name": "Khulna"
					},
					{
						"code": "BD-26",
						"name": "Kishoreganj"
					},
					{
						"code": "BD-28",
						"name": "Kurigram"
					},
					{
						"code": "BD-30",
						"name": "Kushtia"
					},
					{
						"code": "BD-31",
						"name": "Lakshmipur"
					},
					{
						"code": "BD-32",
						"name": "Lalmonirhat"
					},
					{
						"code": "BD-36",
						"name": "Madaripur"
					},
					{
						"code": "BD-37",
						"name": "Magura"
					},
					{
						"code": "BD-33",
						"name": "Manikganj "
					},
					{
						"code": "BD-39",
						"name": "Meherpur"
					},
					{
						"code": "BD-38",
						"name": "Moulvibazar"
					},
					{
						"code": "BD-35",
						"name": "Munshiganj"
					},
					{
						"code": "BD-34",
						"name": "Mymensingh"
					},
					{
						"code": "BD-48",
						"name": "Naogaon"
					},
					{
						"code": "BD-43",
						"name": "Narail"
					},
					{
						"code": "BD-40",
						"name": "Narayanganj"
					},
					{
						"code": "BD-42",
						"name": "Narsingdi"
					},
					{
						"code": "BD-44",
						"name": "Natore"
					},
					{
						"code": "BD-45",
						"name": "Nawabganj"
					},
					{
						"code": "BD-41",
						"name": "Netrakona"
					},
					{
						"code": "BD-46",
						"name": "Nilphamari"
					},
					{
						"code": "BD-47",
						"name": "Noakhali"
					},
					{
						"code": "BD-49",
						"name": "Pabna"
					},
					{
						"code": "BD-52",
						"name": "Panchagarh"
					},
					{
						"code": "BD-51",
						"name": "Patuakhali"
					},
					{
						"code": "BD-50",
						"name": "Pirojpur"
					},
					{
						"code": "BD-53",
						"name": "Rajbari"
					},
					{
						"code": "BD-54",
						"name": "Rajshahi"
					},
					{
						"code": "BD-56",
						"name": "Rangamati"
					},
					{
						"code": "BD-55",
						"name": "Rangpur"
					},
					{
						"code": "BD-58",
						"name": "Satkhira"
					},
					{
						"code": "BD-62",
						"name": "Shariatpur"
					},
					{
						"code": "BD-57",
						"name": "Sherpur"
					},
					{
						"code": "BD-59",
						"name": "Sirajganj"
					},
					{
						"code": "BD-61",
						"name": "Sunamganj"
					},
					{
						"code": "BD-60",
						"name": "Sylhet"
					},
					{
						"code": "BD-63",
						"name": "Tangail"
					},
					{
						"code": "BD-64",
						"name": "Thakurgaon"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BB",
				"name": "Barbados",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BY",
				"name": "Belarus",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PW",
				"name": "Belau",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BE",
				"name": "Belgium",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BZ",
				"name": "Belize",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BJ",
				"name": "Benin",
				"states": [{
						"code": "AL",
						"name": "Alibori"
					},
					{
						"code": "AK",
						"name": "Atakora"
					},
					{
						"code": "AQ",
						"name": "Atlantique"
					},
					{
						"code": "BO",
						"name": "Borgou"
					},
					{
						"code": "CO",
						"name": "Collines"
					},
					{
						"code": "KO",
						"name": "Kouffo"
					},
					{
						"code": "DO",
						"name": "Donga"
					},
					{
						"code": "LI",
						"name": "Littoral"
					},
					{
						"code": "MO",
						"name": "Mono"
					},
					{
						"code": "OU",
						"name": "Ouémé"
					},
					{
						"code": "PL",
						"name": "Plateau"
					},
					{
						"code": "ZO",
						"name": "Zou"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BM",
				"name": "Bermuda",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BT",
				"name": "Bhutan",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BO",
				"name": "Bolivia",
				"states": [{
						"code": "BO-B",
						"name": "Beni"
					},
					{
						"code": "BO-H",
						"name": "Chuquisaca"
					},
					{
						"code": "BO-C",
						"name": "Cochabamba"
					},
					{
						"code": "BO-L",
						"name": "La Paz"
					},
					{
						"code": "BO-O",
						"name": "Oruro"
					},
					{
						"code": "BO-N",
						"name": "Pando"
					},
					{
						"code": "BO-P",
						"name": "Potosí"
					},
					{
						"code": "BO-S",
						"name": "Santa Cruz"
					},
					{
						"code": "BO-T",
						"name": "Tarija"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BQ",
				"name": "Bonaire, Saint Eustatius and Saba",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BA",
				"name": "Bosnia and Herzegovina",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BW",
				"name": "Botswana",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BV",
				"name": "Bouvet Island",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BR",
				"name": "Brazil",
				"states": [{
						"code": "AC",
						"name": "Acre"
					},
					{
						"code": "AL",
						"name": "Alagoas"
					},
					{
						"code": "AP",
						"name": "Amapá"
					},
					{
						"code": "AM",
						"name": "Amazonas"
					},
					{
						"code": "BA",
						"name": "Bahia"
					},
					{
						"code": "CE",
						"name": "Ceará"
					},
					{
						"code": "DF",
						"name": "Distrito Federal"
					},
					{
						"code": "ES",
						"name": "Espírito Santo"
					},
					{
						"code": "GO",
						"name": "Goiás"
					},
					{
						"code": "MA",
						"name": "Maranhão"
					},
					{
						"code": "MT",
						"name": "Mato Grosso"
					},
					{
						"code": "MS",
						"name": "Mato Grosso do Sul"
					},
					{
						"code": "MG",
						"name": "Minas Gerais"
					},
					{
						"code": "PA",
						"name": "Pará"
					},
					{
						"code": "PB",
						"name": "Paraíba"
					},
					{
						"code": "PR",
						"name": "Paraná"
					},
					{
						"code": "PE",
						"name": "Pernambuco"
					},
					{
						"code": "PI",
						"name": "Piauí"
					},
					{
						"code": "RJ",
						"name": "Rio de Janeiro"
					},
					{
						"code": "RN",
						"name": "Rio Grande do Norte"
					},
					{
						"code": "RS",
						"name": "Rio Grande do Sul"
					},
					{
						"code": "RO",
						"name": "Rondônia"
					},
					{
						"code": "RR",
						"name": "Roraima"
					},
					{
						"code": "SC",
						"name": "Santa Catarina"
					},
					{
						"code": "SP",
						"name": "São Paulo"
					},
					{
						"code": "SE",
						"name": "Sergipe"
					},
					{
						"code": "TO",
						"name": "Tocantins"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IO",
				"name": "British Indian Ocean Territory",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BN",
				"name": "Brunei",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BG",
				"name": "Bulgaria",
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
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BF",
				"name": "Burkina Faso",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BI",
				"name": "Burundi",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KH",
				"name": "Cambodia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CM",
				"name": "Cameroon",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CA",
				"name": "Canada",
				"states": [{
						"code": "AB",
						"name": "Alberta"
					},
					{
						"code": "BC",
						"name": "British Columbia"
					},
					{
						"code": "MB",
						"name": "Manitoba"
					},
					{
						"code": "NB",
						"name": "New Brunswick"
					},
					{
						"code": "NL",
						"name": "Newfoundland and Labrador"
					},
					{
						"code": "NT",
						"name": "Northwest Territories"
					},
					{
						"code": "NS",
						"name": "Nova Scotia"
					},
					{
						"code": "NU",
						"name": "Nunavut"
					},
					{
						"code": "ON",
						"name": "Ontario"
					},
					{
						"code": "PE",
						"name": "Prince Edward Island"
					},
					{
						"code": "QC",
						"name": "Quebec"
					},
					{
						"code": "SK",
						"name": "Saskatchewan"
					},
					{
						"code": "YT",
						"name": "Yukon Territory"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CV",
				"name": "Cape Verde",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KY",
				"name": "Cayman Islands",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CF",
				"name": "Central African Republic",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TD",
				"name": "Chad",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CL",
				"name": "Chile",
				"states": [{
						"code": "CL-AI",
						"name": "Aisén del General Carlos Ibañez del Campo"
					},
					{
						"code": "CL-AN",
						"name": "Antofagasta"
					},
					{
						"code": "CL-AP",
						"name": "Arica y Parinacota"
					},
					{
						"code": "CL-AR",
						"name": "La Araucanía"
					},
					{
						"code": "CL-AT",
						"name": "Atacama"
					},
					{
						"code": "CL-BI",
						"name": "Biobío"
					},
					{
						"code": "CL-CO",
						"name": "Coquimbo"
					},
					{
						"code": "CL-LI",
						"name": "Libertador General Bernardo O'Higgins"
					},
					{
						"code": "CL-LL",
						"name": "Los Lagos"
					},
					{
						"code": "CL-LR",
						"name": "Los Ríos"
					},
					{
						"code": "CL-MA",
						"name": "Magallanes"
					},
					{
						"code": "CL-ML",
						"name": "Maule"
					},
					{
						"code": "CL-NB",
						"name": "Ñuble"
					},
					{
						"code": "CL-RM",
						"name": "Región Metropolitana de Santiago"
					},
					{
						"code": "CL-TA",
						"name": "Tarapacá"
					},
					{
						"code": "CL-VS",
						"name": "Valparaíso"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CN",
				"name": "China",
				"states": [{
						"code": "CN1",
						"name": "Yunnan / 云南"
					},
					{
						"code": "CN2",
						"name": "Beijing / 北京"
					},
					{
						"code": "CN3",
						"name": "Tianjin / 天津"
					},
					{
						"code": "CN4",
						"name": "Hebei / 河北"
					},
					{
						"code": "CN5",
						"name": "Shanxi / 山西"
					},
					{
						"code": "CN6",
						"name": "Inner Mongolia / 內蒙古"
					},
					{
						"code": "CN7",
						"name": "Liaoning / 辽宁"
					},
					{
						"code": "CN8",
						"name": "Jilin / 吉林"
					},
					{
						"code": "CN9",
						"name": "Heilongjiang / 黑龙江"
					},
					{
						"code": "CN10",
						"name": "Shanghai / 上海"
					},
					{
						"code": "CN11",
						"name": "Jiangsu / 江苏"
					},
					{
						"code": "CN12",
						"name": "Zhejiang / 浙江"
					},
					{
						"code": "CN13",
						"name": "Anhui / 安徽"
					},
					{
						"code": "CN14",
						"name": "Fujian / 福建"
					},
					{
						"code": "CN15",
						"name": "Jiangxi / 江西"
					},
					{
						"code": "CN16",
						"name": "Shandong / 山东"
					},
					{
						"code": "CN17",
						"name": "Henan / 河南"
					},
					{
						"code": "CN18",
						"name": "Hubei / 湖北"
					},
					{
						"code": "CN19",
						"name": "Hunan / 湖南"
					},
					{
						"code": "CN20",
						"name": "Guangdong / 广东"
					},
					{
						"code": "CN21",
						"name": "Guangxi Zhuang / 广西壮族"
					},
					{
						"code": "CN22",
						"name": "Hainan / 海南"
					},
					{
						"code": "CN23",
						"name": "Chongqing / 重庆"
					},
					{
						"code": "CN24",
						"name": "Sichuan / 四川"
					},
					{
						"code": "CN25",
						"name": "Guizhou / 贵州"
					},
					{
						"code": "CN26",
						"name": "Shaanxi / 陕西"
					},
					{
						"code": "CN27",
						"name": "Gansu / 甘肃"
					},
					{
						"code": "CN28",
						"name": "Qinghai / 青海"
					},
					{
						"code": "CN29",
						"name": "Ningxia Hui / 宁夏"
					},
					{
						"code": "CN30",
						"name": "Macao / 澳门"
					},
					{
						"code": "CN31",
						"name": "Tibet / 西藏"
					},
					{
						"code": "CN32",
						"name": "Xinjiang / 新疆"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CX",
				"name": "Christmas Island",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CC",
				"name": "Cocos (Keeling) Islands",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CO",
				"name": "Colombia",
				"states": [{
						"code": "CO-AMA",
						"name": "Amazonas"
					},
					{
						"code": "CO-ANT",
						"name": "Antioquia"
					},
					{
						"code": "CO-ARA",
						"name": "Arauca"
					},
					{
						"code": "CO-ATL",
						"name": "Atlántico"
					},
					{
						"code": "CO-BOL",
						"name": "Bolívar"
					},
					{
						"code": "CO-BOY",
						"name": "Boyacá"
					},
					{
						"code": "CO-CAL",
						"name": "Caldas"
					},
					{
						"code": "CO-CAQ",
						"name": "Caquetá"
					},
					{
						"code": "CO-CAS",
						"name": "Casanare"
					},
					{
						"code": "CO-CAU",
						"name": "Cauca"
					},
					{
						"code": "CO-CES",
						"name": "Cesar"
					},
					{
						"code": "CO-CHO",
						"name": "Chocó"
					},
					{
						"code": "CO-COR",
						"name": "Córdoba"
					},
					{
						"code": "CO-CUN",
						"name": "Cundinamarca"
					},
					{
						"code": "CO-DC",
						"name": "Capital District"
					},
					{
						"code": "CO-GUA",
						"name": "Guainía"
					},
					{
						"code": "CO-GUV",
						"name": "Guaviare"
					},
					{
						"code": "CO-HUI",
						"name": "Huila"
					},
					{
						"code": "CO-LAG",
						"name": "La Guajira"
					},
					{
						"code": "CO-MAG",
						"name": "Magdalena"
					},
					{
						"code": "CO-MET",
						"name": "Meta"
					},
					{
						"code": "CO-NAR",
						"name": "Nariño"
					},
					{
						"code": "CO-NSA",
						"name": "Norte de Santander"
					},
					{
						"code": "CO-PUT",
						"name": "Putumayo"
					},
					{
						"code": "CO-QUI",
						"name": "Quindío"
					},
					{
						"code": "CO-RIS",
						"name": "Risaralda"
					},
					{
						"code": "CO-SAN",
						"name": "Santander"
					},
					{
						"code": "CO-SAP",
						"name": "San Andrés & Providencia"
					},
					{
						"code": "CO-SUC",
						"name": "Sucre"
					},
					{
						"code": "CO-TOL",
						"name": "Tolima"
					},
					{
						"code": "CO-VAC",
						"name": "Valle del Cauca"
					},
					{
						"code": "CO-VAU",
						"name": "Vaupés"
					},
					{
						"code": "CO-VID",
						"name": "Vichada"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KM",
				"name": "Comoros",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CG",
				"name": "Congo (Brazzaville)",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CD",
				"name": "Congo (Kinshasa)",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CK",
				"name": "Cook Islands",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CR",
				"name": "Costa Rica",
				"states": [{
						"code": "CR-A",
						"name": "Alajuela"
					},
					{
						"code": "CR-C",
						"name": "Cartago"
					},
					{
						"code": "CR-G",
						"name": "Guanacaste"
					},
					{
						"code": "CR-H",
						"name": "Heredia"
					},
					{
						"code": "CR-L",
						"name": "Limón"
					},
					{
						"code": "CR-P",
						"name": "Puntarenas"
					},
					{
						"code": "CR-SJ",
						"name": "San José"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "HR",
				"name": "Croatia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CU",
				"name": "Cuba",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CW",
				"name": "Cura&ccedil;ao",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CY",
				"name": "Cyprus",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CZ",
				"name": "Czech Republic",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "DK",
				"name": "Denmark",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "DJ",
				"name": "Djibouti",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "DM",
				"name": "Dominica",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "DO",
				"name": "Dominican Republic",
				"states": [{
						"code": "DO-01",
						"name": "Distrito Nacional"
					},
					{
						"code": "DO-02",
						"name": "Azua"
					},
					{
						"code": "DO-03",
						"name": "Baoruco"
					},
					{
						"code": "DO-04",
						"name": "Barahona"
					},
					{
						"code": "DO-33",
						"name": "Cibao Nordeste"
					},
					{
						"code": "DO-34",
						"name": "Cibao Noroeste"
					},
					{
						"code": "DO-35",
						"name": "Cibao Norte"
					},
					{
						"code": "DO-36",
						"name": "Cibao Sur"
					},
					{
						"code": "DO-05",
						"name": "Dajabón"
					},
					{
						"code": "DO-06",
						"name": "Duarte"
					},
					{
						"code": "DO-08",
						"name": "El Seibo"
					},
					{
						"code": "DO-37",
						"name": "El Valle"
					},
					{
						"code": "DO-07",
						"name": "Elías Piña"
					},
					{
						"code": "DO-38",
						"name": "Enriquillo"
					},
					{
						"code": "DO-09",
						"name": "Espaillat"
					},
					{
						"code": "DO-30",
						"name": "Hato Mayor"
					},
					{
						"code": "DO-19",
						"name": "Hermanas Mirabal"
					},
					{
						"code": "DO-39",
						"name": "Higüamo"
					},
					{
						"code": "DO-10",
						"name": "Independencia"
					},
					{
						"code": "DO-11",
						"name": "La Altagracia"
					},
					{
						"code": "DO-12",
						"name": "La Romana"
					},
					{
						"code": "DO-13",
						"name": "La Vega"
					},
					{
						"code": "DO-14",
						"name": "María Trinidad Sánchez"
					},
					{
						"code": "DO-28",
						"name": "Monseñor Nouel"
					},
					{
						"code": "DO-15",
						"name": "Monte Cristi"
					},
					{
						"code": "DO-29",
						"name": "Monte Plata"
					},
					{
						"code": "DO-40",
						"name": "Ozama"
					},
					{
						"code": "DO-16",
						"name": "Pedernales"
					},
					{
						"code": "DO-17",
						"name": "Peravia"
					},
					{
						"code": "DO-18",
						"name": "Puerto Plata"
					},
					{
						"code": "DO-20",
						"name": "Samaná"
					},
					{
						"code": "DO-21",
						"name": "San Cristóbal"
					},
					{
						"code": "DO-31",
						"name": "San José de Ocoa"
					},
					{
						"code": "DO-22",
						"name": "San Juan"
					},
					{
						"code": "DO-23",
						"name": "San Pedro de Macorís"
					},
					{
						"code": "DO-24",
						"name": "Sánchez Ramírez"
					},
					{
						"code": "DO-25",
						"name": "Santiago"
					},
					{
						"code": "DO-26",
						"name": "Santiago Rodríguez"
					},
					{
						"code": "DO-32",
						"name": "Santo Domingo"
					},
					{
						"code": "DO-41",
						"name": "Valdesia"
					},
					{
						"code": "DO-27",
						"name": "Valverde"
					},
					{
						"code": "DO-42",
						"name": "Yuma"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "EC",
				"name": "Ecuador",
				"states": [{
						"code": "EC-A",
						"name": "Azuay"
					},
					{
						"code": "EC-B",
						"name": "Bolívar"
					},
					{
						"code": "EC-F",
						"name": "Cañar"
					},
					{
						"code": "EC-C",
						"name": "Carchi"
					},
					{
						"code": "EC-H",
						"name": "Chimborazo"
					},
					{
						"code": "EC-X",
						"name": "Cotopaxi"
					},
					{
						"code": "EC-O",
						"name": "El Oro"
					},
					{
						"code": "EC-E",
						"name": "Esmeraldas"
					},
					{
						"code": "EC-W",
						"name": "Galápagos"
					},
					{
						"code": "EC-G",
						"name": "Guayas"
					},
					{
						"code": "EC-I",
						"name": "Imbabura"
					},
					{
						"code": "EC-L",
						"name": "Loja"
					},
					{
						"code": "EC-R",
						"name": "Los Ríos"
					},
					{
						"code": "EC-M",
						"name": "Manabí"
					},
					{
						"code": "EC-S",
						"name": "Morona-Santiago"
					},
					{
						"code": "EC-N",
						"name": "Napo"
					},
					{
						"code": "EC-D",
						"name": "Orellana"
					},
					{
						"code": "EC-Y",
						"name": "Pastaza"
					},
					{
						"code": "EC-P",
						"name": "Pichincha"
					},
					{
						"code": "EC-SE",
						"name": "Santa Elena"
					},
					{
						"code": "EC-SD",
						"name": "Santo Domingo de los Tsáchilas"
					},
					{
						"code": "EC-U",
						"name": "Sucumbíos"
					},
					{
						"code": "EC-T",
						"name": "Tungurahua"
					},
					{
						"code": "EC-Z",
						"name": "Zamora-Chinchipe"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "EG",
				"name": "Egypt",
				"states": [{
						"code": "EGALX",
						"name": "Alexandria"
					},
					{
						"code": "EGASN",
						"name": "Aswan"
					},
					{
						"code": "EGAST",
						"name": "Asyut"
					},
					{
						"code": "EGBA",
						"name": "Red Sea"
					},
					{
						"code": "EGBH",
						"name": "Beheira"
					},
					{
						"code": "EGBNS",
						"name": "Beni Suef"
					},
					{
						"code": "EGC",
						"name": "Cairo"
					},
					{
						"code": "EGDK",
						"name": "Dakahlia"
					},
					{
						"code": "EGDT",
						"name": "Damietta"
					},
					{
						"code": "EGFYM",
						"name": "Faiyum"
					},
					{
						"code": "EGGH",
						"name": "Gharbia"
					},
					{
						"code": "EGGZ",
						"name": "Giza"
					},
					{
						"code": "EGIS",
						"name": "Ismailia"
					},
					{
						"code": "EGJS",
						"name": "South Sinai"
					},
					{
						"code": "EGKB",
						"name": "Qalyubia"
					},
					{
						"code": "EGKFS",
						"name": "Kafr el-Sheikh"
					},
					{
						"code": "EGKN",
						"name": "Qena"
					},
					{
						"code": "EGLX",
						"name": "Luxor"
					},
					{
						"code": "EGMN",
						"name": "Minya"
					},
					{
						"code": "EGMNF",
						"name": "Monufia"
					},
					{
						"code": "EGMT",
						"name": "Matrouh"
					},
					{
						"code": "EGPTS",
						"name": "Port Said"
					},
					{
						"code": "EGSHG",
						"name": "Sohag"
					},
					{
						"code": "EGSHR",
						"name": "Al Sharqia"
					},
					{
						"code": "EGSIN",
						"name": "North Sinai"
					},
					{
						"code": "EGSUZ",
						"name": "Suez"
					},
					{
						"code": "EGWAD",
						"name": "New Valley"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SV",
				"name": "El Salvador",
				"states": [{
						"code": "SV-AH",
						"name": "Ahuachapán"
					},
					{
						"code": "SV-CA",
						"name": "Cabañas"
					},
					{
						"code": "SV-CH",
						"name": "Chalatenango"
					},
					{
						"code": "SV-CU",
						"name": "Cuscatlán"
					},
					{
						"code": "SV-LI",
						"name": "La Libertad"
					},
					{
						"code": "SV-MO",
						"name": "Morazán"
					},
					{
						"code": "SV-PA",
						"name": "La Paz"
					},
					{
						"code": "SV-SA",
						"name": "Santa Ana"
					},
					{
						"code": "SV-SM",
						"name": "San Miguel"
					},
					{
						"code": "SV-SO",
						"name": "Sonsonate"
					},
					{
						"code": "SV-SS",
						"name": "San Salvador"
					},
					{
						"code": "SV-SV",
						"name": "San Vicente"
					},
					{
						"code": "SV-UN",
						"name": "La Unión"
					},
					{
						"code": "SV-US",
						"name": "Usulután"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GQ",
				"name": "Equatorial Guinea",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ER",
				"name": "Eritrea",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "EE",
				"name": "Estonia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SZ",
				"name": "Eswatini",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ET",
				"name": "Ethiopia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "FK",
				"name": "Falkland Islands",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "FO",
				"name": "Faroe Islands",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "FJ",
				"name": "Fiji",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "FI",
				"name": "Finland",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "FR",
				"name": "France",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GF",
				"name": "French Guiana",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PF",
				"name": "French Polynesia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TF",
				"name": "French Southern Territories",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GA",
				"name": "Gabon",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GM",
				"name": "Gambia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GE",
				"name": "Georgia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "DE",
				"name": "Germany",
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
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GH",
				"name": "Ghana",
				"states": [{
						"code": "AF",
						"name": "Ahafo"
					},
					{
						"code": "AH",
						"name": "Ashanti"
					},
					{
						"code": "BA",
						"name": "Brong-Ahafo"
					},
					{
						"code": "BO",
						"name": "Bono"
					},
					{
						"code": "BE",
						"name": "Bono East"
					},
					{
						"code": "CP",
						"name": "Central"
					},
					{
						"code": "EP",
						"name": "Eastern"
					},
					{
						"code": "AA",
						"name": "Greater Accra"
					},
					{
						"code": "NE",
						"name": "North East"
					},
					{
						"code": "NP",
						"name": "Northern"
					},
					{
						"code": "OT",
						"name": "Oti"
					},
					{
						"code": "SV",
						"name": "Savannah"
					},
					{
						"code": "UE",
						"name": "Upper East"
					},
					{
						"code": "UW",
						"name": "Upper West"
					},
					{
						"code": "TV",
						"name": "Volta"
					},
					{
						"code": "WP",
						"name": "Western"
					},
					{
						"code": "WN",
						"name": "Western North"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GI",
				"name": "Gibraltar",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GR",
				"name": "Greece",
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
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GL",
				"name": "Greenland",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GD",
				"name": "Grenada",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GP",
				"name": "Guadeloupe",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GU",
				"name": "Guam",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GT",
				"name": "Guatemala",
				"states": [{
						"code": "GT-AV",
						"name": "Alta Verapaz"
					},
					{
						"code": "GT-BV",
						"name": "Baja Verapaz"
					},
					{
						"code": "GT-CM",
						"name": "Chimaltenango"
					},
					{
						"code": "GT-CQ",
						"name": "Chiquimula"
					},
					{
						"code": "GT-PR",
						"name": "El Progreso"
					},
					{
						"code": "GT-ES",
						"name": "Escuintla"
					},
					{
						"code": "GT-GU",
						"name": "Guatemala"
					},
					{
						"code": "GT-HU",
						"name": "Huehuetenango"
					},
					{
						"code": "GT-IZ",
						"name": "Izabal"
					},
					{
						"code": "GT-JA",
						"name": "Jalapa"
					},
					{
						"code": "GT-JU",
						"name": "Jutiapa"
					},
					{
						"code": "GT-PE",
						"name": "Petén"
					},
					{
						"code": "GT-QZ",
						"name": "Quetzaltenango"
					},
					{
						"code": "GT-QC",
						"name": "Quiché"
					},
					{
						"code": "GT-RE",
						"name": "Retalhuleu"
					},
					{
						"code": "GT-SA",
						"name": "Sacatepéquez"
					},
					{
						"code": "GT-SM",
						"name": "San Marcos"
					},
					{
						"code": "GT-SR",
						"name": "Santa Rosa"
					},
					{
						"code": "GT-SO",
						"name": "Sololá"
					},
					{
						"code": "GT-SU",
						"name": "Suchitepéquez"
					},
					{
						"code": "GT-TO",
						"name": "Totonicapán"
					},
					{
						"code": "GT-ZA",
						"name": "Zacapa"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GG",
				"name": "Guernsey",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GN",
				"name": "Guinea",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GW",
				"name": "Guinea-Bissau",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GY",
				"name": "Guyana",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "HT",
				"name": "Haiti",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "HM",
				"name": "Heard Island and McDonald Islands",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "HN",
				"name": "Honduras",
				"states": [{
						"code": "HN-AT",
						"name": "Atlántida"
					},
					{
						"code": "HN-IB",
						"name": "Bay Islands"
					},
					{
						"code": "HN-CH",
						"name": "Choluteca"
					},
					{
						"code": "HN-CL",
						"name": "Colón"
					},
					{
						"code": "HN-CM",
						"name": "Comayagua"
					},
					{
						"code": "HN-CP",
						"name": "Copán"
					},
					{
						"code": "HN-CR",
						"name": "Cortés"
					},
					{
						"code": "HN-EP",
						"name": "El Paraíso"
					},
					{
						"code": "HN-FM",
						"name": "Francisco Morazán"
					},
					{
						"code": "HN-GD",
						"name": "Gracias a Dios"
					},
					{
						"code": "HN-IN",
						"name": "Intibucá"
					},
					{
						"code": "HN-LE",
						"name": "Lempira"
					},
					{
						"code": "HN-LP",
						"name": "La Paz"
					},
					{
						"code": "HN-OC",
						"name": "Ocotepeque"
					},
					{
						"code": "HN-OL",
						"name": "Olancho"
					},
					{
						"code": "HN-SB",
						"name": "Santa Bárbara"
					},
					{
						"code": "HN-VA",
						"name": "Valle"
					},
					{
						"code": "HN-YO",
						"name": "Yoro"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "HK",
				"name": "Hong Kong",
				"states": [{
						"code": "HONG KONG",
						"name": "Hong Kong Island"
					},
					{
						"code": "KOWLOON",
						"name": "Kowloon"
					},
					{
						"code": "NEW TERRITORIES",
						"name": "New Territories"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "HU",
				"name": "Hungary",
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
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IS",
				"name": "Iceland",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IN",
				"name": "India",
				"states": [{
						"code": "AP",
						"name": "Andhra Pradesh"
					},
					{
						"code": "AR",
						"name": "Arunachal Pradesh"
					},
					{
						"code": "AS",
						"name": "Assam"
					},
					{
						"code": "BR",
						"name": "Bihar"
					},
					{
						"code": "CT",
						"name": "Chhattisgarh"
					},
					{
						"code": "GA",
						"name": "Goa"
					},
					{
						"code": "GJ",
						"name": "Gujarat"
					},
					{
						"code": "HR",
						"name": "Haryana"
					},
					{
						"code": "HP",
						"name": "Himachal Pradesh"
					},
					{
						"code": "JK",
						"name": "Jammu and Kashmir"
					},
					{
						"code": "JH",
						"name": "Jharkhand"
					},
					{
						"code": "KA",
						"name": "Karnataka"
					},
					{
						"code": "KL",
						"name": "Kerala"
					},
					{
						"code": "LA",
						"name": "Ladakh"
					},
					{
						"code": "MP",
						"name": "Madhya Pradesh"
					},
					{
						"code": "MH",
						"name": "Maharashtra"
					},
					{
						"code": "MN",
						"name": "Manipur"
					},
					{
						"code": "ML",
						"name": "Meghalaya"
					},
					{
						"code": "MZ",
						"name": "Mizoram"
					},
					{
						"code": "NL",
						"name": "Nagaland"
					},
					{
						"code": "OR",
						"name": "Odisha"
					},
					{
						"code": "PB",
						"name": "Punjab"
					},
					{
						"code": "RJ",
						"name": "Rajasthan"
					},
					{
						"code": "SK",
						"name": "Sikkim"
					},
					{
						"code": "TN",
						"name": "Tamil Nadu"
					},
					{
						"code": "TS",
						"name": "Telangana"
					},
					{
						"code": "TR",
						"name": "Tripura"
					},
					{
						"code": "UK",
						"name": "Uttarakhand"
					},
					{
						"code": "UP",
						"name": "Uttar Pradesh"
					},
					{
						"code": "WB",
						"name": "West Bengal"
					},
					{
						"code": "AN",
						"name": "Andaman and Nicobar Islands"
					},
					{
						"code": "CH",
						"name": "Chandigarh"
					},
					{
						"code": "DN",
						"name": "Dadra and Nagar Haveli"
					},
					{
						"code": "DD",
						"name": "Daman and Diu"
					},
					{
						"code": "DL",
						"name": "Delhi"
					},
					{
						"code": "LD",
						"name": "Lakshadeep"
					},
					{
						"code": "PY",
						"name": "Pondicherry (Puducherry)"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ID",
				"name": "Indonesia",
				"states": [{
						"code": "AC",
						"name": "Daerah Istimewa Aceh"
					},
					{
						"code": "SU",
						"name": "Sumatera Utara"
					},
					{
						"code": "SB",
						"name": "Sumatera Barat"
					},
					{
						"code": "RI",
						"name": "Riau"
					},
					{
						"code": "KR",
						"name": "Kepulauan Riau"
					},
					{
						"code": "JA",
						"name": "Jambi"
					},
					{
						"code": "SS",
						"name": "Sumatera Selatan"
					},
					{
						"code": "BB",
						"name": "Bangka Belitung"
					},
					{
						"code": "BE",
						"name": "Bengkulu"
					},
					{
						"code": "LA",
						"name": "Lampung"
					},
					{
						"code": "JK",
						"name": "DKI Jakarta"
					},
					{
						"code": "JB",
						"name": "Jawa Barat"
					},
					{
						"code": "BT",
						"name": "Banten"
					},
					{
						"code": "JT",
						"name": "Jawa Tengah"
					},
					{
						"code": "JI",
						"name": "Jawa Timur"
					},
					{
						"code": "YO",
						"name": "Daerah Istimewa Yogyakarta"
					},
					{
						"code": "BA",
						"name": "Bali"
					},
					{
						"code": "NB",
						"name": "Nusa Tenggara Barat"
					},
					{
						"code": "NT",
						"name": "Nusa Tenggara Timur"
					},
					{
						"code": "KB",
						"name": "Kalimantan Barat"
					},
					{
						"code": "KT",
						"name": "Kalimantan Tengah"
					},
					{
						"code": "KI",
						"name": "Kalimantan Timur"
					},
					{
						"code": "KS",
						"name": "Kalimantan Selatan"
					},
					{
						"code": "KU",
						"name": "Kalimantan Utara"
					},
					{
						"code": "SA",
						"name": "Sulawesi Utara"
					},
					{
						"code": "ST",
						"name": "Sulawesi Tengah"
					},
					{
						"code": "SG",
						"name": "Sulawesi Tenggara"
					},
					{
						"code": "SR",
						"name": "Sulawesi Barat"
					},
					{
						"code": "SN",
						"name": "Sulawesi Selatan"
					},
					{
						"code": "GO",
						"name": "Gorontalo"
					},
					{
						"code": "MA",
						"name": "Maluku"
					},
					{
						"code": "MU",
						"name": "Maluku Utara"
					},
					{
						"code": "PA",
						"name": "Papua"
					},
					{
						"code": "PB",
						"name": "Papua Barat"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IR",
				"name": "Iran",
				"states": [{
						"code": "KHZ",
						"name": "Khuzestan (خوزستان)"
					},
					{
						"code": "THR",
						"name": "Tehran (تهران)"
					},
					{
						"code": "ILM",
						"name": "Ilaam (ایلام)"
					},
					{
						"code": "BHR",
						"name": "Bushehr (بوشهر)"
					},
					{
						"code": "ADL",
						"name": "Ardabil (اردبیل)"
					},
					{
						"code": "ESF",
						"name": "Isfahan (اصفهان)"
					},
					{
						"code": "YZD",
						"name": "Yazd (یزد)"
					},
					{
						"code": "KRH",
						"name": "Kermanshah (کرمانشاه)"
					},
					{
						"code": "KRN",
						"name": "Kerman (کرمان)"
					},
					{
						"code": "HDN",
						"name": "Hamadan (همدان)"
					},
					{
						"code": "GZN",
						"name": "Ghazvin (قزوین)"
					},
					{
						"code": "ZJN",
						"name": "Zanjan (زنجان)"
					},
					{
						"code": "LRS",
						"name": "Luristan (لرستان)"
					},
					{
						"code": "ABZ",
						"name": "Alborz (البرز)"
					},
					{
						"code": "EAZ",
						"name": "East Azarbaijan (آذربایجان شرقی)"
					},
					{
						"code": "WAZ",
						"name": "West Azarbaijan (آذربایجان غربی)"
					},
					{
						"code": "CHB",
						"name": "Chaharmahal and Bakhtiari (چهارمحال و بختیاری)"
					},
					{
						"code": "SKH",
						"name": "South Khorasan (خراسان جنوبی)"
					},
					{
						"code": "RKH",
						"name": "Razavi Khorasan (خراسان رضوی)"
					},
					{
						"code": "NKH",
						"name": "North Khorasan (خراسان شمالی)"
					},
					{
						"code": "SMN",
						"name": "Semnan (سمنان)"
					},
					{
						"code": "FRS",
						"name": "Fars (فارس)"
					},
					{
						"code": "QHM",
						"name": "Qom (قم)"
					},
					{
						"code": "KRD",
						"name": "Kurdistan / کردستان)"
					},
					{
						"code": "KBD",
						"name": "Kohgiluyeh and BoyerAhmad (کهگیلوییه و بویراحمد)"
					},
					{
						"code": "GLS",
						"name": "Golestan (گلستان)"
					},
					{
						"code": "GIL",
						"name": "Gilan (گیلان)"
					},
					{
						"code": "MZN",
						"name": "Mazandaran (مازندران)"
					},
					{
						"code": "MKZ",
						"name": "Markazi (مرکزی)"
					},
					{
						"code": "HRZ",
						"name": "Hormozgan (هرمزگان)"
					},
					{
						"code": "SBN",
						"name": "Sistan and Baluchestan (سیستان و بلوچستان)"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IQ",
				"name": "Iraq",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IE",
				"name": "Ireland",
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
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IM",
				"name": "Isle of Man",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IL",
				"name": "Israel",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "IT",
				"name": "Italy",
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
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CI",
				"name": "Ivory Coast",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "JM",
				"name": "Jamaica",
				"states": [{
						"code": "JM-01",
						"name": "Kingston"
					},
					{
						"code": "JM-02",
						"name": "Saint Andrew"
					},
					{
						"code": "JM-03",
						"name": "Saint Thomas"
					},
					{
						"code": "JM-04",
						"name": "Portland"
					},
					{
						"code": "JM-05",
						"name": "Saint Mary"
					},
					{
						"code": "JM-06",
						"name": "Saint Ann"
					},
					{
						"code": "JM-07",
						"name": "Trelawny"
					},
					{
						"code": "JM-08",
						"name": "Saint James"
					},
					{
						"code": "JM-09",
						"name": "Hanover"
					},
					{
						"code": "JM-10",
						"name": "Westmoreland"
					},
					{
						"code": "JM-11",
						"name": "Saint Elizabeth"
					},
					{
						"code": "JM-12",
						"name": "Manchester"
					},
					{
						"code": "JM-13",
						"name": "Clarendon"
					},
					{
						"code": "JM-14",
						"name": "Saint Catherine"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "JP",
				"name": "Japan",
				"states": [{
						"code": "JP01",
						"name": "Hokkaido"
					},
					{
						"code": "JP02",
						"name": "Aomori"
					},
					{
						"code": "JP03",
						"name": "Iwate"
					},
					{
						"code": "JP04",
						"name": "Miyagi"
					},
					{
						"code": "JP05",
						"name": "Akita"
					},
					{
						"code": "JP06",
						"name": "Yamagata"
					},
					{
						"code": "JP07",
						"name": "Fukushima"
					},
					{
						"code": "JP08",
						"name": "Ibaraki"
					},
					{
						"code": "JP09",
						"name": "Tochigi"
					},
					{
						"code": "JP10",
						"name": "Gunma"
					},
					{
						"code": "JP11",
						"name": "Saitama"
					},
					{
						"code": "JP12",
						"name": "Chiba"
					},
					{
						"code": "JP13",
						"name": "Tokyo"
					},
					{
						"code": "JP14",
						"name": "Kanagawa"
					},
					{
						"code": "JP15",
						"name": "Niigata"
					},
					{
						"code": "JP16",
						"name": "Toyama"
					},
					{
						"code": "JP17",
						"name": "Ishikawa"
					},
					{
						"code": "JP18",
						"name": "Fukui"
					},
					{
						"code": "JP19",
						"name": "Yamanashi"
					},
					{
						"code": "JP20",
						"name": "Nagano"
					},
					{
						"code": "JP21",
						"name": "Gifu"
					},
					{
						"code": "JP22",
						"name": "Shizuoka"
					},
					{
						"code": "JP23",
						"name": "Aichi"
					},
					{
						"code": "JP24",
						"name": "Mie"
					},
					{
						"code": "JP25",
						"name": "Shiga"
					},
					{
						"code": "JP26",
						"name": "Kyoto"
					},
					{
						"code": "JP27",
						"name": "Osaka"
					},
					{
						"code": "JP28",
						"name": "Hyogo"
					},
					{
						"code": "JP29",
						"name": "Nara"
					},
					{
						"code": "JP30",
						"name": "Wakayama"
					},
					{
						"code": "JP31",
						"name": "Tottori"
					},
					{
						"code": "JP32",
						"name": "Shimane"
					},
					{
						"code": "JP33",
						"name": "Okayama"
					},
					{
						"code": "JP34",
						"name": "Hiroshima"
					},
					{
						"code": "JP35",
						"name": "Yamaguchi"
					},
					{
						"code": "JP36",
						"name": "Tokushima"
					},
					{
						"code": "JP37",
						"name": "Kagawa"
					},
					{
						"code": "JP38",
						"name": "Ehime"
					},
					{
						"code": "JP39",
						"name": "Kochi"
					},
					{
						"code": "JP40",
						"name": "Fukuoka"
					},
					{
						"code": "JP41",
						"name": "Saga"
					},
					{
						"code": "JP42",
						"name": "Nagasaki"
					},
					{
						"code": "JP43",
						"name": "Kumamoto"
					},
					{
						"code": "JP44",
						"name": "Oita"
					},
					{
						"code": "JP45",
						"name": "Miyazaki"
					},
					{
						"code": "JP46",
						"name": "Kagoshima"
					},
					{
						"code": "JP47",
						"name": "Okinawa"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "JE",
				"name": "Jersey",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "JO",
				"name": "Jordan",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KZ",
				"name": "Kazakhstan",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KE",
				"name": "Kenya",
				"states": [{
						"code": "KE01",
						"name": "Baringo"
					},
					{
						"code": "KE02",
						"name": "Bomet"
					},
					{
						"code": "KE03",
						"name": "Bungoma"
					},
					{
						"code": "KE04",
						"name": "Busia"
					},
					{
						"code": "KE05",
						"name": "Elgeyo-Marakwet"
					},
					{
						"code": "KE06",
						"name": "Embu"
					},
					{
						"code": "KE07",
						"name": "Garissa"
					},
					{
						"code": "KE08",
						"name": "Homa Bay"
					},
					{
						"code": "KE09",
						"name": "Isiolo"
					},
					{
						"code": "KE10",
						"name": "Kajiado"
					},
					{
						"code": "KE11",
						"name": "Kakamega"
					},
					{
						"code": "KE12",
						"name": "Kericho"
					},
					{
						"code": "KE13",
						"name": "Kiambu"
					},
					{
						"code": "KE14",
						"name": "Kilifi"
					},
					{
						"code": "KE15",
						"name": "Kirinyaga"
					},
					{
						"code": "KE16",
						"name": "Kisii"
					},
					{
						"code": "KE17",
						"name": "Kisumu"
					},
					{
						"code": "KE18",
						"name": "Kitui"
					},
					{
						"code": "KE19",
						"name": "Kwale"
					},
					{
						"code": "KE20",
						"name": "Laikipia"
					},
					{
						"code": "KE21",
						"name": "Lamu"
					},
					{
						"code": "KE22",
						"name": "Machakos"
					},
					{
						"code": "KE23",
						"name": "Makueni"
					},
					{
						"code": "KE24",
						"name": "Mandera"
					},
					{
						"code": "KE25",
						"name": "Marsabit"
					},
					{
						"code": "KE26",
						"name": "Meru"
					},
					{
						"code": "KE27",
						"name": "Migori"
					},
					{
						"code": "KE28",
						"name": "Mombasa"
					},
					{
						"code": "KE29",
						"name": "Murang’a"
					},
					{
						"code": "KE30",
						"name": "Nairobi County"
					},
					{
						"code": "KE31",
						"name": "Nakuru"
					},
					{
						"code": "KE32",
						"name": "Nandi"
					},
					{
						"code": "KE33",
						"name": "Narok"
					},
					{
						"code": "KE34",
						"name": "Nyamira"
					},
					{
						"code": "KE35",
						"name": "Nyandarua"
					},
					{
						"code": "KE36",
						"name": "Nyeri"
					},
					{
						"code": "KE37",
						"name": "Samburu"
					},
					{
						"code": "KE38",
						"name": "Siaya"
					},
					{
						"code": "KE39",
						"name": "Taita-Taveta"
					},
					{
						"code": "KE40",
						"name": "Tana River"
					},
					{
						"code": "KE41",
						"name": "Tharaka-Nithi"
					},
					{
						"code": "KE42",
						"name": "Trans Nzoia"
					},
					{
						"code": "KE43",
						"name": "Turkana"
					},
					{
						"code": "KE44",
						"name": "Uasin Gishu"
					},
					{
						"code": "KE45",
						"name": "Vihiga"
					},
					{
						"code": "KE46",
						"name": "Wajir"
					},
					{
						"code": "KE47",
						"name": "West Pokot"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KI",
				"name": "Kiribati",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KW",
				"name": "Kuwait",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KG",
				"name": "Kyrgyzstan",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LA",
				"name": "Laos",
				"states": [{
						"code": "AT",
						"name": "Attapeu"
					},
					{
						"code": "BK",
						"name": "Bokeo"
					},
					{
						"code": "BL",
						"name": "Bolikhamsai"
					},
					{
						"code": "CH",
						"name": "Champasak"
					},
					{
						"code": "HO",
						"name": "Houaphanh"
					},
					{
						"code": "KH",
						"name": "Khammouane"
					},
					{
						"code": "LM",
						"name": "Luang Namtha"
					},
					{
						"code": "LP",
						"name": "Luang Prabang"
					},
					{
						"code": "OU",
						"name": "Oudomxay"
					},
					{
						"code": "PH",
						"name": "Phongsaly"
					},
					{
						"code": "SL",
						"name": "Salavan"
					},
					{
						"code": "SV",
						"name": "Savannakhet"
					},
					{
						"code": "VI",
						"name": "Vientiane Province"
					},
					{
						"code": "VT",
						"name": "Vientiane"
					},
					{
						"code": "XA",
						"name": "Sainyabuli"
					},
					{
						"code": "XE",
						"name": "Sekong"
					},
					{
						"code": "XI",
						"name": "Xiangkhouang"
					},
					{
						"code": "XS",
						"name": "Xaisomboun"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LV",
				"name": "Latvia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LB",
				"name": "Lebanon",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LS",
				"name": "Lesotho",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LR",
				"name": "Liberia",
				"states": [{
						"code": "BM",
						"name": "Bomi"
					},
					{
						"code": "BN",
						"name": "Bong"
					},
					{
						"code": "GA",
						"name": "Gbarpolu"
					},
					{
						"code": "GB",
						"name": "Grand Bassa"
					},
					{
						"code": "GC",
						"name": "Grand Cape Mount"
					},
					{
						"code": "GG",
						"name": "Grand Gedeh"
					},
					{
						"code": "GK",
						"name": "Grand Kru"
					},
					{
						"code": "LO",
						"name": "Lofa"
					},
					{
						"code": "MA",
						"name": "Margibi"
					},
					{
						"code": "MY",
						"name": "Maryland"
					},
					{
						"code": "MO",
						"name": "Montserrado"
					},
					{
						"code": "NM",
						"name": "Nimba"
					},
					{
						"code": "RV",
						"name": "Rivercess"
					},
					{
						"code": "RG",
						"name": "River Gee"
					},
					{
						"code": "SN",
						"name": "Sinoe"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LY",
				"name": "Libya",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LI",
				"name": "Liechtenstein",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LT",
				"name": "Lithuania",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LU",
				"name": "Luxembourg",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MO",
				"name": "Macao",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MG",
				"name": "Madagascar",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MW",
				"name": "Malawi",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MY",
				"name": "Malaysia",
				"states": [{
						"code": "JHR",
						"name": "Johor"
					},
					{
						"code": "KDH",
						"name": "Kedah"
					},
					{
						"code": "KTN",
						"name": "Kelantan"
					},
					{
						"code": "LBN",
						"name": "Labuan"
					},
					{
						"code": "MLK",
						"name": "Malacca (Melaka)"
					},
					{
						"code": "NSN",
						"name": "Negeri Sembilan"
					},
					{
						"code": "PHG",
						"name": "Pahang"
					},
					{
						"code": "PNG",
						"name": "Penang (Pulau Pinang)"
					},
					{
						"code": "PRK",
						"name": "Perak"
					},
					{
						"code": "PLS",
						"name": "Perlis"
					},
					{
						"code": "SBH",
						"name": "Sabah"
					},
					{
						"code": "SWK",
						"name": "Sarawak"
					},
					{
						"code": "SGR",
						"name": "Selangor"
					},
					{
						"code": "TRG",
						"name": "Terengganu"
					},
					{
						"code": "PJY",
						"name": "Putrajaya"
					},
					{
						"code": "KUL",
						"name": "Kuala Lumpur"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MV",
				"name": "Maldives",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ML",
				"name": "Mali",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MT",
				"name": "Malta",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MH",
				"name": "Marshall Islands",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MQ",
				"name": "Martinique",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MR",
				"name": "Mauritania",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MU",
				"name": "Mauritius",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "YT",
				"name": "Mayotte",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MX",
				"name": "Mexico",
				"states": [{
						"code": "DF",
						"name": "Ciudad de México"
					},
					{
						"code": "JA",
						"name": "Jalisco"
					},
					{
						"code": "NL",
						"name": "Nuevo León"
					},
					{
						"code": "AG",
						"name": "Aguascalientes"
					},
					{
						"code": "BC",
						"name": "Baja California"
					},
					{
						"code": "BS",
						"name": "Baja California Sur"
					},
					{
						"code": "CM",
						"name": "Campeche"
					},
					{
						"code": "CS",
						"name": "Chiapas"
					},
					{
						"code": "CH",
						"name": "Chihuahua"
					},
					{
						"code": "CO",
						"name": "Coahuila"
					},
					{
						"code": "CL",
						"name": "Colima"
					},
					{
						"code": "DG",
						"name": "Durango"
					},
					{
						"code": "GT",
						"name": "Guanajuato"
					},
					{
						"code": "GR",
						"name": "Guerrero"
					},
					{
						"code": "HG",
						"name": "Hidalgo"
					},
					{
						"code": "MX",
						"name": "Estado de México"
					},
					{
						"code": "MI",
						"name": "Michoacán"
					},
					{
						"code": "MO",
						"name": "Morelos"
					},
					{
						"code": "NA",
						"name": "Nayarit"
					},
					{
						"code": "OA",
						"name": "Oaxaca"
					},
					{
						"code": "PU",
						"name": "Puebla"
					},
					{
						"code": "QT",
						"name": "Querétaro"
					},
					{
						"code": "QR",
						"name": "Quintana Roo"
					},
					{
						"code": "SL",
						"name": "San Luis Potosí"
					},
					{
						"code": "SI",
						"name": "Sinaloa"
					},
					{
						"code": "SO",
						"name": "Sonora"
					},
					{
						"code": "TB",
						"name": "Tabasco"
					},
					{
						"code": "TM",
						"name": "Tamaulipas"
					},
					{
						"code": "TL",
						"name": "Tlaxcala"
					},
					{
						"code": "VE",
						"name": "Veracruz"
					},
					{
						"code": "YU",
						"name": "Yucatán"
					},
					{
						"code": "ZA",
						"name": "Zacatecas"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "FM",
				"name": "Micronesia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MD",
				"name": "Moldova",
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
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MC",
				"name": "Monaco",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MN",
				"name": "Mongolia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ME",
				"name": "Montenegro",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MS",
				"name": "Montserrat",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MA",
				"name": "Morocco",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MZ",
				"name": "Mozambique",
				"states": [{
						"code": "MZP",
						"name": "Cabo Delgado"
					},
					{
						"code": "MZG",
						"name": "Gaza"
					},
					{
						"code": "MZI",
						"name": "Inhambane"
					},
					{
						"code": "MZB",
						"name": "Manica"
					},
					{
						"code": "MZL",
						"name": "Maputo Province"
					},
					{
						"code": "MZMPM",
						"name": "Maputo"
					},
					{
						"code": "MZN",
						"name": "Nampula"
					},
					{
						"code": "MZA",
						"name": "Niassa"
					},
					{
						"code": "MZS",
						"name": "Sofala"
					},
					{
						"code": "MZT",
						"name": "Tete"
					},
					{
						"code": "MZQ",
						"name": "Zambézia"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MM",
				"name": "Myanmar",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NA",
				"name": "Namibia",
				"states": [{
						"code": "ER",
						"name": "Erongo"
					},
					{
						"code": "HA",
						"name": "Hardap"
					},
					{
						"code": "KA",
						"name": "Karas"
					},
					{
						"code": "KE",
						"name": "Kavango East"
					},
					{
						"code": "KW",
						"name": "Kavango West"
					},
					{
						"code": "KH",
						"name": "Khomas"
					},
					{
						"code": "KU",
						"name": "Kunene"
					},
					{
						"code": "OW",
						"name": "Ohangwena"
					},
					{
						"code": "OH",
						"name": "Omaheke"
					},
					{
						"code": "OS",
						"name": "Omusati"
					},
					{
						"code": "ON",
						"name": "Oshana"
					},
					{
						"code": "OT",
						"name": "Oshikoto"
					},
					{
						"code": "OD",
						"name": "Otjozondjupa"
					},
					{
						"code": "CA",
						"name": "Zambezi"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NR",
				"name": "Nauru",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NP",
				"name": "Nepal",
				"states": [{
						"code": "BAG",
						"name": "Bagmati"
					},
					{
						"code": "BHE",
						"name": "Bheri"
					},
					{
						"code": "DHA",
						"name": "Dhaulagiri"
					},
					{
						"code": "GAN",
						"name": "Gandaki"
					},
					{
						"code": "JAN",
						"name": "Janakpur"
					},
					{
						"code": "KAR",
						"name": "Karnali"
					},
					{
						"code": "KOS",
						"name": "Koshi"
					},
					{
						"code": "LUM",
						"name": "Lumbini"
					},
					{
						"code": "MAH",
						"name": "Mahakali"
					},
					{
						"code": "MEC",
						"name": "Mechi"
					},
					{
						"code": "NAR",
						"name": "Narayani"
					},
					{
						"code": "RAP",
						"name": "Rapti"
					},
					{
						"code": "SAG",
						"name": "Sagarmatha"
					},
					{
						"code": "SET",
						"name": "Seti"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NL",
				"name": "Netherlands",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NC",
				"name": "New Caledonia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NZ",
				"name": "New Zealand",
				"states": expect.arrayContaining([]),
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NI",
				"name": "Nicaragua",
				"states": [{
						"code": "NI-AN",
						"name": "Atlántico Norte"
					},
					{
						"code": "NI-AS",
						"name": "Atlántico Sur"
					},
					{
						"code": "NI-BO",
						"name": "Boaco"
					},
					{
						"code": "NI-CA",
						"name": "Carazo"
					},
					{
						"code": "NI-CI",
						"name": "Chinandega"
					},
					{
						"code": "NI-CO",
						"name": "Chontales"
					},
					{
						"code": "NI-ES",
						"name": "Estelí"
					},
					{
						"code": "NI-GR",
						"name": "Granada"
					},
					{
						"code": "NI-JI",
						"name": "Jinotega"
					},
					{
						"code": "NI-LE",
						"name": "León"
					},
					{
						"code": "NI-MD",
						"name": "Madriz"
					},
					{
						"code": "NI-MN",
						"name": "Managua"
					},
					{
						"code": "NI-MS",
						"name": "Masaya"
					},
					{
						"code": "NI-MT",
						"name": "Matagalpa"
					},
					{
						"code": "NI-NS",
						"name": "Nueva Segovia"
					},
					{
						"code": "NI-RI",
						"name": "Rivas"
					},
					{
						"code": "NI-SJ",
						"name": "Río San Juan"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NE",
				"name": "Niger",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NG",
				"name": "Nigeria",
				"states": [{
						"code": "AB",
						"name": "Abia"
					},
					{
						"code": "FC",
						"name": "Abuja"
					},
					{
						"code": "AD",
						"name": "Adamawa"
					},
					{
						"code": "AK",
						"name": "Akwa Ibom"
					},
					{
						"code": "AN",
						"name": "Anambra"
					},
					{
						"code": "BA",
						"name": "Bauchi"
					},
					{
						"code": "BY",
						"name": "Bayelsa"
					},
					{
						"code": "BE",
						"name": "Benue"
					},
					{
						"code": "BO",
						"name": "Borno"
					},
					{
						"code": "CR",
						"name": "Cross River"
					},
					{
						"code": "DE",
						"name": "Delta"
					},
					{
						"code": "EB",
						"name": "Ebonyi"
					},
					{
						"code": "ED",
						"name": "Edo"
					},
					{
						"code": "EK",
						"name": "Ekiti"
					},
					{
						"code": "EN",
						"name": "Enugu"
					},
					{
						"code": "GO",
						"name": "Gombe"
					},
					{
						"code": "IM",
						"name": "Imo"
					},
					{
						"code": "JI",
						"name": "Jigawa"
					},
					{
						"code": "KD",
						"name": "Kaduna"
					},
					{
						"code": "KN",
						"name": "Kano"
					},
					{
						"code": "KT",
						"name": "Katsina"
					},
					{
						"code": "KE",
						"name": "Kebbi"
					},
					{
						"code": "KO",
						"name": "Kogi"
					},
					{
						"code": "KW",
						"name": "Kwara"
					},
					{
						"code": "LA",
						"name": "Lagos"
					},
					{
						"code": "NA",
						"name": "Nasarawa"
					},
					{
						"code": "NI",
						"name": "Niger"
					},
					{
						"code": "OG",
						"name": "Ogun"
					},
					{
						"code": "ON",
						"name": "Ondo"
					},
					{
						"code": "OS",
						"name": "Osun"
					},
					{
						"code": "OY",
						"name": "Oyo"
					},
					{
						"code": "PL",
						"name": "Plateau"
					},
					{
						"code": "RI",
						"name": "Rivers"
					},
					{
						"code": "SO",
						"name": "Sokoto"
					},
					{
						"code": "TA",
						"name": "Taraba"
					},
					{
						"code": "YO",
						"name": "Yobe"
					},
					{
						"code": "ZA",
						"name": "Zamfara"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NU",
				"name": "Niue",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NF",
				"name": "Norfolk Island",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KP",
				"name": "North Korea",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MK",
				"name": "North Macedonia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MP",
				"name": "Northern Mariana Islands",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "NO",
				"name": "Norway",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "OM",
				"name": "Oman",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PK",
				"name": "Pakistan",
				"states": [{
						"code": "JK",
						"name": "Azad Kashmir"
					},
					{
						"code": "BA",
						"name": "Balochistan"
					},
					{
						"code": "TA",
						"name": "FATA"
					},
					{
						"code": "GB",
						"name": "Gilgit Baltistan"
					},
					{
						"code": "IS",
						"name": "Islamabad Capital Territory"
					},
					{
						"code": "KP",
						"name": "Khyber Pakhtunkhwa"
					},
					{
						"code": "PB",
						"name": "Punjab"
					},
					{
						"code": "SD",
						"name": "Sindh"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PS",
				"name": "Palestinian Territory",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PA",
				"name": "Panama",
				"states": [{
						"code": "PA-1",
						"name": "Bocas del Toro"
					},
					{
						"code": "PA-2",
						"name": "Coclé"
					},
					{
						"code": "PA-3",
						"name": "Colón"
					},
					{
						"code": "PA-4",
						"name": "Chiriquí"
					},
					{
						"code": "PA-5",
						"name": "Darién"
					},
					{
						"code": "PA-6",
						"name": "Herrera"
					},
					{
						"code": "PA-7",
						"name": "Los Santos"
					},
					{
						"code": "PA-8",
						"name": "Panamá"
					},
					{
						"code": "PA-9",
						"name": "Veraguas"
					},
					{
						"code": "PA-10",
						"name": "West Panamá"
					},
					{
						"code": "PA-EM",
						"name": "Emberá"
					},
					{
						"code": "PA-KY",
						"name": "Guna Yala"
					},
					{
						"code": "PA-NB",
						"name": "Ngöbe-Buglé"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PG",
				"name": "Papua New Guinea",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PY",
				"name": "Paraguay",
				"states": [{
						"code": "PY-ASU",
						"name": "Asunción"
					},
					{
						"code": "PY-1",
						"name": "Concepción"
					},
					{
						"code": "PY-2",
						"name": "San Pedro"
					},
					{
						"code": "PY-3",
						"name": "Cordillera"
					},
					{
						"code": "PY-4",
						"name": "Guairá"
					},
					{
						"code": "PY-5",
						"name": "Caaguazú"
					},
					{
						"code": "PY-6",
						"name": "Caazapá"
					},
					{
						"code": "PY-7",
						"name": "Itapúa"
					},
					{
						"code": "PY-8",
						"name": "Misiones"
					},
					{
						"code": "PY-9",
						"name": "Paraguarí"
					},
					{
						"code": "PY-10",
						"name": "Alto Paraná"
					},
					{
						"code": "PY-11",
						"name": "Central"
					},
					{
						"code": "PY-12",
						"name": "Ñeembucú"
					},
					{
						"code": "PY-13",
						"name": "Amambay"
					},
					{
						"code": "PY-14",
						"name": "Canindeyú"
					},
					{
						"code": "PY-15",
						"name": "Presidente Hayes"
					},
					{
						"code": "PY-16",
						"name": "Alto Paraguay"
					},
					{
						"code": "PY-17",
						"name": "Boquerón"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PE",
				"name": "Peru",
				"states": [{
						"code": "CAL",
						"name": "El Callao"
					},
					{
						"code": "LMA",
						"name": "Municipalidad Metropolitana de Lima"
					},
					{
						"code": "AMA",
						"name": "Amazonas"
					},
					{
						"code": "ANC",
						"name": "Ancash"
					},
					{
						"code": "APU",
						"name": "Apurímac"
					},
					{
						"code": "ARE",
						"name": "Arequipa"
					},
					{
						"code": "AYA",
						"name": "Ayacucho"
					},
					{
						"code": "CAJ",
						"name": "Cajamarca"
					},
					{
						"code": "CUS",
						"name": "Cusco"
					},
					{
						"code": "HUV",
						"name": "Huancavelica"
					},
					{
						"code": "HUC",
						"name": "Huánuco"
					},
					{
						"code": "ICA",
						"name": "Ica"
					},
					{
						"code": "JUN",
						"name": "Junín"
					},
					{
						"code": "LAL",
						"name": "La Libertad"
					},
					{
						"code": "LAM",
						"name": "Lambayeque"
					},
					{
						"code": "LIM",
						"name": "Lima"
					},
					{
						"code": "LOR",
						"name": "Loreto"
					},
					{
						"code": "MDD",
						"name": "Madre de Dios"
					},
					{
						"code": "MOQ",
						"name": "Moquegua"
					},
					{
						"code": "PAS",
						"name": "Pasco"
					},
					{
						"code": "PIU",
						"name": "Piura"
					},
					{
						"code": "PUN",
						"name": "Puno"
					},
					{
						"code": "SAM",
						"name": "San Martín"
					},
					{
						"code": "TAC",
						"name": "Tacna"
					},
					{
						"code": "TUM",
						"name": "Tumbes"
					},
					{
						"code": "UCA",
						"name": "Ucayali"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PH",
				"name": "Philippines",
				"states": [{
						"code": "ABR",
						"name": "Abra"
					},
					{
						"code": "AGN",
						"name": "Agusan del Norte"
					},
					{
						"code": "AGS",
						"name": "Agusan del Sur"
					},
					{
						"code": "AKL",
						"name": "Aklan"
					},
					{
						"code": "ALB",
						"name": "Albay"
					},
					{
						"code": "ANT",
						"name": "Antique"
					},
					{
						"code": "APA",
						"name": "Apayao"
					},
					{
						"code": "AUR",
						"name": "Aurora"
					},
					{
						"code": "BAS",
						"name": "Basilan"
					},
					{
						"code": "BAN",
						"name": "Bataan"
					},
					{
						"code": "BTN",
						"name": "Batanes"
					},
					{
						"code": "BTG",
						"name": "Batangas"
					},
					{
						"code": "BEN",
						"name": "Benguet"
					},
					{
						"code": "BIL",
						"name": "Biliran"
					},
					{
						"code": "BOH",
						"name": "Bohol"
					},
					{
						"code": "BUK",
						"name": "Bukidnon"
					},
					{
						"code": "BUL",
						"name": "Bulacan"
					},
					{
						"code": "CAG",
						"name": "Cagayan"
					},
					{
						"code": "CAN",
						"name": "Camarines Norte"
					},
					{
						"code": "CAS",
						"name": "Camarines Sur"
					},
					{
						"code": "CAM",
						"name": "Camiguin"
					},
					{
						"code": "CAP",
						"name": "Capiz"
					},
					{
						"code": "CAT",
						"name": "Catanduanes"
					},
					{
						"code": "CAV",
						"name": "Cavite"
					},
					{
						"code": "CEB",
						"name": "Cebu"
					},
					{
						"code": "COM",
						"name": "Compostela Valley"
					},
					{
						"code": "NCO",
						"name": "Cotabato"
					},
					{
						"code": "DAV",
						"name": "Davao del Norte"
					},
					{
						"code": "DAS",
						"name": "Davao del Sur"
					},
					{
						"code": "DAC",
						"name": "Davao Occidental"
					},
					{
						"code": "DAO",
						"name": "Davao Oriental"
					},
					{
						"code": "DIN",
						"name": "Dinagat Islands"
					},
					{
						"code": "EAS",
						"name": "Eastern Samar"
					},
					{
						"code": "GUI",
						"name": "Guimaras"
					},
					{
						"code": "IFU",
						"name": "Ifugao"
					},
					{
						"code": "ILN",
						"name": "Ilocos Norte"
					},
					{
						"code": "ILS",
						"name": "Ilocos Sur"
					},
					{
						"code": "ILI",
						"name": "Iloilo"
					},
					{
						"code": "ISA",
						"name": "Isabela"
					},
					{
						"code": "KAL",
						"name": "Kalinga"
					},
					{
						"code": "LUN",
						"name": "La Union"
					},
					{
						"code": "LAG",
						"name": "Laguna"
					},
					{
						"code": "LAN",
						"name": "Lanao del Norte"
					},
					{
						"code": "LAS",
						"name": "Lanao del Sur"
					},
					{
						"code": "LEY",
						"name": "Leyte"
					},
					{
						"code": "MAG",
						"name": "Maguindanao"
					},
					{
						"code": "MAD",
						"name": "Marinduque"
					},
					{
						"code": "MAS",
						"name": "Masbate"
					},
					{
						"code": "MSC",
						"name": "Misamis Occidental"
					},
					{
						"code": "MSR",
						"name": "Misamis Oriental"
					},
					{
						"code": "MOU",
						"name": "Mountain Province"
					},
					{
						"code": "NEC",
						"name": "Negros Occidental"
					},
					{
						"code": "NER",
						"name": "Negros Oriental"
					},
					{
						"code": "NSA",
						"name": "Northern Samar"
					},
					{
						"code": "NUE",
						"name": "Nueva Ecija"
					},
					{
						"code": "NUV",
						"name": "Nueva Vizcaya"
					},
					{
						"code": "MDC",
						"name": "Occidental Mindoro"
					},
					{
						"code": "MDR",
						"name": "Oriental Mindoro"
					},
					{
						"code": "PLW",
						"name": "Palawan"
					},
					{
						"code": "PAM",
						"name": "Pampanga"
					},
					{
						"code": "PAN",
						"name": "Pangasinan"
					},
					{
						"code": "QUE",
						"name": "Quezon"
					},
					{
						"code": "QUI",
						"name": "Quirino"
					},
					{
						"code": "RIZ",
						"name": "Rizal"
					},
					{
						"code": "ROM",
						"name": "Romblon"
					},
					{
						"code": "WSA",
						"name": "Samar"
					},
					{
						"code": "SAR",
						"name": "Sarangani"
					},
					{
						"code": "SIQ",
						"name": "Siquijor"
					},
					{
						"code": "SOR",
						"name": "Sorsogon"
					},
					{
						"code": "SCO",
						"name": "South Cotabato"
					},
					{
						"code": "SLE",
						"name": "Southern Leyte"
					},
					{
						"code": "SUK",
						"name": "Sultan Kudarat"
					},
					{
						"code": "SLU",
						"name": "Sulu"
					},
					{
						"code": "SUN",
						"name": "Surigao del Norte"
					},
					{
						"code": "SUR",
						"name": "Surigao del Sur"
					},
					{
						"code": "TAR",
						"name": "Tarlac"
					},
					{
						"code": "TAW",
						"name": "Tawi-Tawi"
					},
					{
						"code": "ZMB",
						"name": "Zambales"
					},
					{
						"code": "ZAN",
						"name": "Zamboanga del Norte"
					},
					{
						"code": "ZAS",
						"name": "Zamboanga del Sur"
					},
					{
						"code": "ZSI",
						"name": "Zamboanga Sibugay"
					},
					{
						"code": "00",
						"name": "Metro Manila"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PN",
				"name": "Pitcairn",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PL",
				"name": "Poland",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PT",
				"name": "Portugal",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PR",
				"name": "Puerto Rico",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "QA",
				"name": "Qatar",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "RE",
				"name": "Reunion",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "RO",
				"name": "Romania",
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
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "RU",
				"name": "Russia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "RW",
				"name": "Rwanda",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ST",
				"name": "S&atilde;o Tom&eacute; and Pr&iacute;ncipe",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "BL",
				"name": "Saint Barth&eacute;lemy",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SH",
				"name": "Saint Helena",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KN",
				"name": "Saint Kitts and Nevis",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LC",
				"name": "Saint Lucia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SX",
				"name": "Saint Martin (Dutch part)",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "MF",
				"name": "Saint Martin (French part)",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "PM",
				"name": "Saint Pierre and Miquelon",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "VC",
				"name": "Saint Vincent and the Grenadines",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "WS",
				"name": "Samoa",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SM",
				"name": "San Marino",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SA",
				"name": "Saudi Arabia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SN",
				"name": "Senegal",
				"states": expect.arrayContaining([]),
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "RS",
				"name": "Serbia",
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
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SC",
				"name": "Seychelles",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SL",
				"name": "Sierra Leone",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SG",
				"name": "Singapore",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SK",
				"name": "Slovakia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SI",
				"name": "Slovenia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SB",
				"name": "Solomon Islands",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SO",
				"name": "Somalia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ZA",
				"name": "South Africa",
				"states": [{
						"code": "EC",
						"name": "Eastern Cape"
					},
					{
						"code": "FS",
						"name": "Free State"
					},
					{
						"code": "GP",
						"name": "Gauteng"
					},
					{
						"code": "KZN",
						"name": "KwaZulu-Natal"
					},
					{
						"code": "LP",
						"name": "Limpopo"
					},
					{
						"code": "MP",
						"name": "Mpumalanga"
					},
					{
						"code": "NC",
						"name": "Northern Cape"
					},
					{
						"code": "NW",
						"name": "North West"
					},
					{
						"code": "WC",
						"name": "Western Cape"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GS",
				"name": "South Georgia/Sandwich Islands",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "KR",
				"name": "South Korea",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SS",
				"name": "South Sudan",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ES",
				"name": "Spain",
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
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "LK",
				"name": "Sri Lanka",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SD",
				"name": "Sudan",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SR",
				"name": "Suriname",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SJ",
				"name": "Svalbard and Jan Mayen",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SE",
				"name": "Sweden",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "CH",
				"name": "Switzerland",
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
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "SY",
				"name": "Syria",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TW",
				"name": "Taiwan",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TJ",
				"name": "Tajikistan",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TZ",
				"name": "Tanzania",
				"states": [{
						"code": "TZ01",
						"name": "Arusha"
					},
					{
						"code": "TZ02",
						"name": "Dar es Salaam"
					},
					{
						"code": "TZ03",
						"name": "Dodoma"
					},
					{
						"code": "TZ04",
						"name": "Iringa"
					},
					{
						"code": "TZ05",
						"name": "Kagera"
					},
					{
						"code": "TZ06",
						"name": "Pemba North"
					},
					{
						"code": "TZ07",
						"name": "Zanzibar North"
					},
					{
						"code": "TZ08",
						"name": "Kigoma"
					},
					{
						"code": "TZ09",
						"name": "Kilimanjaro"
					},
					{
						"code": "TZ10",
						"name": "Pemba South"
					},
					{
						"code": "TZ11",
						"name": "Zanzibar South"
					},
					{
						"code": "TZ12",
						"name": "Lindi"
					},
					{
						"code": "TZ13",
						"name": "Mara"
					},
					{
						"code": "TZ14",
						"name": "Mbeya"
					},
					{
						"code": "TZ15",
						"name": "Zanzibar West"
					},
					{
						"code": "TZ16",
						"name": "Morogoro"
					},
					{
						"code": "TZ17",
						"name": "Mtwara"
					},
					{
						"code": "TZ18",
						"name": "Mwanza"
					},
					{
						"code": "TZ19",
						"name": "Coast"
					},
					{
						"code": "TZ20",
						"name": "Rukwa"
					},
					{
						"code": "TZ21",
						"name": "Ruvuma"
					},
					{
						"code": "TZ22",
						"name": "Shinyanga"
					},
					{
						"code": "TZ23",
						"name": "Singida"
					},
					{
						"code": "TZ24",
						"name": "Tabora"
					},
					{
						"code": "TZ25",
						"name": "Tanga"
					},
					{
						"code": "TZ26",
						"name": "Manyara"
					},
					{
						"code": "TZ27",
						"name": "Geita"
					},
					{
						"code": "TZ28",
						"name": "Katavi"
					},
					{
						"code": "TZ29",
						"name": "Njombe"
					},
					{
						"code": "TZ30",
						"name": "Simiyu"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TH",
				"name": "Thailand",
				"states": [{
						"code": "TH-37",
						"name": "Amnat Charoen"
					},
					{
						"code": "TH-15",
						"name": "Ang Thong"
					},
					{
						"code": "TH-14",
						"name": "Ayutthaya"
					},
					{
						"code": "TH-10",
						"name": "Bangkok"
					},
					{
						"code": "TH-38",
						"name": "Bueng Kan"
					},
					{
						"code": "TH-31",
						"name": "Buri Ram"
					},
					{
						"code": "TH-24",
						"name": "Chachoengsao"
					},
					{
						"code": "TH-18",
						"name": "Chai Nat"
					},
					{
						"code": "TH-36",
						"name": "Chaiyaphum"
					},
					{
						"code": "TH-22",
						"name": "Chanthaburi"
					},
					{
						"code": "TH-50",
						"name": "Chiang Mai"
					},
					{
						"code": "TH-57",
						"name": "Chiang Rai"
					},
					{
						"code": "TH-20",
						"name": "Chonburi"
					},
					{
						"code": "TH-86",
						"name": "Chumphon"
					},
					{
						"code": "TH-46",
						"name": "Kalasin"
					},
					{
						"code": "TH-62",
						"name": "Kamphaeng Phet"
					},
					{
						"code": "TH-71",
						"name": "Kanchanaburi"
					},
					{
						"code": "TH-40",
						"name": "Khon Kaen"
					},
					{
						"code": "TH-81",
						"name": "Krabi"
					},
					{
						"code": "TH-52",
						"name": "Lampang"
					},
					{
						"code": "TH-51",
						"name": "Lamphun"
					},
					{
						"code": "TH-42",
						"name": "Loei"
					},
					{
						"code": "TH-16",
						"name": "Lopburi"
					},
					{
						"code": "TH-58",
						"name": "Mae Hong Son"
					},
					{
						"code": "TH-44",
						"name": "Maha Sarakham"
					},
					{
						"code": "TH-49",
						"name": "Mukdahan"
					},
					{
						"code": "TH-26",
						"name": "Nakhon Nayok"
					},
					{
						"code": "TH-73",
						"name": "Nakhon Pathom"
					},
					{
						"code": "TH-48",
						"name": "Nakhon Phanom"
					},
					{
						"code": "TH-30",
						"name": "Nakhon Ratchasima"
					},
					{
						"code": "TH-60",
						"name": "Nakhon Sawan"
					},
					{
						"code": "TH-80",
						"name": "Nakhon Si Thammarat"
					},
					{
						"code": "TH-55",
						"name": "Nan"
					},
					{
						"code": "TH-96",
						"name": "Narathiwat"
					},
					{
						"code": "TH-39",
						"name": "Nong Bua Lam Phu"
					},
					{
						"code": "TH-43",
						"name": "Nong Khai"
					},
					{
						"code": "TH-12",
						"name": "Nonthaburi"
					},
					{
						"code": "TH-13",
						"name": "Pathum Thani"
					},
					{
						"code": "TH-94",
						"name": "Pattani"
					},
					{
						"code": "TH-82",
						"name": "Phang Nga"
					},
					{
						"code": "TH-93",
						"name": "Phatthalung"
					},
					{
						"code": "TH-56",
						"name": "Phayao"
					},
					{
						"code": "TH-67",
						"name": "Phetchabun"
					},
					{
						"code": "TH-76",
						"name": "Phetchaburi"
					},
					{
						"code": "TH-66",
						"name": "Phichit"
					},
					{
						"code": "TH-65",
						"name": "Phitsanulok"
					},
					{
						"code": "TH-54",
						"name": "Phrae"
					},
					{
						"code": "TH-83",
						"name": "Phuket"
					},
					{
						"code": "TH-25",
						"name": "Prachin Buri"
					},
					{
						"code": "TH-77",
						"name": "Prachuap Khiri Khan"
					},
					{
						"code": "TH-85",
						"name": "Ranong"
					},
					{
						"code": "TH-70",
						"name": "Ratchaburi"
					},
					{
						"code": "TH-21",
						"name": "Rayong"
					},
					{
						"code": "TH-45",
						"name": "Roi Et"
					},
					{
						"code": "TH-27",
						"name": "Sa Kaeo"
					},
					{
						"code": "TH-47",
						"name": "Sakon Nakhon"
					},
					{
						"code": "TH-11",
						"name": "Samut Prakan"
					},
					{
						"code": "TH-74",
						"name": "Samut Sakhon"
					},
					{
						"code": "TH-75",
						"name": "Samut Songkhram"
					},
					{
						"code": "TH-19",
						"name": "Saraburi"
					},
					{
						"code": "TH-91",
						"name": "Satun"
					},
					{
						"code": "TH-17",
						"name": "Sing Buri"
					},
					{
						"code": "TH-33",
						"name": "Sisaket"
					},
					{
						"code": "TH-90",
						"name": "Songkhla"
					},
					{
						"code": "TH-64",
						"name": "Sukhothai"
					},
					{
						"code": "TH-72",
						"name": "Suphan Buri"
					},
					{
						"code": "TH-84",
						"name": "Surat Thani"
					},
					{
						"code": "TH-32",
						"name": "Surin"
					},
					{
						"code": "TH-63",
						"name": "Tak"
					},
					{
						"code": "TH-92",
						"name": "Trang"
					},
					{
						"code": "TH-23",
						"name": "Trat"
					},
					{
						"code": "TH-34",
						"name": "Ubon Ratchathani"
					},
					{
						"code": "TH-41",
						"name": "Udon Thani"
					},
					{
						"code": "TH-61",
						"name": "Uthai Thani"
					},
					{
						"code": "TH-53",
						"name": "Uttaradit"
					},
					{
						"code": "TH-95",
						"name": "Yala"
					},
					{
						"code": "TH-35",
						"name": "Yasothon"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TL",
				"name": "Timor-Leste",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TG",
				"name": "Togo",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TK",
				"name": "Tokelau",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TO",
				"name": "Tonga",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TT",
				"name": "Trinidad and Tobago",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TN",
				"name": "Tunisia",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TR",
				"name": "Turkey",
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
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TM",
				"name": "Turkmenistan",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TC",
				"name": "Turks and Caicos Islands",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "TV",
				"name": "Tuvalu",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "UG",
				"name": "Uganda",
				"states": [{
						"code": "UG314",
						"name": "Abim"
					},
					{
						"code": "UG301",
						"name": "Adjumani"
					},
					{
						"code": "UG322",
						"name": "Agago"
					},
					{
						"code": "UG323",
						"name": "Alebtong"
					},
					{
						"code": "UG315",
						"name": "Amolatar"
					},
					{
						"code": "UG324",
						"name": "Amudat"
					},
					{
						"code": "UG216",
						"name": "Amuria"
					},
					{
						"code": "UG316",
						"name": "Amuru"
					},
					{
						"code": "UG302",
						"name": "Apac"
					},
					{
						"code": "UG303",
						"name": "Arua"
					},
					{
						"code": "UG217",
						"name": "Budaka"
					},
					{
						"code": "UG218",
						"name": "Bududa"
					},
					{
						"code": "UG201",
						"name": "Bugiri"
					},
					{
						"code": "UG235",
						"name": "Bugweri"
					},
					{
						"code": "UG420",
						"name": "Buhweju"
					},
					{
						"code": "UG117",
						"name": "Buikwe"
					},
					{
						"code": "UG219",
						"name": "Bukedea"
					},
					{
						"code": "UG118",
						"name": "Bukomansimbi"
					},
					{
						"code": "UG220",
						"name": "Bukwa"
					},
					{
						"code": "UG225",
						"name": "Bulambuli"
					},
					{
						"code": "UG416",
						"name": "Buliisa"
					},
					{
						"code": "UG401",
						"name": "Bundibugyo"
					},
					{
						"code": "UG430",
						"name": "Bunyangabu"
					},
					{
						"code": "UG402",
						"name": "Bushenyi"
					},
					{
						"code": "UG202",
						"name": "Busia"
					},
					{
						"code": "UG221",
						"name": "Butaleja"
					},
					{
						"code": "UG119",
						"name": "Butambala"
					},
					{
						"code": "UG233",
						"name": "Butebo"
					},
					{
						"code": "UG120",
						"name": "Buvuma"
					},
					{
						"code": "UG226",
						"name": "Buyende"
					},
					{
						"code": "UG317",
						"name": "Dokolo"
					},
					{
						"code": "UG121",
						"name": "Gomba"
					},
					{
						"code": "UG304",
						"name": "Gulu"
					},
					{
						"code": "UG403",
						"name": "Hoima"
					},
					{
						"code": "UG417",
						"name": "Ibanda"
					},
					{
						"code": "UG203",
						"name": "Iganga"
					},
					{
						"code": "UG418",
						"name": "Isingiro"
					},
					{
						"code": "UG204",
						"name": "Jinja"
					},
					{
						"code": "UG318",
						"name": "Kaabong"
					},
					{
						"code": "UG404",
						"name": "Kabale"
					},
					{
						"code": "UG405",
						"name": "Kabarole"
					},
					{
						"code": "UG213",
						"name": "Kaberamaido"
					},
					{
						"code": "UG427",
						"name": "Kagadi"
					},
					{
						"code": "UG428",
						"name": "Kakumiro"
					},
					{
						"code": "UG101",
						"name": "Kalangala"
					},
					{
						"code": "UG222",
						"name": "Kaliro"
					},
					{
						"code": "UG122",
						"name": "Kalungu"
					},
					{
						"code": "UG102",
						"name": "Kampala"
					},
					{
						"code": "UG205",
						"name": "Kamuli"
					},
					{
						"code": "UG413",
						"name": "Kamwenge"
					},
					{
						"code": "UG414",
						"name": "Kanungu"
					},
					{
						"code": "UG206",
						"name": "Kapchorwa"
					},
					{
						"code": "UG236",
						"name": "Kapelebyong"
					},
					{
						"code": "UG126",
						"name": "Kasanda"
					},
					{
						"code": "UG406",
						"name": "Kasese"
					},
					{
						"code": "UG207",
						"name": "Katakwi"
					},
					{
						"code": "UG112",
						"name": "Kayunga"
					},
					{
						"code": "UG407",
						"name": "Kibaale"
					},
					{
						"code": "UG103",
						"name": "Kiboga"
					},
					{
						"code": "UG227",
						"name": "Kibuku"
					},
					{
						"code": "UG432",
						"name": "Kikuube"
					},
					{
						"code": "UG419",
						"name": "Kiruhura"
					},
					{
						"code": "UG421",
						"name": "Kiryandongo"
					},
					{
						"code": "UG408",
						"name": "Kisoro"
					},
					{
						"code": "UG305",
						"name": "Kitgum"
					},
					{
						"code": "UG319",
						"name": "Koboko"
					},
					{
						"code": "UG325",
						"name": "Kole"
					},
					{
						"code": "UG306",
						"name": "Kotido"
					},
					{
						"code": "UG208",
						"name": "Kumi"
					},
					{
						"code": "UG333",
						"name": "Kwania"
					},
					{
						"code": "UG228",
						"name": "Kween"
					},
					{
						"code": "UG123",
						"name": "Kyankwanzi"
					},
					{
						"code": "UG422",
						"name": "Kyegegwa"
					},
					{
						"code": "UG415",
						"name": "Kyenjojo"
					},
					{
						"code": "UG125",
						"name": "Kyotera"
					},
					{
						"code": "UG326",
						"name": "Lamwo"
					},
					{
						"code": "UG307",
						"name": "Lira"
					},
					{
						"code": "UG229",
						"name": "Luuka"
					},
					{
						"code": "UG104",
						"name": "Luwero"
					},
					{
						"code": "UG124",
						"name": "Lwengo"
					},
					{
						"code": "UG114",
						"name": "Lyantonde"
					},
					{
						"code": "UG223",
						"name": "Manafwa"
					},
					{
						"code": "UG320",
						"name": "Maracha"
					},
					{
						"code": "UG105",
						"name": "Masaka"
					},
					{
						"code": "UG409",
						"name": "Masindi"
					},
					{
						"code": "UG214",
						"name": "Mayuge"
					},
					{
						"code": "UG209",
						"name": "Mbale"
					},
					{
						"code": "UG410",
						"name": "Mbarara"
					},
					{
						"code": "UG423",
						"name": "Mitooma"
					},
					{
						"code": "UG115",
						"name": "Mityana"
					},
					{
						"code": "UG308",
						"name": "Moroto"
					},
					{
						"code": "UG309",
						"name": "Moyo"
					},
					{
						"code": "UG106",
						"name": "Mpigi"
					},
					{
						"code": "UG107",
						"name": "Mubende"
					},
					{
						"code": "UG108",
						"name": "Mukono"
					},
					{
						"code": "UG334",
						"name": "Nabilatuk"
					},
					{
						"code": "UG311",
						"name": "Nakapiripirit"
					},
					{
						"code": "UG116",
						"name": "Nakaseke"
					},
					{
						"code": "UG109",
						"name": "Nakasongola"
					},
					{
						"code": "UG230",
						"name": "Namayingo"
					},
					{
						"code": "UG234",
						"name": "Namisindwa"
					},
					{
						"code": "UG224",
						"name": "Namutumba"
					},
					{
						"code": "UG327",
						"name": "Napak"
					},
					{
						"code": "UG310",
						"name": "Nebbi"
					},
					{
						"code": "UG231",
						"name": "Ngora"
					},
					{
						"code": "UG424",
						"name": "Ntoroko"
					},
					{
						"code": "UG411",
						"name": "Ntungamo"
					},
					{
						"code": "UG328",
						"name": "Nwoya"
					},
					{
						"code": "UG331",
						"name": "Omoro"
					},
					{
						"code": "UG329",
						"name": "Otuke"
					},
					{
						"code": "UG321",
						"name": "Oyam"
					},
					{
						"code": "UG312",
						"name": "Pader"
					},
					{
						"code": "UG332",
						"name": "Pakwach"
					},
					{
						"code": "UG210",
						"name": "Pallisa"
					},
					{
						"code": "UG110",
						"name": "Rakai"
					},
					{
						"code": "UG429",
						"name": "Rubanda"
					},
					{
						"code": "UG425",
						"name": "Rubirizi"
					},
					{
						"code": "UG431",
						"name": "Rukiga"
					},
					{
						"code": "UG412",
						"name": "Rukungiri"
					},
					{
						"code": "UG111",
						"name": "Sembabule"
					},
					{
						"code": "UG232",
						"name": "Serere"
					},
					{
						"code": "UG426",
						"name": "Sheema"
					},
					{
						"code": "UG215",
						"name": "Sironko"
					},
					{
						"code": "UG211",
						"name": "Soroti"
					},
					{
						"code": "UG212",
						"name": "Tororo"
					},
					{
						"code": "UG113",
						"name": "Wakiso"
					},
					{
						"code": "UG313",
						"name": "Yumbe"
					},
					{
						"code": "UG330",
						"name": "Zombo"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "UA",
				"name": "Ukraine",
				"states": expect.arrayContaining([]),
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "AE",
				"name": "United Arab Emirates",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "GB",
				"name": "United Kingdom (UK)",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "US",
				"name": "United States (US)",
				"states": [{
						"code": "AL",
						"name": "Alabama"
					},
					{
						"code": "AK",
						"name": "Alaska"
					},
					{
						"code": "AZ",
						"name": "Arizona"
					},
					{
						"code": "AR",
						"name": "Arkansas"
					},
					{
						"code": "CA",
						"name": "California"
					},
					{
						"code": "CO",
						"name": "Colorado"
					},
					{
						"code": "CT",
						"name": "Connecticut"
					},
					{
						"code": "DE",
						"name": "Delaware"
					},
					{
						"code": "DC",
						"name": "District Of Columbia"
					},
					{
						"code": "FL",
						"name": "Florida"
					},
					{
						"code": "GA",
						"name": "Georgia"
					},
					{
						"code": "HI",
						"name": "Hawaii"
					},
					{
						"code": "ID",
						"name": "Idaho"
					},
					{
						"code": "IL",
						"name": "Illinois"
					},
					{
						"code": "IN",
						"name": "Indiana"
					},
					{
						"code": "IA",
						"name": "Iowa"
					},
					{
						"code": "KS",
						"name": "Kansas"
					},
					{
						"code": "KY",
						"name": "Kentucky"
					},
					{
						"code": "LA",
						"name": "Louisiana"
					},
					{
						"code": "ME",
						"name": "Maine"
					},
					{
						"code": "MD",
						"name": "Maryland"
					},
					{
						"code": "MA",
						"name": "Massachusetts"
					},
					{
						"code": "MI",
						"name": "Michigan"
					},
					{
						"code": "MN",
						"name": "Minnesota"
					},
					{
						"code": "MS",
						"name": "Mississippi"
					},
					{
						"code": "MO",
						"name": "Missouri"
					},
					{
						"code": "MT",
						"name": "Montana"
					},
					{
						"code": "NE",
						"name": "Nebraska"
					},
					{
						"code": "NV",
						"name": "Nevada"
					},
					{
						"code": "NH",
						"name": "New Hampshire"
					},
					{
						"code": "NJ",
						"name": "New Jersey"
					},
					{
						"code": "NM",
						"name": "New Mexico"
					},
					{
						"code": "NY",
						"name": "New York"
					},
					{
						"code": "NC",
						"name": "North Carolina"
					},
					{
						"code": "ND",
						"name": "North Dakota"
					},
					{
						"code": "OH",
						"name": "Ohio"
					},
					{
						"code": "OK",
						"name": "Oklahoma"
					},
					{
						"code": "OR",
						"name": "Oregon"
					},
					{
						"code": "PA",
						"name": "Pennsylvania"
					},
					{
						"code": "RI",
						"name": "Rhode Island"
					},
					{
						"code": "SC",
						"name": "South Carolina"
					},
					{
						"code": "SD",
						"name": "South Dakota"
					},
					{
						"code": "TN",
						"name": "Tennessee"
					},
					{
						"code": "TX",
						"name": "Texas"
					},
					{
						"code": "UT",
						"name": "Utah"
					},
					{
						"code": "VT",
						"name": "Vermont"
					},
					{
						"code": "VA",
						"name": "Virginia"
					},
					{
						"code": "WA",
						"name": "Washington"
					},
					{
						"code": "WV",
						"name": "West Virginia"
					},
					{
						"code": "WI",
						"name": "Wisconsin"
					},
					{
						"code": "WY",
						"name": "Wyoming"
					},
					{
						"code": "AA",
						"name": "Armed Forces (AA)"
					},
					{
						"code": "AE",
						"name": "Armed Forces (AE)"
					},
					{
						"code": "AP",
						"name": "Armed Forces (AP)"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "UM",
				"name": "United States (US) Minor Outlying Islands",
				"states": [{
						"code": 81,
						"name": "Baker Island"
					},
					{
						"code": 84,
						"name": "Howland Island"
					},
					{
						"code": 86,
						"name": "Jarvis Island"
					},
					{
						"code": 67,
						"name": "Johnston Atoll"
					},
					{
						"code": 89,
						"name": "Kingman Reef"
					},
					{
						"code": 71,
						"name": "Midway Atoll"
					},
					{
						"code": 76,
						"name": "Navassa Island"
					},
					{
						"code": 95,
						"name": "Palmyra Atoll"
					},
					{
						"code": 79,
						"name": "Wake Island"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "UY",
				"name": "Uruguay",
				"states": [{
						"code": "UY-AR",
						"name": "Artigas"
					},
					{
						"code": "UY-CA",
						"name": "Canelones"
					},
					{
						"code": "UY-CL",
						"name": "Cerro Largo"
					},
					{
						"code": "UY-CO",
						"name": "Colonia"
					},
					{
						"code": "UY-DU",
						"name": "Durazno"
					},
					{
						"code": "UY-FS",
						"name": "Flores"
					},
					{
						"code": "UY-FD",
						"name": "Florida"
					},
					{
						"code": "UY-LA",
						"name": "Lavalleja"
					},
					{
						"code": "UY-MA",
						"name": "Maldonado"
					},
					{
						"code": "UY-MO",
						"name": "Montevideo"
					},
					{
						"code": "UY-PA",
						"name": "Paysandú"
					},
					{
						"code": "UY-RN",
						"name": "Río Negro"
					},
					{
						"code": "UY-RV",
						"name": "Rivera"
					},
					{
						"code": "UY-RO",
						"name": "Rocha"
					},
					{
						"code": "UY-SA",
						"name": "Salto"
					},
					{
						"code": "UY-SJ",
						"name": "San José"
					},
					{
						"code": "UY-SO",
						"name": "Soriano"
					},
					{
						"code": "UY-TA",
						"name": "Tacuarembó"
					},
					{
						"code": "UY-TT",
						"name": "Treinta y Tres"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "UZ",
				"name": "Uzbekistan",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "VU",
				"name": "Vanuatu",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "VA",
				"name": "Vatican",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "VE",
				"name": "Venezuela",
				"states": [{
						"code": "VE-A",
						"name": "Capital"
					},
					{
						"code": "VE-B",
						"name": "Anzoátegui"
					},
					{
						"code": "VE-C",
						"name": "Apure"
					},
					{
						"code": "VE-D",
						"name": "Aragua"
					},
					{
						"code": "VE-E",
						"name": "Barinas"
					},
					{
						"code": "VE-F",
						"name": "Bolívar"
					},
					{
						"code": "VE-G",
						"name": "Carabobo"
					},
					{
						"code": "VE-H",
						"name": "Cojedes"
					},
					{
						"code": "VE-I",
						"name": "Falcón"
					},
					{
						"code": "VE-J",
						"name": "Guárico"
					},
					{
						"code": "VE-K",
						"name": "Lara"
					},
					{
						"code": "VE-L",
						"name": "Mérida"
					},
					{
						"code": "VE-M",
						"name": "Miranda"
					},
					{
						"code": "VE-N",
						"name": "Monagas"
					},
					{
						"code": "VE-O",
						"name": "Nueva Esparta"
					},
					{
						"code": "VE-P",
						"name": "Portuguesa"
					},
					{
						"code": "VE-R",
						"name": "Sucre"
					},
					{
						"code": "VE-S",
						"name": "Táchira"
					},
					{
						"code": "VE-T",
						"name": "Trujillo"
					},
					{
						"code": "VE-U",
						"name": "Yaracuy"
					},
					{
						"code": "VE-V",
						"name": "Zulia"
					},
					{
						"code": "VE-W",
						"name": "Federal Dependencies"
					},
					{
						"code": "VE-X",
						"name": "La Guaira (Vargas)"
					},
					{
						"code": "VE-Y",
						"name": "Delta Amacuro"
					},
					{
						"code": "VE-Z",
						"name": "Amazonas"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "VN",
				"name": "Vietnam",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "VG",
				"name": "Virgin Islands (British)",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "VI",
				"name": "Virgin Islands (US)",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "WF",
				"name": "Wallis and Futuna",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "EH",
				"name": "Western Sahara",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "YE",
				"name": "Yemen",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ZM",
				"name": "Zambia",
				"states": [{
						"code": "ZM-01",
						"name": "Western"
					},
					{
						"code": "ZM-02",
						"name": "Central"
					},
					{
						"code": "ZM-03",
						"name": "Eastern"
					},
					{
						"code": "ZM-04",
						"name": "Luapula"
					},
					{
						"code": "ZM-05",
						"name": "Northern"
					},
					{
						"code": "ZM-06",
						"name": "North-Western"
					},
					{
						"code": "ZM-07",
						"name": "Southern"
					},
					{
						"code": "ZM-08",
						"name": "Copperbelt"
					},
					{
						"code": "ZM-09",
						"name": "Lusaka"
					},
					{
						"code": "ZM-10",
						"name": "Muchinga"
					}
				],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
		expect(responseJSON).toEqual(expect.arrayContaining([
			expect.objectContaining({
				"code": "ZW",
				"name": "Zimbabwe",
				"states": [],
				"_links": {
					"self": [{
						"href": expect.any(String)
					}],
					"collection": [{
						"href": expect.any(String)
					}]
				}
			})
		]));
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
