/**
 * Internal dependencies
 */
import TYPES from '../action-types';
import { addCesSurvey, showCesModal } from '../actions';

const survey = {
	action: 'save_product',
	title: 'How easy was it to save this product?',
	description: 'Tell us about your experience.',
	noticeLabel: 'Product saved',
	firstQuestion: 'Saving this product was easy.',
	secondQuestion: 'The save flow met my needs.',
	icon: 'product',
	pageNow: 'product',
	adminPage: 'post-php',
	props: { productType: 'simple' },
};

const actionCreators = [
	{
		name: 'addCesSurvey',
		type: TYPES.ADD_CES_SURVEY,
		create: ( labels ) => addCesSurvey( { ...survey, ...labels } ),
		defaultLabel: undefined,
	},
	{
		name: 'showCesModal',
		type: TYPES.SHOW_CES_MODAL,
		create: ( labels ) => showCesModal( { ...survey, ...labels } ),
		defaultLabel: '',
	},
];

const precedenceCases = [
	{
		caseName: 'prefers the canonical value',
		labels: {
			onSubmitLabel: 'Canonical label',
			onsubmitLabel: 'Lower-camel label',
			onsubmit_label: 'Snake-case label',
		},
		expected: 'Canonical label',
	},
	{
		caseName: 'keeps an empty canonical value',
		labels: {
			onSubmitLabel: '',
			onsubmitLabel: 'Lower-camel label',
			onsubmit_label: 'Snake-case label',
		},
		expected: '',
	},
	{
		caseName: 'keeps an empty lower-camel value',
		labels: {
			onSubmitLabel: null,
			onsubmitLabel: '',
			onsubmit_label: 'Snake-case label',
		},
		expected: '',
	},
	{
		caseName: 'falls through nullish values',
		labels: {
			onSubmitLabel: null,
			onsubmitLabel: null,
			onsubmit_label: 'Snake-case label',
		},
		expected: 'Snake-case label',
	},
];

describe.each( actionCreators )(
	'$name',
	( { create, defaultLabel, type } ) => {
		it.each( [ 'onSubmitLabel', 'onsubmitLabel', 'onsubmit_label' ] )(
			'normalizes the %s input field to onSubmitLabel',
			( inputField ) => {
				const action = create( { [ inputField ]: 'Share feedback' } );

				expect( action ).toMatchObject( {
					type,
					onSubmitLabel: 'Share feedback',
				} );
				expect( action ).not.toHaveProperty( 'onsubmitLabel' );
				expect( action ).not.toHaveProperty( 'onsubmit_label' );
			}
		);

		it.each( precedenceCases )( '$caseName', ( { labels, expected } ) => {
			expect( create( labels ).onSubmitLabel ).toBe( expected );
		} );

		it( 'uses the expected value when no label field is present', () => {
			expect( create( {} ) ).toHaveProperty(
				'onSubmitLabel',
				defaultLabel
			);
		} );
	}
);
