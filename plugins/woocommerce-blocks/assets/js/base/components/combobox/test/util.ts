/**
 * Internal dependencies
 */
import {
	findBestMatchByLabel,
	findExactMatchByLabel,
	findMatchingSuggestions,
} from '../util';

export const OPTIONS = [
	{
		value: 'DZ-01',
		label: 'Adrar',
	},
	{
		value: 'DZ-44',
		label: 'Aïn Defla',
	},
	{
		value: 'DZ-46',
		label: 'Aïn Témouchent',
	},
	{
		value: 'DZ-16',
		label: 'Algiers',
	},
	{
		value: 'DZ-23',
		label: 'Annaba',
	},
	{
		value: 'DZ-05',
		label: 'Batna',
	},
	{
		value: 'DZ-08',
		label: 'Béchar',
	},
	{
		value: 'DZ-06',
		label: 'Béjaïa',
	},
	{
		value: 'DZ-07',
		label: 'Biskra',
	},
	{
		value: 'DZ-09',
		label: 'Blida',
	},
	{
		value: 'DZ-34',
		label: 'Bordj Bou Arréridj',
	},
	{
		value: 'DZ-10',
		label: 'Bouira',
	},
	{
		value: 'DZ-35',
		label: 'Boumerdès',
	},
	{
		value: 'DZ-02',
		label: 'Chlef',
	},
	{
		value: 'DZ-25',
		label: 'Constantine',
	},
	{
		value: 'DZ-17',
		label: 'Djelfa',
	},
	{
		value: 'DZ-32',
		label: 'El Bayadh',
	},
	{
		value: 'DZ-39',
		label: 'El Oued',
	},
	{
		value: 'DZ-36',
		label: 'El Tarf',
	},
	{
		value: 'DZ-47',
		label: 'Ghardaïa',
	},
	{
		value: 'DZ-24',
		label: 'Guelma',
	},
	{
		value: 'DZ-33',
		label: 'Illizi',
	},
	{
		value: 'DZ-18',
		label: 'Jijel',
	},
	{
		value: 'DZ-40',
		label: 'Khenchela',
	},
	{
		value: 'DZ-03',
		label: 'Laghouat',
	},
	{
		value: 'DZ-29',
		label: 'Mascara',
	},
	{
		value: 'DZ-26',
		label: 'Médéa',
	},
	{
		value: 'DZ-43',
		label: 'Mila',
	},
	{
		value: 'DZ-27',
		label: 'Mostaganem',
	},
	{
		value: 'DZ-28',
		label: 'M’Sila',
	},
	{
		value: 'DZ-45',
		label: 'Naama',
	},
	{
		value: 'DZ-31',
		label: 'Oran',
	},
	{
		value: 'DZ-30',
		label: 'Ouargla',
	},
	{
		value: 'DZ-04',
		label: 'Oum El Bouaghi',
	},
	{
		value: 'DZ-48',
		label: 'Relizane',
	},
	{
		value: 'DZ-20',
		label: 'Saïda',
	},
	{
		value: 'DZ-19',
		label: 'Sétif',
	},
	{
		value: 'DZ-22',
		label: 'Sidi Bel Abbès',
	},
	{
		value: 'DZ-21',
		label: 'Skikda',
	},
	{
		value: 'DZ-41',
		label: 'Souk Ahras',
	},
	{
		value: 'DZ-11',
		label: 'Tamanghasset',
	},
	{
		value: 'DZ-12',
		label: 'Tébessa',
	},
	{
		value: 'DZ-14',
		label: 'Tiaret',
	},
	{
		value: 'DZ-37',
		label: 'Tindouf',
	},
	{
		value: 'DZ-42',
		label: 'Tipasa',
	},
	{
		value: 'DZ-38',
		label: 'Tissemsilt',
	},
	{
		value: 'DZ-15',
		label: 'Tizi Ouzou',
	},
	{
		value: 'DZ-13',
		label: 'Tlemcen',
	},
];

describe( 'Combobox option matching utilities', () => {
	describe( 'findExactMatchByLabel', () => {
		test( 'When providing a search term that is an exact label match it returns one item', () => {
			const result = findExactMatchByLabel( 'Algiers', OPTIONS );
			expect( result ).toEqual( {
				value: 'DZ-16',
				label: 'Algiers',
			} );
		} );

		test( 'When providing a search term that is a non-exact label match it returns no match', () => {
			const result = findExactMatchByLabel( 'Algier', OPTIONS );
			expect( result ).toBeUndefined();
		} );

		test( 'It makes an exact match even when the option has special characters', () => {
			const result = findExactMatchByLabel( 'Ain Defla', OPTIONS );
			expect( result ).toEqual( {
				value: 'DZ-44',
				label: 'Aïn Defla',
			} );
		} );
	} );

	describe( 'findBestMatchByLabel', () => {
		test( 'When providing a search term and there is a label that starts with those characters it returns the match', () => {
			const result = findBestMatchByLabel( 'Tia', OPTIONS );
			expect( result ).toEqual( {
				value: 'DZ-14',
				label: 'Tiaret',
			} );
		} );

		test( 'When providing a search term it matches when there are special characters', () => {
			const result = findBestMatchByLabel( 'Set', OPTIONS );
			expect( result ).toEqual( {
				value: 'DZ-19',
				label: 'Sétif',
			} );
		} );

		test( 'Empty strings always return no match', () => {
			const result = findBestMatchByLabel( '', OPTIONS );
			expect( result ).toBeUndefined();
		} );

		test( 'When there is no label starting with search term no match is returned', () => {
			const result = findBestMatchByLabel( 'Zz', OPTIONS );
			expect( result ).toBeUndefined();
		} );
	} );

	describe( 'findMatchingSuggestions', () => {
		test( 'When there is only one match for the search term and it is exactly the same, no results are returned', () => {
			const result = findMatchingSuggestions( 'Algiers', OPTIONS );
			expect( result ).toEqual( [] );
		} );

		test( 'Each option that contains the search term is returned as a matching result', () => {
			const result = findMatchingSuggestions( 'El', OPTIONS );
			expect( result ).toEqual( [
				{
					value: 'DZ-32',
					label: 'El Bayadh',
				},
				{
					value: 'DZ-39',
					label: 'El Oued',
				},
				{
					value: 'DZ-36',
					label: 'El Tarf',
				},
				{
					label: 'Djelfa',
					value: 'DZ-17',
				},
				{
					label: 'Guelma',
					value: 'DZ-24',
				},
				{
					label: 'Jijel',
					value: 'DZ-18',
				},
				{
					label: 'Khenchela',
					value: 'DZ-40',
				},
				{
					label: 'Oum El Bouaghi',
					value: 'DZ-04',
				},
				{
					label: 'Relizane',
					value: 'DZ-48',
				},
				{
					label: 'Sidi Bel Abbès',
					value: 'DZ-22',
				},
			] );
		} );

		test( 'If no options have a label containing the search term, no results are returned', () => {
			const result = findMatchingSuggestions( 'Zz', OPTIONS );
			expect( result ).toEqual( [] );
		} );

		test( 'When providing a search term, it matches all results with special characters', () => {
			const result = findMatchingSuggestions( 'Ai', OPTIONS );
			expect( result ).toEqual( [
				{
					value: 'DZ-44',
					label: 'Aïn Defla',
				},
				{
					value: 'DZ-46',
					label: 'Aïn Témouchent',
				},
				{
					label: 'Béjaïa',
					value: 'DZ-06',
				},
				{
					label: 'Ghardaïa',
					value: 'DZ-47',
				},
				{
					label: 'Saïda',
					value: 'DZ-20',
				},
			] );
		} );
	} );
} );
