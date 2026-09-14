/**
 * Internal dependencies
 */
import TYPES from '../action-types';
import reducer from '../reducer';

const surveyAction = {
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

const reducerCases = [
	{
		name: 'ADD_CES_SURVEY',
		createAction: ( labels ) => ( {
			type: TYPES.ADD_CES_SURVEY,
			...surveyAction,
			...labels,
		} ),
		getLabel: ( state ) => state.queue[ 0 ].onSubmitLabel,
	},
	{
		name: 'SHOW_CES_MODAL',
		createAction: ( labels ) => ( {
			type: TYPES.SHOW_CES_MODAL,
			surveyProps: surveyAction,
			props: surveyAction.props,
			onSubmitNoticeProps: { type: 'success' },
			tracksProps: { source: 'product-editor' },
			...labels,
		} ),
		getLabel: ( state ) => state.cesModalData.onSubmitLabel,
	},
];

const precedenceCases = [
	{
		caseName: 'prefers the canonical action field',
		labels: {
			onSubmitLabel: 'Canonical label',
			onsubmitLabel: 'Lower-camel label',
			onsubmit_label: 'Snake-case label',
		},
		expected: 'Canonical label',
	},
	{
		caseName: 'keeps an empty canonical action field',
		labels: {
			onSubmitLabel: '',
			onsubmitLabel: 'Lower-camel label',
			onsubmit_label: 'Snake-case label',
		},
		expected: '',
	},
	{
		caseName: 'falls through a null canonical action field',
		labels: {
			onSubmitLabel: null,
			onsubmitLabel: 'Lower-camel label',
			onsubmit_label: 'Snake-case label',
		},
		expected: 'Lower-camel label',
	},
	{
		caseName: 'keeps an empty lower-camel action field',
		labels: {
			onSubmitLabel: null,
			onsubmitLabel: '',
			onsubmit_label: 'Snake-case label',
		},
		expected: '',
	},
];

describe( 'customer effort score reducer', () => {
	describe.each( reducerCases )( '$name', ( { createAction, getLabel } ) => {
		it.each( [ 'onSubmitLabel', 'onsubmitLabel', 'onsubmit_label' ] )(
			'keeps a custom label from the %s action field',
			( inputField ) => {
				const state = reducer(
					undefined,
					createAction( { [ inputField ]: 'Share feedback' } )
				);

				expect( getLabel( state ) ).toBe( 'Share feedback' );
			}
		);

		it.each( precedenceCases )( '$caseName', ( { labels, expected } ) => {
			const state = reducer( undefined, createAction( labels ) );

			expect( getLabel( state ) ).toBe( expected );
		} );
	} );

	it( 'leaves the queue unchanged for a duplicate survey', () => {
		const initialState = {
			queue: [
				{ action: 'save_product', onSubmitLabel: 'Save label' },
				{
					action: 'publish_product',
					onSubmitLabel: 'Publish label',
				},
			],
			cesModalData: undefined,
			showCESModal: false,
			showProductMVPFeedbackModal: false,
		};

		const state = reducer( initialState, {
			type: TYPES.ADD_CES_SURVEY,
			...surveyAction,
			onSubmitLabel: 'Replacement label',
		} );

		expect( state ).toBe( initialState );
	} );

	it( 'normalizes persisted queue labels without changing legacy data or order', () => {
		const existingItem = Object.freeze( {
			id: 'existing',
			action: 'existing_survey',
			onSubmitLabel: 'Existing label',
		} );
		const queue = Object.freeze( [
			Object.freeze( {
				id: 'canonical',
				action: 'canonical_survey',
				onSubmitLabel: 'Canonical label',
				onsubmitLabel: 'Lower-camel label',
				onsubmit_label: 'Snake-case label',
				unrelated: Object.freeze( { source: 'canonical' } ),
			} ),
			Object.freeze( {
				id: 'lower-camel',
				action: 'lower_camel_survey',
				onsubmitLabel: 'Lower-camel label',
				onsubmit_label: 'Snake-case label',
			} ),
			Object.freeze( {
				id: 'snake-case',
				action: 'snake_case_survey',
				onsubmit_label: 'Snake-case label',
			} ),
			Object.freeze( {
				id: 'no-label',
				action: 'default_label_survey',
			} ),
		] );
		const initialState = Object.freeze( {
			queue: Object.freeze( [ existingItem ] ),
			cesModalData: undefined,
			showCESModal: false,
			showProductMVPFeedbackModal: false,
		} );
		const action = Object.freeze( {
			type: TYPES.SET_CES_SURVEY_QUEUE,
			queue,
		} );

		const state = reducer( initialState, action );

		expect( state.queue ).toEqual( [
			existingItem,
			{
				...queue[ 0 ],
				onSubmitLabel: 'Canonical label',
			},
			{
				...queue[ 1 ],
				onSubmitLabel: 'Lower-camel label',
			},
			{
				...queue[ 2 ],
				onSubmitLabel: 'Snake-case label',
			},
			{
				...queue[ 3 ],
				onSubmitLabel: undefined,
			},
		] );
		expect( state.queue.map( ( item ) => item.id ) ).toEqual( [
			'existing',
			'canonical',
			'lower-camel',
			'snake-case',
			'no-label',
		] );
		expect( state.queue[ 0 ] ).toBe( existingItem );
		expect( state.queue[ 1 ] ).not.toBe( queue[ 0 ] );
		expect( state.queue[ 2 ] ).not.toBe( queue[ 1 ] );
		expect( state.queue[ 3 ] ).not.toBe( queue[ 2 ] );
		expect( state.queue[ 4 ] ).not.toBe( queue[ 3 ] );
	} );
} );
