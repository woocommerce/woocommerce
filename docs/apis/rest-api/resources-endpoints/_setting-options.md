# Setting options #

## Setting option properties ##

| Attribute     | Type   | Description                                                                                                                                                                                     |
|---------------|--------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `id`          | string | A unique identifier for the setting. <i class="label label-info">read-only</i>                                                                                                                  |
| `label`       | string | A human readable label for the setting used in interfaces. <i class="label label-info">read-only</i>                                                                                            |
| `description` | string | A human readable description for the setting used in interfaces. <i class="label label-info">read-only</i>                                                                                      |
| `value`       | mixed  | Setting value.                                                                                                                                                                                  |
| `default`     | mixed  | Default value for the setting. <i class="label label-info">read-only</i>                                                                                                                        |
| `tip`         | string | Additional help text shown to the user about the setting. <i class="label label-info">read-only</i>                                                                                             |
| `placeholder` | string | Placeholder text to be displayed in text inputs. <i class="label label-info">read-only</i>                                                                                                      |
| `type`        | string | Type of setting. Options: `text`, `email`, `number`, `color`, `password`, `textarea`, `select`, `multiselect`, `radio`, `image_width` and `checkbox`. <i class="label label-info">read-only</i> |
| `options`     | object | Array of options (key value pairs) for inputs such as select, multiselect, and radio buttons. <i class="label label-info">read-only</i>                                                         |
| `group_id`    | string | An identifier for the group this setting belongs to. <i class="label label-info">read-only</i>                                                                                                  |

## Retrieve a setting option ##

This API lets you retrieve and view a specific setting option.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/settings/&lt;group_id&gt;/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/settings/general/woocommerce_allowed_countries \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("settings/general/woocommerce_allowed_countries")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('settings/general/woocommerce_allowed_countries')); ?>
```

```python
print(wcapi.get("settings/general/woocommerce_allowed_countries").json())
```

```ruby
woocommerce.get("settings/general/woocommerce_allowed_countries").parsed_response
```

> JSON response example:

```json
{
	"id": "woocommerce_allowed_countries",
	"label": "Selling location(s)",
	"description": "This option lets you limit which countries you are willing to sell to.",
	"type": "select",
	"default": "all",
	"options": {
		"all": "Sell to all countries",
		"all_except": "Sell to all countries, except for&hellip;",
		"specific": "Sell to specific countries"
	},
	"tip": "This option lets you limit which countries you are willing to sell to.",
	"value": "all",
	"group_id": "general",
	"_links": {
		"self": [
			{
				"href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_allowed_countries"
			}
		],
		"collection": [
			{
				"href": "https://example.com/wp-json/wc/v3/settings/general"
			}
		]
	}
}
```

## List all setting options ##

This API helps you to view all the setting options.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-get">GET</i>
		<h6>/wp-json/wc/v3/settings/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl https://example.com/wp-json/wc/v3/settings/general \
	-u consumer_key:consumer_secret
```

```javascript
WooCommerce.get("settings/general")
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php print_r($woocommerce->get('settings/general')); ?>
```

```python
print(wcapi.get("settings/general").json())
```

```ruby
woocommerce.get("settings/general").parsed_response
```

> JSON response example:

```json
[
  {
    "id": "woocommerce_allowed_countries",
    "label": "Selling location(s)",
    "description": "This option lets you limit which countries you are willing to sell to.",
    "type": "select",
    "default": "all",
    "options": {
      "all": "Sell to all countries",
      "all_except": "Sell to all countries, except for&hellip;",
      "specific": "Sell to specific countries"
    },
    "tip": "This option lets you limit which countries you are willing to sell to.",
    "value": "all",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_allowed_countries"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "woocommerce_all_except_countries",
    "label": "Sell to all countries, except for&hellip;",
    "description": "",
    "type": "multiselect",
    "default": "",
    "value": "",
    "options": {
      "AX": "&#197;land Islands",
      "AF": "Afghanistan",
      "AL": "Albania",
      "DZ": "Algeria",
      "AS": "American Samoa",
      "AD": "Andorra",
      "AO": "Angola",
      "AI": "Anguilla",
      "AQ": "Antarctica",
      "AG": "Antigua and Barbuda",
      "AR": "Argentina",
      "AM": "Armenia",
      "AW": "Aruba",
      "AU": "Australia",
      "AT": "Austria",
      "AZ": "Azerbaijan",
      "BS": "Bahamas",
      "BH": "Bahrain",
      "BD": "Bangladesh",
      "BB": "Barbados",
      "BY": "Belarus",
      "PW": "Belau",
      "BE": "Belgium",
      "BZ": "Belize",
      "BJ": "Benin",
      "BM": "Bermuda",
      "BT": "Bhutan",
      "BO": "Bolivia",
      "BQ": "Bonaire, Saint Eustatius and Saba",
      "BA": "Bosnia and Herzegovina",
      "BW": "Botswana",
      "BV": "Bouvet Island",
      "BR": "Brazil",
      "IO": "British Indian Ocean Territory",
      "VG": "British Virgin Islands",
      "BN": "Brunei",
      "BG": "Bulgaria",
      "BF": "Burkina Faso",
      "BI": "Burundi",
      "KH": "Cambodia",
      "CM": "Cameroon",
      "CA": "Canada",
      "CV": "Cape Verde",
      "KY": "Cayman Islands",
      "CF": "Central African Republic",
      "TD": "Chad",
      "CL": "Chile",
      "CN": "China",
      "CX": "Christmas Island",
      "CC": "Cocos (Keeling) Islands",
      "CO": "Colombia",
      "KM": "Comoros",
      "CG": "Congo (Brazzaville)",
      "CD": "Congo (Kinshasa)",
      "CK": "Cook Islands",
      "CR": "Costa Rica",
      "HR": "Croatia",
      "CU": "Cuba",
      "CW": "Cura&ccedil;ao",
      "CY": "Cyprus",
      "CZ": "Czech Republic",
      "DK": "Denmark",
      "DJ": "Djibouti",
      "DM": "Dominica",
      "DO": "Dominican Republic",
      "EC": "Ecuador",
      "EG": "Egypt",
      "SV": "El Salvador",
      "GQ": "Equatorial Guinea",
      "ER": "Eritrea",
      "EE": "Estonia",
      "ET": "Ethiopia",
      "FK": "Falkland Islands",
      "FO": "Faroe Islands",
      "FJ": "Fiji",
      "FI": "Finland",
      "FR": "France",
      "GF": "French Guiana",
      "PF": "French Polynesia",
      "TF": "French Southern Territories",
      "GA": "Gabon",
      "GM": "Gambia",
      "GE": "Georgia",
      "DE": "Germany",
      "GH": "Ghana",
      "GI": "Gibraltar",
      "GR": "Greece",
      "GL": "Greenland",
      "GD": "Grenada",
      "GP": "Guadeloupe",
      "GU": "Guam",
      "GT": "Guatemala",
      "GG": "Guernsey",
      "GN": "Guinea",
      "GW": "Guinea-Bissau",
      "GY": "Guyana",
      "HT": "Haiti",
      "HM": "Heard Island and McDonald Islands",
      "HN": "Honduras",
      "HK": "Hong Kong",
      "HU": "Hungary",
      "IS": "Iceland",
      "IN": "India",
      "ID": "Indonesia",
      "IR": "Iran",
      "IQ": "Iraq",
      "IE": "Ireland",
      "IM": "Isle of Man",
      "IL": "Israel",
      "IT": "Italy",
      "CI": "Ivory Coast",
      "JM": "Jamaica",
      "JP": "Japan",
      "JE": "Jersey",
      "JO": "Jordan",
      "KZ": "Kazakhstan",
      "KE": "Kenya",
      "KI": "Kiribati",
      "KW": "Kuwait",
      "KG": "Kyrgyzstan",
      "LA": "Laos",
      "LV": "Latvia",
      "LB": "Lebanon",
      "LS": "Lesotho",
      "LR": "Liberia",
      "LY": "Libya",
      "LI": "Liechtenstein",
      "LT": "Lithuania",
      "LU": "Luxembourg",
      "MO": "Macao S.A.R., China",
      "MK": "Macedonia",
      "MG": "Madagascar",
      "MW": "Malawi",
      "MY": "Malaysia",
      "MV": "Maldives",
      "ML": "Mali",
      "MT": "Malta",
      "MH": "Marshall Islands",
      "MQ": "Martinique",
      "MR": "Mauritania",
      "MU": "Mauritius",
      "YT": "Mayotte",
      "MX": "Mexico",
      "FM": "Micronesia",
      "MD": "Moldova",
      "MC": "Monaco",
      "MN": "Mongolia",
      "ME": "Montenegro",
      "MS": "Montserrat",
      "MA": "Morocco",
      "MZ": "Mozambique",
      "MM": "Myanmar",
      "NA": "Namibia",
      "NR": "Nauru",
      "NP": "Nepal",
      "NL": "Netherlands",
      "NC": "New Caledonia",
      "NZ": "New Zealand",
      "NI": "Nicaragua",
      "NE": "Niger",
      "NG": "Nigeria",
      "NU": "Niue",
      "NF": "Norfolk Island",
      "KP": "North Korea",
      "MP": "Northern Mariana Islands",
      "NO": "Norway",
      "OM": "Oman",
      "PK": "Pakistan",
      "PS": "Palestinian Territory",
      "PA": "Panama",
      "PG": "Papua New Guinea",
      "PY": "Paraguay",
      "PE": "Peru",
      "PH": "Philippines",
      "PN": "Pitcairn",
      "PL": "Poland",
      "PT": "Portugal",
      "PR": "Puerto Rico",
      "QA": "Qatar",
      "RE": "Reunion",
      "RO": "Romania",
      "RU": "Russia",
      "RW": "Rwanda",
      "ST": "S&atilde;o Tom&eacute; and Pr&iacute;ncipe",
      "BL": "Saint Barth&eacute;lemy",
      "SH": "Saint Helena",
      "KN": "Saint Kitts and Nevis",
      "LC": "Saint Lucia",
      "SX": "Saint Martin (Dutch part)",
      "MF": "Saint Martin (French part)",
      "PM": "Saint Pierre and Miquelon",
      "VC": "Saint Vincent and the Grenadines",
      "WS": "Samoa",
      "SM": "San Marino",
      "SA": "Saudi Arabia",
      "SN": "Senegal",
      "RS": "Serbia",
      "SC": "Seychelles",
      "SL": "Sierra Leone",
      "SG": "Singapore",
      "SK": "Slovakia",
      "SI": "Slovenia",
      "SB": "Solomon Islands",
      "SO": "Somalia",
      "ZA": "South Africa",
      "GS": "South Georgia/Sandwich Islands",
      "KR": "South Korea",
      "SS": "South Sudan",
      "ES": "Spain",
      "LK": "Sri Lanka",
      "SD": "Sudan",
      "SR": "Suriname",
      "SJ": "Svalbard and Jan Mayen",
      "SZ": "Swaziland",
      "SE": "Sweden",
      "CH": "Switzerland",
      "SY": "Syria",
      "TW": "Taiwan",
      "TJ": "Tajikistan",
      "TZ": "Tanzania",
      "TH": "Thailand",
      "TL": "Timor-Leste",
      "TG": "Togo",
      "TK": "Tokelau",
      "TO": "Tonga",
      "TT": "Trinidad and Tobago",
      "TN": "Tunisia",
      "TR": "Turkey",
      "TM": "Turkmenistan",
      "TC": "Turks and Caicos Islands",
      "TV": "Tuvalu",
      "UG": "Uganda",
      "UA": "Ukraine",
      "AE": "United Arab Emirates",
      "GB": "United Kingdom (UK)",
      "US": "United States (US)",
      "UM": "United States (US) Minor Outlying Islands",
      "VI": "United States (US) Virgin Islands",
      "UY": "Uruguay",
      "UZ": "Uzbekistan",
      "VU": "Vanuatu",
      "VA": "Vatican",
      "VE": "Venezuela",
      "VN": "Vietnam",
      "WF": "Wallis and Futuna",
      "EH": "Western Sahara",
      "YE": "Yemen",
      "ZM": "Zambia",
      "ZW": "Zimbabwe"
    },
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_all_except_countries"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "woocommerce_specific_allowed_countries",
    "label": "Sell to specific countries",
    "description": "",
    "type": "multiselect",
    "default": "",
    "value": "",
    "options": {
      "AX": "&#197;land Islands",
      "AF": "Afghanistan",
      "AL": "Albania",
      "DZ": "Algeria",
      "AS": "American Samoa",
      "AD": "Andorra",
      "AO": "Angola",
      "AI": "Anguilla",
      "AQ": "Antarctica",
      "AG": "Antigua and Barbuda",
      "AR": "Argentina",
      "AM": "Armenia",
      "AW": "Aruba",
      "AU": "Australia",
      "AT": "Austria",
      "AZ": "Azerbaijan",
      "BS": "Bahamas",
      "BH": "Bahrain",
      "BD": "Bangladesh",
      "BB": "Barbados",
      "BY": "Belarus",
      "PW": "Belau",
      "BE": "Belgium",
      "BZ": "Belize",
      "BJ": "Benin",
      "BM": "Bermuda",
      "BT": "Bhutan",
      "BO": "Bolivia",
      "BQ": "Bonaire, Saint Eustatius and Saba",
      "BA": "Bosnia and Herzegovina",
      "BW": "Botswana",
      "BV": "Bouvet Island",
      "BR": "Brazil",
      "IO": "British Indian Ocean Territory",
      "VG": "British Virgin Islands",
      "BN": "Brunei",
      "BG": "Bulgaria",
      "BF": "Burkina Faso",
      "BI": "Burundi",
      "KH": "Cambodia",
      "CM": "Cameroon",
      "CA": "Canada",
      "CV": "Cape Verde",
      "KY": "Cayman Islands",
      "CF": "Central African Republic",
      "TD": "Chad",
      "CL": "Chile",
      "CN": "China",
      "CX": "Christmas Island",
      "CC": "Cocos (Keeling) Islands",
      "CO": "Colombia",
      "KM": "Comoros",
      "CG": "Congo (Brazzaville)",
      "CD": "Congo (Kinshasa)",
      "CK": "Cook Islands",
      "CR": "Costa Rica",
      "HR": "Croatia",
      "CU": "Cuba",
      "CW": "Cura&ccedil;ao",
      "CY": "Cyprus",
      "CZ": "Czech Republic",
      "DK": "Denmark",
      "DJ": "Djibouti",
      "DM": "Dominica",
      "DO": "Dominican Republic",
      "EC": "Ecuador",
      "EG": "Egypt",
      "SV": "El Salvador",
      "GQ": "Equatorial Guinea",
      "ER": "Eritrea",
      "EE": "Estonia",
      "ET": "Ethiopia",
      "FK": "Falkland Islands",
      "FO": "Faroe Islands",
      "FJ": "Fiji",
      "FI": "Finland",
      "FR": "France",
      "GF": "French Guiana",
      "PF": "French Polynesia",
      "TF": "French Southern Territories",
      "GA": "Gabon",
      "GM": "Gambia",
      "GE": "Georgia",
      "DE": "Germany",
      "GH": "Ghana",
      "GI": "Gibraltar",
      "GR": "Greece",
      "GL": "Greenland",
      "GD": "Grenada",
      "GP": "Guadeloupe",
      "GU": "Guam",
      "GT": "Guatemala",
      "GG": "Guernsey",
      "GN": "Guinea",
      "GW": "Guinea-Bissau",
      "GY": "Guyana",
      "HT": "Haiti",
      "HM": "Heard Island and McDonald Islands",
      "HN": "Honduras",
      "HK": "Hong Kong",
      "HU": "Hungary",
      "IS": "Iceland",
      "IN": "India",
      "ID": "Indonesia",
      "IR": "Iran",
      "IQ": "Iraq",
      "IE": "Ireland",
      "IM": "Isle of Man",
      "IL": "Israel",
      "IT": "Italy",
      "CI": "Ivory Coast",
      "JM": "Jamaica",
      "JP": "Japan",
      "JE": "Jersey",
      "JO": "Jordan",
      "KZ": "Kazakhstan",
      "KE": "Kenya",
      "KI": "Kiribati",
      "KW": "Kuwait",
      "KG": "Kyrgyzstan",
      "LA": "Laos",
      "LV": "Latvia",
      "LB": "Lebanon",
      "LS": "Lesotho",
      "LR": "Liberia",
      "LY": "Libya",
      "LI": "Liechtenstein",
      "LT": "Lithuania",
      "LU": "Luxembourg",
      "MO": "Macao S.A.R., China",
      "MK": "Macedonia",
      "MG": "Madagascar",
      "MW": "Malawi",
      "MY": "Malaysia",
      "MV": "Maldives",
      "ML": "Mali",
      "MT": "Malta",
      "MH": "Marshall Islands",
      "MQ": "Martinique",
      "MR": "Mauritania",
      "MU": "Mauritius",
      "YT": "Mayotte",
      "MX": "Mexico",
      "FM": "Micronesia",
      "MD": "Moldova",
      "MC": "Monaco",
      "MN": "Mongolia",
      "ME": "Montenegro",
      "MS": "Montserrat",
      "MA": "Morocco",
      "MZ": "Mozambique",
      "MM": "Myanmar",
      "NA": "Namibia",
      "NR": "Nauru",
      "NP": "Nepal",
      "NL": "Netherlands",
      "NC": "New Caledonia",
      "NZ": "New Zealand",
      "NI": "Nicaragua",
      "NE": "Niger",
      "NG": "Nigeria",
      "NU": "Niue",
      "NF": "Norfolk Island",
      "KP": "North Korea",
      "MP": "Northern Mariana Islands",
      "NO": "Norway",
      "OM": "Oman",
      "PK": "Pakistan",
      "PS": "Palestinian Territory",
      "PA": "Panama",
      "PG": "Papua New Guinea",
      "PY": "Paraguay",
      "PE": "Peru",
      "PH": "Philippines",
      "PN": "Pitcairn",
      "PL": "Poland",
      "PT": "Portugal",
      "PR": "Puerto Rico",
      "QA": "Qatar",
      "RE": "Reunion",
      "RO": "Romania",
      "RU": "Russia",
      "RW": "Rwanda",
      "ST": "S&atilde;o Tom&eacute; and Pr&iacute;ncipe",
      "BL": "Saint Barth&eacute;lemy",
      "SH": "Saint Helena",
      "KN": "Saint Kitts and Nevis",
      "LC": "Saint Lucia",
      "SX": "Saint Martin (Dutch part)",
      "MF": "Saint Martin (French part)",
      "PM": "Saint Pierre and Miquelon",
      "VC": "Saint Vincent and the Grenadines",
      "WS": "Samoa",
      "SM": "San Marino",
      "SA": "Saudi Arabia",
      "SN": "Senegal",
      "RS": "Serbia",
      "SC": "Seychelles",
      "SL": "Sierra Leone",
      "SG": "Singapore",
      "SK": "Slovakia",
      "SI": "Slovenia",
      "SB": "Solomon Islands",
      "SO": "Somalia",
      "ZA": "South Africa",
      "GS": "South Georgia/Sandwich Islands",
      "KR": "South Korea",
      "SS": "South Sudan",
      "ES": "Spain",
      "LK": "Sri Lanka",
      "SD": "Sudan",
      "SR": "Suriname",
      "SJ": "Svalbard and Jan Mayen",
      "SZ": "Swaziland",
      "SE": "Sweden",
      "CH": "Switzerland",
      "SY": "Syria",
      "TW": "Taiwan",
      "TJ": "Tajikistan",
      "TZ": "Tanzania",
      "TH": "Thailand",
      "TL": "Timor-Leste",
      "TG": "Togo",
      "TK": "Tokelau",
      "TO": "Tonga",
      "TT": "Trinidad and Tobago",
      "TN": "Tunisia",
      "TR": "Turkey",
      "TM": "Turkmenistan",
      "TC": "Turks and Caicos Islands",
      "TV": "Tuvalu",
      "UG": "Uganda",
      "UA": "Ukraine",
      "AE": "United Arab Emirates",
      "GB": "United Kingdom (UK)",
      "US": "United States (US)",
      "UM": "United States (US) Minor Outlying Islands",
      "VI": "United States (US) Virgin Islands",
      "UY": "Uruguay",
      "UZ": "Uzbekistan",
      "VU": "Vanuatu",
      "VA": "Vatican",
      "VE": "Venezuela",
      "VN": "Vietnam",
      "WF": "Wallis and Futuna",
      "EH": "Western Sahara",
      "YE": "Yemen",
      "ZM": "Zambia",
      "ZW": "Zimbabwe"
    },
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_specific_allowed_countries"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "woocommerce_ship_to_countries",
    "label": "Shipping location(s)",
    "description": "Choose which countries you want to ship to, or choose to ship to all locations you sell to.",
    "type": "select",
    "default": "",
    "options": {
      "": "Ship to all countries you sell to",
      "all": "Ship to all countries",
      "specific": "Ship to specific countries only",
      "disabled": "Disable shipping &amp; shipping calculations"
    },
    "tip": "Choose which countries you want to ship to, or choose to ship to all locations you sell to.",
    "value": "",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_ship_to_countries"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "woocommerce_specific_ship_to_countries",
    "label": "Ship to specific countries",
    "description": "",
    "type": "multiselect",
    "default": "",
    "value": "",
    "options": {
      "AX": "&#197;land Islands",
      "AF": "Afghanistan",
      "AL": "Albania",
      "DZ": "Algeria",
      "AS": "American Samoa",
      "AD": "Andorra",
      "AO": "Angola",
      "AI": "Anguilla",
      "AQ": "Antarctica",
      "AG": "Antigua and Barbuda",
      "AR": "Argentina",
      "AM": "Armenia",
      "AW": "Aruba",
      "AU": "Australia",
      "AT": "Austria",
      "AZ": "Azerbaijan",
      "BS": "Bahamas",
      "BH": "Bahrain",
      "BD": "Bangladesh",
      "BB": "Barbados",
      "BY": "Belarus",
      "PW": "Belau",
      "BE": "Belgium",
      "BZ": "Belize",
      "BJ": "Benin",
      "BM": "Bermuda",
      "BT": "Bhutan",
      "BO": "Bolivia",
      "BQ": "Bonaire, Saint Eustatius and Saba",
      "BA": "Bosnia and Herzegovina",
      "BW": "Botswana",
      "BV": "Bouvet Island",
      "BR": "Brazil",
      "IO": "British Indian Ocean Territory",
      "VG": "British Virgin Islands",
      "BN": "Brunei",
      "BG": "Bulgaria",
      "BF": "Burkina Faso",
      "BI": "Burundi",
      "KH": "Cambodia",
      "CM": "Cameroon",
      "CA": "Canada",
      "CV": "Cape Verde",
      "KY": "Cayman Islands",
      "CF": "Central African Republic",
      "TD": "Chad",
      "CL": "Chile",
      "CN": "China",
      "CX": "Christmas Island",
      "CC": "Cocos (Keeling) Islands",
      "CO": "Colombia",
      "KM": "Comoros",
      "CG": "Congo (Brazzaville)",
      "CD": "Congo (Kinshasa)",
      "CK": "Cook Islands",
      "CR": "Costa Rica",
      "HR": "Croatia",
      "CU": "Cuba",
      "CW": "Cura&ccedil;ao",
      "CY": "Cyprus",
      "CZ": "Czech Republic",
      "DK": "Denmark",
      "DJ": "Djibouti",
      "DM": "Dominica",
      "DO": "Dominican Republic",
      "EC": "Ecuador",
      "EG": "Egypt",
      "SV": "El Salvador",
      "GQ": "Equatorial Guinea",
      "ER": "Eritrea",
      "EE": "Estonia",
      "ET": "Ethiopia",
      "FK": "Falkland Islands",
      "FO": "Faroe Islands",
      "FJ": "Fiji",
      "FI": "Finland",
      "FR": "France",
      "GF": "French Guiana",
      "PF": "French Polynesia",
      "TF": "French Southern Territories",
      "GA": "Gabon",
      "GM": "Gambia",
      "GE": "Georgia",
      "DE": "Germany",
      "GH": "Ghana",
      "GI": "Gibraltar",
      "GR": "Greece",
      "GL": "Greenland",
      "GD": "Grenada",
      "GP": "Guadeloupe",
      "GU": "Guam",
      "GT": "Guatemala",
      "GG": "Guernsey",
      "GN": "Guinea",
      "GW": "Guinea-Bissau",
      "GY": "Guyana",
      "HT": "Haiti",
      "HM": "Heard Island and McDonald Islands",
      "HN": "Honduras",
      "HK": "Hong Kong",
      "HU": "Hungary",
      "IS": "Iceland",
      "IN": "India",
      "ID": "Indonesia",
      "IR": "Iran",
      "IQ": "Iraq",
      "IE": "Ireland",
      "IM": "Isle of Man",
      "IL": "Israel",
      "IT": "Italy",
      "CI": "Ivory Coast",
      "JM": "Jamaica",
      "JP": "Japan",
      "JE": "Jersey",
      "JO": "Jordan",
      "KZ": "Kazakhstan",
      "KE": "Kenya",
      "KI": "Kiribati",
      "KW": "Kuwait",
      "KG": "Kyrgyzstan",
      "LA": "Laos",
      "LV": "Latvia",
      "LB": "Lebanon",
      "LS": "Lesotho",
      "LR": "Liberia",
      "LY": "Libya",
      "LI": "Liechtenstein",
      "LT": "Lithuania",
      "LU": "Luxembourg",
      "MO": "Macao S.A.R., China",
      "MK": "Macedonia",
      "MG": "Madagascar",
      "MW": "Malawi",
      "MY": "Malaysia",
      "MV": "Maldives",
      "ML": "Mali",
      "MT": "Malta",
      "MH": "Marshall Islands",
      "MQ": "Martinique",
      "MR": "Mauritania",
      "MU": "Mauritius",
      "YT": "Mayotte",
      "MX": "Mexico",
      "FM": "Micronesia",
      "MD": "Moldova",
      "MC": "Monaco",
      "MN": "Mongolia",
      "ME": "Montenegro",
      "MS": "Montserrat",
      "MA": "Morocco",
      "MZ": "Mozambique",
      "MM": "Myanmar",
      "NA": "Namibia",
      "NR": "Nauru",
      "NP": "Nepal",
      "NL": "Netherlands",
      "NC": "New Caledonia",
      "NZ": "New Zealand",
      "NI": "Nicaragua",
      "NE": "Niger",
      "NG": "Nigeria",
      "NU": "Niue",
      "NF": "Norfolk Island",
      "KP": "North Korea",
      "MP": "Northern Mariana Islands",
      "NO": "Norway",
      "OM": "Oman",
      "PK": "Pakistan",
      "PS": "Palestinian Territory",
      "PA": "Panama",
      "PG": "Papua New Guinea",
      "PY": "Paraguay",
      "PE": "Peru",
      "PH": "Philippines",
      "PN": "Pitcairn",
      "PL": "Poland",
      "PT": "Portugal",
      "PR": "Puerto Rico",
      "QA": "Qatar",
      "RE": "Reunion",
      "RO": "Romania",
      "RU": "Russia",
      "RW": "Rwanda",
      "ST": "S&atilde;o Tom&eacute; and Pr&iacute;ncipe",
      "BL": "Saint Barth&eacute;lemy",
      "SH": "Saint Helena",
      "KN": "Saint Kitts and Nevis",
      "LC": "Saint Lucia",
      "SX": "Saint Martin (Dutch part)",
      "MF": "Saint Martin (French part)",
      "PM": "Saint Pierre and Miquelon",
      "VC": "Saint Vincent and the Grenadines",
      "WS": "Samoa",
      "SM": "San Marino",
      "SA": "Saudi Arabia",
      "SN": "Senegal",
      "RS": "Serbia",
      "SC": "Seychelles",
      "SL": "Sierra Leone",
      "SG": "Singapore",
      "SK": "Slovakia",
      "SI": "Slovenia",
      "SB": "Solomon Islands",
      "SO": "Somalia",
      "ZA": "South Africa",
      "GS": "South Georgia/Sandwich Islands",
      "KR": "South Korea",
      "SS": "South Sudan",
      "ES": "Spain",
      "LK": "Sri Lanka",
      "SD": "Sudan",
      "SR": "Suriname",
      "SJ": "Svalbard and Jan Mayen",
      "SZ": "Swaziland",
      "SE": "Sweden",
      "CH": "Switzerland",
      "SY": "Syria",
      "TW": "Taiwan",
      "TJ": "Tajikistan",
      "TZ": "Tanzania",
      "TH": "Thailand",
      "TL": "Timor-Leste",
      "TG": "Togo",
      "TK": "Tokelau",
      "TO": "Tonga",
      "TT": "Trinidad and Tobago",
      "TN": "Tunisia",
      "TR": "Turkey",
      "TM": "Turkmenistan",
      "TC": "Turks and Caicos Islands",
      "TV": "Tuvalu",
      "UG": "Uganda",
      "UA": "Ukraine",
      "AE": "United Arab Emirates",
      "GB": "United Kingdom (UK)",
      "US": "United States (US)",
      "UM": "United States (US) Minor Outlying Islands",
      "VI": "United States (US) Virgin Islands",
      "UY": "Uruguay",
      "UZ": "Uzbekistan",
      "VU": "Vanuatu",
      "VA": "Vatican",
      "VE": "Venezuela",
      "VN": "Vietnam",
      "WF": "Wallis and Futuna",
      "EH": "Western Sahara",
      "YE": "Yemen",
      "ZM": "Zambia",
      "ZW": "Zimbabwe"
    },
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_specific_ship_to_countries"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "woocommerce_default_customer_address",
    "label": "Default customer location",
    "description": "",
    "type": "select",
    "default": "geolocation",
    "options": {
      "": "No location by default",
      "base": "Shop base address",
      "geolocation": "Geolocate",
      "geolocation_ajax": "Geolocate (with page caching support)"
    },
    "tip": "This option determines a customers default location. The MaxMind GeoLite Database will be periodically downloaded to your wp-content directory if using geolocation.",
    "value": "geolocation",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_default_customer_address"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "woocommerce_calc_taxes",
    "label": "Enable taxes",
    "description": "Enable taxes and tax calculations",
    "type": "checkbox",
    "default": "no",
    "value": "yes",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_calc_taxes"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "woocommerce_demo_store",
    "label": "Store notice",
    "description": "Enable site-wide store notice text",
    "type": "checkbox",
    "default": "no",
    "value": "no",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_demo_store"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "woocommerce_demo_store_notice",
    "label": "Store notice text",
    "description": "",
    "type": "textarea",
    "default": "This is a demo store for testing purposes &mdash; no orders shall be fulfilled.",
    "value": "This is a demo store for testing purposes &mdash; no orders shall be fulfilled.",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_demo_store_notice"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "woocommerce_currency",
    "label": "Currency",
    "description": "This controls what currency prices are listed at in the catalog and which currency gateways will take payments in.",
    "type": "select",
    "default": "GBP",
    "options": {
      "AED": "United Arab Emirates dirham (&#x62f;.&#x625;)",
      "AFN": "Afghan afghani (&#x60b;)",
      "ALL": "Albanian lek (L)",
      "AMD": "Armenian dram (AMD)",
      "ANG": "Netherlands Antillean guilder (&fnof;)",
      "AOA": "Angolan kwanza (Kz)",
      "ARS": "Argentine peso (&#36;)",
      "AUD": "Australian dollar (&#36;)",
      "AWG": "Aruban florin (&fnof;)",
      "AZN": "Azerbaijani manat (AZN)",
      "BAM": "Bosnia and Herzegovina convertible mark (KM)",
      "BBD": "Barbadian dollar (&#36;)",
      "BDT": "Bangladeshi taka (&#2547;&nbsp;)",
      "BGN": "Bulgarian lev (&#1083;&#1074;.)",
      "BHD": "Bahraini dinar (.&#x62f;.&#x628;)",
      "BIF": "Burundian franc (Fr)",
      "BMD": "Bermudian dollar (&#36;)",
      "BND": "Brunei dollar (&#36;)",
      "BOB": "Bolivian boliviano (Bs.)",
      "BRL": "Brazilian real (&#82;&#36;)",
      "BSD": "Bahamian dollar (&#36;)",
      "BTC": "Bitcoin (&#3647;)",
      "BTN": "Bhutanese ngultrum (Nu.)",
      "BWP": "Botswana pula (P)",
      "BYR": "Belarusian ruble (Br)",
      "BZD": "Belize dollar (&#36;)",
      "CAD": "Canadian dollar (&#36;)",
      "CDF": "Congolese franc (Fr)",
      "CHF": "Swiss franc (&#67;&#72;&#70;)",
      "CLP": "Chilean peso (&#36;)",
      "CNY": "Chinese yuan (&yen;)",
      "COP": "Colombian peso (&#36;)",
      "CRC": "Costa Rican col&oacute;n (&#x20a1;)",
      "CUC": "Cuban convertible peso (&#36;)",
      "CUP": "Cuban peso (&#36;)",
      "CVE": "Cape Verdean escudo (&#36;)",
      "CZK": "Czech koruna (&#75;&#269;)",
      "DJF": "Djiboutian franc (Fr)",
      "DKK": "Danish krone (DKK)",
      "DOP": "Dominican peso (RD&#36;)",
      "DZD": "Algerian dinar (&#x62f;.&#x62c;)",
      "EGP": "Egyptian pound (EGP)",
      "ERN": "Eritrean nakfa (Nfk)",
      "ETB": "Ethiopian birr (Br)",
      "EUR": "Euro (&euro;)",
      "FJD": "Fijian dollar (&#36;)",
      "FKP": "Falkland Islands pound (&pound;)",
      "GBP": "Pound sterling (&pound;)",
      "GEL": "Georgian lari (&#x10da;)",
      "GGP": "Guernsey pound (&pound;)",
      "GHS": "Ghana cedi (&#x20b5;)",
      "GIP": "Gibraltar pound (&pound;)",
      "GMD": "Gambian dalasi (D)",
      "GNF": "Guinean franc (Fr)",
      "GTQ": "Guatemalan quetzal (Q)",
      "GYD": "Guyanese dollar (&#36;)",
      "HKD": "Hong Kong dollar (&#36;)",
      "HNL": "Honduran lempira (L)",
      "HRK": "Croatian kuna (Kn)",
      "HTG": "Haitian gourde (G)",
      "HUF": "Hungarian forint (&#70;&#116;)",
      "IDR": "Indonesian rupiah (Rp)",
      "ILS": "Israeli new shekel (&#8362;)",
      "IMP": "Manx pound (&pound;)",
      "INR": "Indian rupee (&#8377;)",
      "IQD": "Iraqi dinar (&#x639;.&#x62f;)",
      "IRR": "Iranian rial (&#xfdfc;)",
      "IRT": "Iranian toman (&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;)",
      "ISK": "Icelandic kr&oacute;na (kr.)",
      "JEP": "Jersey pound (&pound;)",
      "JMD": "Jamaican dollar (&#36;)",
      "JOD": "Jordanian dinar (&#x62f;.&#x627;)",
      "JPY": "Japanese yen (&yen;)",
      "KES": "Kenyan shilling (KSh)",
      "KGS": "Kyrgyzstani som (&#x441;&#x43e;&#x43c;)",
      "KHR": "Cambodian riel (&#x17db;)",
      "KMF": "Comorian franc (Fr)",
      "KPW": "North Korean won (&#x20a9;)",
      "KRW": "South Korean won (&#8361;)",
      "KWD": "Kuwaiti dinar (&#x62f;.&#x643;)",
      "KYD": "Cayman Islands dollar (&#36;)",
      "KZT": "Kazakhstani tenge (KZT)",
      "LAK": "Lao kip (&#8365;)",
      "LBP": "Lebanese pound (&#x644;.&#x644;)",
      "LKR": "Sri Lankan rupee (&#xdbb;&#xdd4;)",
      "LRD": "Liberian dollar (&#36;)",
      "LSL": "Lesotho loti (L)",
      "LYD": "Libyan dinar (&#x644;.&#x62f;)",
      "MAD": "Moroccan dirham (&#x62f;.&#x645;.)",
      "MDL": "Moldovan leu (MDL)",
      "MGA": "Malagasy ariary (Ar)",
      "MKD": "Macedonian denar (&#x434;&#x435;&#x43d;)",
      "MMK": "Burmese kyat (Ks)",
      "MNT": "Mongolian t&ouml;gr&ouml;g (&#x20ae;)",
      "MOP": "Macanese pataca (P)",
      "MRO": "Mauritanian ouguiya (UM)",
      "MUR": "Mauritian rupee (&#x20a8;)",
      "MVR": "Maldivian rufiyaa (.&#x783;)",
      "MWK": "Malawian kwacha (MK)",
      "MXN": "Mexican peso (&#36;)",
      "MYR": "Malaysian ringgit (&#82;&#77;)",
      "MZN": "Mozambican metical (MT)",
      "NAD": "Namibian dollar (&#36;)",
      "NGN": "Nigerian naira (&#8358;)",
      "NIO": "Nicaraguan c&oacute;rdoba (C&#36;)",
      "NOK": "Norwegian krone (&#107;&#114;)",
      "NPR": "Nepalese rupee (&#8360;)",
      "NZD": "New Zealand dollar (&#36;)",
      "OMR": "Omani rial (&#x631;.&#x639;.)",
      "PAB": "Panamanian balboa (B/.)",
      "PEN": "Peruvian nuevo sol (S/.)",
      "PGK": "Papua New Guinean kina (K)",
      "PHP": "Philippine peso (&#8369;)",
      "PKR": "Pakistani rupee (&#8360;)",
      "PLN": "Polish z&#x142;oty (&#122;&#322;)",
      "PRB": "Transnistrian ruble (&#x440;.)",
      "PYG": "Paraguayan guaran&iacute; (&#8370;)",
      "QAR": "Qatari riyal (&#x631;.&#x642;)",
      "RON": "Romanian leu (lei)",
      "RSD": "Serbian dinar (&#x434;&#x438;&#x43d;.)",
      "RUB": "Russian ruble (&#8381;)",
      "RWF": "Rwandan franc (Fr)",
      "SAR": "Saudi riyal (&#x631;.&#x633;)",
      "SBD": "Solomon Islands dollar (&#36;)",
      "SCR": "Seychellois rupee (&#x20a8;)",
      "SDG": "Sudanese pound (&#x62c;.&#x633;.)",
      "SEK": "Swedish krona (&#107;&#114;)",
      "SGD": "Singapore dollar (&#36;)",
      "SHP": "Saint Helena pound (&pound;)",
      "SLL": "Sierra Leonean leone (Le)",
      "SOS": "Somali shilling (Sh)",
      "SRD": "Surinamese dollar (&#36;)",
      "SSP": "South Sudanese pound (&pound;)",
      "STD": "S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra (Db)",
      "SYP": "Syrian pound (&#x644;.&#x633;)",
      "SZL": "Swazi lilangeni (L)",
      "THB": "Thai baht (&#3647;)",
      "TJS": "Tajikistani somoni (&#x405;&#x41c;)",
      "TMT": "Turkmenistan manat (m)",
      "TND": "Tunisian dinar (&#x62f;.&#x62a;)",
      "TOP": "Tongan pa&#x2bb;anga (T&#36;)",
      "TRY": "Turkish lira (&#8378;)",
      "TTD": "Trinidad and Tobago dollar (&#36;)",
      "TWD": "New Taiwan dollar (&#78;&#84;&#36;)",
      "TZS": "Tanzanian shilling (Sh)",
      "UAH": "Ukrainian hryvnia (&#8372;)",
      "UGX": "Ugandan shilling (UGX)",
      "USD": "United States dollar (&#36;)",
      "UYU": "Uruguayan peso (&#36;)",
      "UZS": "Uzbekistani som (UZS)",
      "VEF": "Venezuelan bol&iacute;var (Bs F)",
      "VND": "Vietnamese &#x111;&#x1ed3;ng (&#8363;)",
      "VUV": "Vanuatu vatu (Vt)",
      "WST": "Samoan t&#x101;l&#x101; (T)",
      "XAF": "Central African CFA franc (Fr)",
      "XCD": "East Caribbean dollar (&#36;)",
      "XOF": "West African CFA franc (Fr)",
      "XPF": "CFP franc (Fr)",
      "YER": "Yemeni rial (&#xfdfc;)",
      "ZAR": "South African rand (&#82;)",
      "ZMW": "Zambian kwacha (ZK)"
    },
    "tip": "This controls what currency prices are listed at in the catalog and which currency gateways will take payments in.",
    "value": "USD",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_currency"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "woocommerce_currency_pos",
    "label": "Currency position",
    "description": "This controls the position of the currency symbol.",
    "type": "select",
    "default": "left",
    "options": {
      "left": "Left (&#36;99.99)",
      "right": "Right (99.99&#36;)",
      "left_space": "Left with space (&#36; 99.99)",
      "right_space": "Right with space (99.99 &#36;)"
    },
    "tip": "This controls the position of the currency symbol.",
    "value": "left",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_currency_pos"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "woocommerce_price_thousand_sep",
    "label": "Thousand separator",
    "description": "This sets the thousand separator of displayed prices.",
    "type": "text",
    "default": ",",
    "tip": "This sets the thousand separator of displayed prices.",
    "value": ",",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_price_thousand_sep"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "woocommerce_price_decimal_sep",
    "label": "Decimal separator",
    "description": "This sets the decimal separator of displayed prices.",
    "type": "text",
    "default": ".",
    "tip": "This sets the decimal separator of displayed prices.",
    "value": ".",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_price_decimal_sep"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  },
  {
    "id": "woocommerce_price_num_decimals",
    "label": "Number of decimals",
    "description": "This sets the number of decimal points shown in displayed prices.",
    "type": "number",
    "default": "2",
    "tip": "This sets the number of decimal points shown in displayed prices.",
    "value": "2",
    "_links": {
      "self": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_price_num_decimals"
        }
      ],
      "collection": [
        {
          "href": "https://example.com/wp-json/wc/v3/settings/general"
        }
      ]
    }
  }
]
```

## Update a setting option ##

This API lets you make changes to a setting option.

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-put">PUT</i>
		<h6>/wp-json/wc/v3/settings/&lt;group_id&gt;/&lt;id&gt;</h6>
	</div>
</div>

```shell
curl -X PUT https://example.com/wp-json/wc/v3/settings/general/woocommerce_allowed_countries \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "value": "all_except"
}'
```

```javascript
const data = {
  value: "all_except"
};

WooCommerce.put("settings/general/woocommerce_allowed_countries", data)
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php 
$data = [
    'value' => 'all_except'
];

print_r($woocommerce->put('settings/general/woocommerce_allowed_countries', $data));
?>
```

```python
data = {
    "value": "all_except"
}

print(wcapi.put("settings/general/woocommerce_allowed_countries", data).json())
```

```ruby
data = {
  value: "all_except"
}

woocommerce.put("settings/general/woocommerce_allowed_countries", data).parsed_response
```

> JSON response example:

```json
{
  "id": "woocommerce_allowed_countries",
  "label": "Selling location(s)",
  "description": "This option lets you limit which countries you are willing to sell to.",
  "type": "select",
  "default": "all",
  "options": {
    "all": "Sell to all countries",
    "all_except": "Sell to all countries, except for&hellip;",
    "specific": "Sell to specific countries"
  },
  "tip": "This option lets you limit which countries you are willing to sell to.",
  "value": "all_except",
  "_links": {
    "self": [
      {
        "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_allowed_countries"
      }
    ],
    "collection": [
      {
        "href": "https://example.com/wp-json/wc/v3/settings/general"
      }
    ]
  }
}
```

## Batch update setting options ##

This API helps you to batch update multiple setting options.

<aside class="notice">
Note: By default it's limited to up to 100 objects to be created, updated or deleted. 
</aside>

### HTTP request ###

<div class="api-endpoint">
	<div class="endpoint-data">
		<i class="label label-post">POST</i>
		<h6>/wp-json/wc/v3/settings/&lt;id&gt;/batch</h6>
	</div>
</div>

```shell
curl -X POST https://example.com/wp-json/wc/v3/settings/general/batch \
	-u consumer_key:consumer_secret \
	-H "Content-Type: application/json" \
	-d '{
  "update": [
    {
      "id": "woocommerce_allowed_countries",
      "value": "all"
    },
    {
      "id": "woocommerce_demo_store",
      "value": "yes"
    },
    {
      "id": "woocommerce_currency",
      "value": "GBP"
    }
  ]
}'
```

```javascript
const data = {
  create: [
    {
      regular_price: "10.00",
      attributes: [
        {
          id: 6,
          option: "Blue"
        }
      ]
    },
    {
      regular_price: "10.00",
      attributes: [
        {
          id: 6,
          option: "White"
        }
      ]
    }
  ],
  update: [
    {
      id: 733,
      regular_price: "10.00"
    }
  ],
  delete: [
    732
  ]
};

WooCommerce.post("products/22/settings/general/batch", data)
  .then((response) => {
    console.log(response.data);
  })
  .catch((error) => {
    console.log(error.response.data);
  });
```

```php
<?php
$data = [
    'create' => [
        [
            'regular_price' => '10.00',
            'attributes' => [
                [
                    'id' => 6,
                    'option' => 'Blue'
                ]
            ]
        ],
        [
            'regular_price' => '10.00',
            'attributes' => [
                [
                    'id' => 6,
                    'option' => 'White'
                ]
            ]
        ]
    ],
    'update' => [
        [
            'id' => 733,
            'regular_price' => '10.00'
        ]
    ],
    'delete' => [
        732
    ]
];

print_r($woocommerce->post('products/22/settings/general/batch', $data));
?>
```

```python
data = {
    "create": [
        {
            "regular_price": "10.00",
            "attributes": [
                {
                    "id": 6,
                    "option": "Blue"
                }
            ]
        },
        {
            "regular_price": "10.00",
            "attributes": [
                {
                    "id": 6,
                    "option": "White"
                }
            ]
        }
    ],
    "update": [
        {
            "id": 733,
            "regular_price": "10.00"
        }
    ],
    "delete": [
        732
    ]
}

print(wcapi.post("products/22/settings/general/batch", data).json())
```

```ruby
data = {
  create: [
    {
      regular_price: "10.00",
      attributes: [
        {
          id: 6,
          option: "Blue"
        }
      ]
    },
    {
      regular_price: "10.00",
      attributes: [
        {
          id: 6,
          option: "White"
        }
      ]
    }
  ],
  update: [
    {
      id: 733,
      regular_price: "10.00"
    }
  ],
  delete: [
    732
  ]
}

woocommerce.post("products/22/settings/general/batch", data).parsed_response
```

> JSON response example:

```json
{
  "update": [
    {
      "id": "woocommerce_allowed_countries",
      "label": "Selling location(s)",
      "description": "This option lets you limit which countries you are willing to sell to.",
      "type": "select",
      "default": "all",
      "options": {
        "all": "Sell to all countries",
        "all_except": "Sell to all countries, except for&hellip;",
        "specific": "Sell to specific countries"
      },
      "tip": "This option lets you limit which countries you are willing to sell to.",
      "value": "all",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_allowed_countries"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/settings/general"
          }
        ]
      }
    },
    {
      "id": "woocommerce_demo_store",
      "label": "Store notice",
      "description": "Enable site-wide store notice text",
      "type": "checkbox",
      "default": "no",
      "value": "yes",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_demo_store"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/settings/general"
          }
        ]
      }
    },
    {
      "id": "woocommerce_currency",
      "label": "Currency",
      "description": "This controls what currency prices are listed at in the catalog and which currency gateways will take payments in.",
      "type": "select",
      "default": "GBP",
      "options": {
        "AED": "United Arab Emirates dirham (&#x62f;.&#x625;)",
        "AFN": "Afghan afghani (&#x60b;)",
        "ALL": "Albanian lek (L)",
        "AMD": "Armenian dram (AMD)",
        "ANG": "Netherlands Antillean guilder (&fnof;)",
        "AOA": "Angolan kwanza (Kz)",
        "ARS": "Argentine peso (&#36;)",
        "AUD": "Australian dollar (&#36;)",
        "AWG": "Aruban florin (&fnof;)",
        "AZN": "Azerbaijani manat (AZN)",
        "BAM": "Bosnia and Herzegovina convertible mark (KM)",
        "BBD": "Barbadian dollar (&#36;)",
        "BDT": "Bangladeshi taka (&#2547;&nbsp;)",
        "BGN": "Bulgarian lev (&#1083;&#1074;.)",
        "BHD": "Bahraini dinar (.&#x62f;.&#x628;)",
        "BIF": "Burundian franc (Fr)",
        "BMD": "Bermudian dollar (&#36;)",
        "BND": "Brunei dollar (&#36;)",
        "BOB": "Bolivian boliviano (Bs.)",
        "BRL": "Brazilian real (&#82;&#36;)",
        "BSD": "Bahamian dollar (&#36;)",
        "BTC": "Bitcoin (&#3647;)",
        "BTN": "Bhutanese ngultrum (Nu.)",
        "BWP": "Botswana pula (P)",
        "BYR": "Belarusian ruble (Br)",
        "BZD": "Belize dollar (&#36;)",
        "CAD": "Canadian dollar (&#36;)",
        "CDF": "Congolese franc (Fr)",
        "CHF": "Swiss franc (&#67;&#72;&#70;)",
        "CLP": "Chilean peso (&#36;)",
        "CNY": "Chinese yuan (&yen;)",
        "COP": "Colombian peso (&#36;)",
        "CRC": "Costa Rican col&oacute;n (&#x20a1;)",
        "CUC": "Cuban convertible peso (&#36;)",
        "CUP": "Cuban peso (&#36;)",
        "CVE": "Cape Verdean escudo (&#36;)",
        "CZK": "Czech koruna (&#75;&#269;)",
        "DJF": "Djiboutian franc (Fr)",
        "DKK": "Danish krone (DKK)",
        "DOP": "Dominican peso (RD&#36;)",
        "DZD": "Algerian dinar (&#x62f;.&#x62c;)",
        "EGP": "Egyptian pound (EGP)",
        "ERN": "Eritrean nakfa (Nfk)",
        "ETB": "Ethiopian birr (Br)",
        "EUR": "Euro (&euro;)",
        "FJD": "Fijian dollar (&#36;)",
        "FKP": "Falkland Islands pound (&pound;)",
        "GBP": "Pound sterling (&pound;)",
        "GEL": "Georgian lari (&#x10da;)",
        "GGP": "Guernsey pound (&pound;)",
        "GHS": "Ghana cedi (&#x20b5;)",
        "GIP": "Gibraltar pound (&pound;)",
        "GMD": "Gambian dalasi (D)",
        "GNF": "Guinean franc (Fr)",
        "GTQ": "Guatemalan quetzal (Q)",
        "GYD": "Guyanese dollar (&#36;)",
        "HKD": "Hong Kong dollar (&#36;)",
        "HNL": "Honduran lempira (L)",
        "HRK": "Croatian kuna (Kn)",
        "HTG": "Haitian gourde (G)",
        "HUF": "Hungarian forint (&#70;&#116;)",
        "IDR": "Indonesian rupiah (Rp)",
        "ILS": "Israeli new shekel (&#8362;)",
        "IMP": "Manx pound (&pound;)",
        "INR": "Indian rupee (&#8377;)",
        "IQD": "Iraqi dinar (&#x639;.&#x62f;)",
        "IRR": "Iranian rial (&#xfdfc;)",
        "IRT": "Iranian toman (&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;)",
        "ISK": "Icelandic kr&oacute;na (kr.)",
        "JEP": "Jersey pound (&pound;)",
        "JMD": "Jamaican dollar (&#36;)",
        "JOD": "Jordanian dinar (&#x62f;.&#x627;)",
        "JPY": "Japanese yen (&yen;)",
        "KES": "Kenyan shilling (KSh)",
        "KGS": "Kyrgyzstani som (&#x441;&#x43e;&#x43c;)",
        "KHR": "Cambodian riel (&#x17db;)",
        "KMF": "Comorian franc (Fr)",
        "KPW": "North Korean won (&#x20a9;)",
        "KRW": "South Korean won (&#8361;)",
        "KWD": "Kuwaiti dinar (&#x62f;.&#x643;)",
        "KYD": "Cayman Islands dollar (&#36;)",
        "KZT": "Kazakhstani tenge (KZT)",
        "LAK": "Lao kip (&#8365;)",
        "LBP": "Lebanese pound (&#x644;.&#x644;)",
        "LKR": "Sri Lankan rupee (&#xdbb;&#xdd4;)",
        "LRD": "Liberian dollar (&#36;)",
        "LSL": "Lesotho loti (L)",
        "LYD": "Libyan dinar (&#x644;.&#x62f;)",
        "MAD": "Moroccan dirham (&#x62f;.&#x645;.)",
        "MDL": "Moldovan leu (MDL)",
        "MGA": "Malagasy ariary (Ar)",
        "MKD": "Macedonian denar (&#x434;&#x435;&#x43d;)",
        "MMK": "Burmese kyat (Ks)",
        "MNT": "Mongolian t&ouml;gr&ouml;g (&#x20ae;)",
        "MOP": "Macanese pataca (P)",
        "MRO": "Mauritanian ouguiya (UM)",
        "MUR": "Mauritian rupee (&#x20a8;)",
        "MVR": "Maldivian rufiyaa (.&#x783;)",
        "MWK": "Malawian kwacha (MK)",
        "MXN": "Mexican peso (&#36;)",
        "MYR": "Malaysian ringgit (&#82;&#77;)",
        "MZN": "Mozambican metical (MT)",
        "NAD": "Namibian dollar (&#36;)",
        "NGN": "Nigerian naira (&#8358;)",
        "NIO": "Nicaraguan c&oacute;rdoba (C&#36;)",
        "NOK": "Norwegian krone (&#107;&#114;)",
        "NPR": "Nepalese rupee (&#8360;)",
        "NZD": "New Zealand dollar (&#36;)",
        "OMR": "Omani rial (&#x631;.&#x639;.)",
        "PAB": "Panamanian balboa (B/.)",
        "PEN": "Peruvian nuevo sol (S/.)",
        "PGK": "Papua New Guinean kina (K)",
        "PHP": "Philippine peso (&#8369;)",
        "PKR": "Pakistani rupee (&#8360;)",
        "PLN": "Polish z&#x142;oty (&#122;&#322;)",
        "PRB": "Transnistrian ruble (&#x440;.)",
        "PYG": "Paraguayan guaran&iacute; (&#8370;)",
        "QAR": "Qatari riyal (&#x631;.&#x642;)",
        "RON": "Romanian leu (lei)",
        "RSD": "Serbian dinar (&#x434;&#x438;&#x43d;.)",
        "RUB": "Russian ruble (&#8381;)",
        "RWF": "Rwandan franc (Fr)",
        "SAR": "Saudi riyal (&#x631;.&#x633;)",
        "SBD": "Solomon Islands dollar (&#36;)",
        "SCR": "Seychellois rupee (&#x20a8;)",
        "SDG": "Sudanese pound (&#x62c;.&#x633;.)",
        "SEK": "Swedish krona (&#107;&#114;)",
        "SGD": "Singapore dollar (&#36;)",
        "SHP": "Saint Helena pound (&pound;)",
        "SLL": "Sierra Leonean leone (Le)",
        "SOS": "Somali shilling (Sh)",
        "SRD": "Surinamese dollar (&#36;)",
        "SSP": "South Sudanese pound (&pound;)",
        "STD": "S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra (Db)",
        "SYP": "Syrian pound (&#x644;.&#x633;)",
        "SZL": "Swazi lilangeni (L)",
        "THB": "Thai baht (&#3647;)",
        "TJS": "Tajikistani somoni (&#x405;&#x41c;)",
        "TMT": "Turkmenistan manat (m)",
        "TND": "Tunisian dinar (&#x62f;.&#x62a;)",
        "TOP": "Tongan pa&#x2bb;anga (T&#36;)",
        "TRY": "Turkish lira (&#8378;)",
        "TTD": "Trinidad and Tobago dollar (&#36;)",
        "TWD": "New Taiwan dollar (&#78;&#84;&#36;)",
        "TZS": "Tanzanian shilling (Sh)",
        "UAH": "Ukrainian hryvnia (&#8372;)",
        "UGX": "Ugandan shilling (UGX)",
        "USD": "United States dollar (&#36;)",
        "UYU": "Uruguayan peso (&#36;)",
        "UZS": "Uzbekistani som (UZS)",
        "VEF": "Venezuelan bol&iacute;var (Bs F)",
        "VND": "Vietnamese &#x111;&#x1ed3;ng (&#8363;)",
        "VUV": "Vanuatu vatu (Vt)",
        "WST": "Samoan t&#x101;l&#x101; (T)",
        "XAF": "Central African CFA franc (Fr)",
        "XCD": "East Caribbean dollar (&#36;)",
        "XOF": "West African CFA franc (Fr)",
        "XPF": "CFP franc (Fr)",
        "YER": "Yemeni rial (&#xfdfc;)",
        "ZAR": "South African rand (&#82;)",
        "ZMW": "Zambian kwacha (ZK)"
      },
      "tip": "This controls what currency prices are listed at in the catalog and which currency gateways will take payments in.",
      "value": "GBP",
      "_links": {
        "self": [
          {
            "href": "https://example.com/wp-json/wc/v3/settings/general/woocommerce_currency"
          }
        ],
        "collection": [
          {
            "href": "https://example.com/wp-json/wc/v3/settings/general"
          }
        ]
      }
    }
  ]
}
```
