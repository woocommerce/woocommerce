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

describe( 'addCesSurvey', () => {
	it.each( [ 'onSubmitLabel', 'onsubmitLabel', 'onsubmit_label' ] )(
		'normalizes the %s input field to onSubmitLabel',
		( inputField ) => {
			const action = addCesSurvey( {
				...survey,
				[ inputField ]: 'Share feedback',
			} );

			expect( action ).toEqual( {
				type: TYPES.ADD_CES_SURVEY,
				...survey,
				onSubmitLabel: 'Share feedback',
			} );
			expect( action ).not.toHaveProperty( 'onsubmitLabel' );
			expect( action ).not.toHaveProperty( 'onsubmit_label' );
		}
	);

	it.each( [
		{
			caseName: 'prefers a non-nullish canonical value',
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
			caseName: 'falls through a null canonical value',
			labels: {
				onSubmitLabel: null,
				onsubmitLabel: 'Lower-camel label',
				onsubmit_label: 'Snake-case label',
			},
			expected: 'Lower-camel label',
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
			caseName: 'falls through null canonical and lower-camel values',
			labels: {
				onSubmitLabel: null,
				onsubmitLabel: null,
				onsubmit_label: 'Snake-case label',
			},
			expected: 'Snake-case label',
		},
	] )( '$caseName', ( { labels, expected } ) => {
		const action = addCesSurvey( { ...survey, ...labels } );

		expect( action.onSubmitLabel ).toBe( expected );
	} );

	it( 'includes an undefined canonical label when no label field is present', () => {
		const action = addCesSurvey( survey );

		expect( action ).toHaveProperty( 'onSubmitLabel', undefined );
	} );
} );

describe( 'showCesModal', () => {
	const props = { productType: 'simple' };
	const onSubmitNoticeProps = { type: 'success' };
	const tracksProps = { source: 'product-editor' };

	it.each( [ 'onSubmitLabel', 'onsubmitLabel', 'onsubmit_label' ] )(
		'normalizes the %s survey field to onSubmitLabel',
		( inputField ) => {
			const surveyProps = {
				...survey,
				[ inputField ]: 'Share feedback',
			};

			const action = showCesModal(
				surveyProps,
				props,
				onSubmitNoticeProps,
				tracksProps
			);

			expect( action ).toEqual( {
				type: TYPES.SHOW_CES_MODAL,
				surveyProps,
				onSubmitLabel: 'Share feedback',
				props,
				onSubmitNoticeProps,
				tracksProps,
			} );
			expect( action ).not.toHaveProperty( 'onsubmitLabel' );
			expect( action ).not.toHaveProperty( 'onsubmit_label' );
		}
	);

	it.each( [
		{
			caseName: 'prefers a non-nullish canonical value',
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
			caseName: 'falls through a null canonical value',
			labels: {
				onSubmitLabel: null,
				onsubmitLabel: 'Lower-camel label',
				onsubmit_label: 'Snake-case label',
			},
			expected: 'Lower-camel label',
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
			caseName: 'falls through null canonical and lower-camel values',
			labels: {
				onSubmitLabel: null,
				onsubmitLabel: null,
				onsubmit_label: 'Snake-case label',
			},
			expected: 'Snake-case label',
		},
	] )( '$caseName', ( { labels, expected } ) => {
		const action = showCesModal( { ...survey, ...labels } );

		expect( action.onSubmitLabel ).toBe( expected );
	} );

	it( 'uses an empty canonical label when no label field is present', () => {
		const action = showCesModal( survey );

		expect( action.onSubmitLabel ).toBe( '' );
	} );
} );
