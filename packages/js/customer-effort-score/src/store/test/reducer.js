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

describe( 'customer effort score reducer', () => {
	describe( 'ADD_CES_SURVEY', () => {
		it.each( [ 'onSubmitLabel', 'onsubmitLabel', 'onsubmit_label' ] )(
			'keeps a custom label from the %s action field',
			( inputField ) => {
				const state = reducer( undefined, {
					type: TYPES.ADD_CES_SURVEY,
					...surveyAction,
					[ inputField ]: 'Share feedback',
				} );

				expect( state.queue ).toHaveLength( 1 );
				expect( state.queue[ 0 ].onSubmitLabel ).toBe(
					'Share feedback'
				);
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
				caseName: 'falls through null canonical and lower-camel values',
				labels: {
					onSubmitLabel: null,
					onsubmitLabel: null,
					onsubmit_label: 'Snake-case label',
				},
				expected: 'Snake-case label',
			},
		] )( '$caseName', ( { labels, expected } ) => {
			const state = reducer( undefined, {
				type: TYPES.ADD_CES_SURVEY,
				...surveyAction,
				...labels,
			} );

			expect( state.queue[ 0 ].onSubmitLabel ).toBe( expected );
		} );

		it( 'leaves the queue and its order unchanged for a duplicate survey', () => {
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
			expect( state.queue.map( ( item ) => item.action ) ).toEqual( [
				'save_product',
				'publish_product',
			] );
		} );
	} );

	describe( 'SHOW_CES_MODAL', () => {
		it.each( [ 'onSubmitLabel', 'onsubmitLabel', 'onsubmit_label' ] )(
			'keeps a custom label from the %s action field',
			( inputField ) => {
				const state = reducer( undefined, {
					type: TYPES.SHOW_CES_MODAL,
					surveyProps: surveyAction,
					[ inputField ]: 'Share feedback',
					props: surveyAction.props,
					onSubmitNoticeProps: { type: 'success' },
					tracksProps: { source: 'product-editor' },
				} );

				expect( state.cesModalData.onSubmitLabel ).toBe(
					'Share feedback'
				);
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
				caseName: 'falls through null canonical and lower-camel values',
				labels: {
					onSubmitLabel: null,
					onsubmitLabel: null,
					onsubmit_label: 'Snake-case label',
				},
				expected: 'Snake-case label',
			},
		] )( '$caseName', ( { labels, expected } ) => {
			const state = reducer( undefined, {
				type: TYPES.SHOW_CES_MODAL,
				surveyProps: surveyAction,
				...labels,
			} );

			expect( state.cesModalData.onSubmitLabel ).toBe( expected );
		} );
	} );

	describe( 'SET_CES_SURVEY_QUEUE', () => {
		it( 'normalizes mixed label fields without changing items or order', () => {
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
					unrelated: Object.freeze( { source: 'canonical' } ),
				} ),
				Object.freeze( {
					id: 'lower-camel',
					action: 'lower_camel_survey',
					onsubmitLabel: 'Lower-camel label',
					unrelated: Object.freeze( { source: 'lower-camel' } ),
				} ),
				Object.freeze( {
					id: 'snake-case',
					action: 'snake_case_survey',
					onsubmit_label: 'Snake-case label',
					unrelated: Object.freeze( { source: 'snake-case' } ),
				} ),
				Object.freeze( {
					id: 'no-label',
					action: 'default_label_survey',
					unrelated: Object.freeze( { source: 'default' } ),
				} ),
			] );
			const initialQueue = Object.freeze( [ existingItem ] );
			const initialState = Object.freeze( {
				queue: initialQueue,
				cesModalData: undefined,
				showCESModal: false,
				showProductMVPFeedbackModal: false,
			} );

			const state = reducer( initialState, {
				type: TYPES.SET_CES_SURVEY_QUEUE,
				queue,
			} );

			expect( state.queue ).toEqual( [
				{
					id: 'existing',
					action: 'existing_survey',
					onSubmitLabel: 'Existing label',
				},
				{
					id: 'canonical',
					action: 'canonical_survey',
					onSubmitLabel: 'Canonical label',
					unrelated: { source: 'canonical' },
				},
				{
					id: 'lower-camel',
					action: 'lower_camel_survey',
					onsubmitLabel: 'Lower-camel label',
					onSubmitLabel: 'Lower-camel label',
					unrelated: { source: 'lower-camel' },
				},
				{
					id: 'snake-case',
					action: 'snake_case_survey',
					onsubmit_label: 'Snake-case label',
					onSubmitLabel: 'Snake-case label',
					unrelated: { source: 'snake-case' },
				},
				{
					id: 'no-label',
					action: 'default_label_survey',
					onSubmitLabel: undefined,
					unrelated: { source: 'default' },
				},
			] );
			expect( state.queue.map( ( item ) => item.id ) ).toEqual( [
				'existing',
				'canonical',
				'lower-camel',
				'snake-case',
				'no-label',
			] );
			expect( state.queue[ 4 ] ).toHaveProperty(
				'onSubmitLabel',
				undefined
			);

			expect( initialState ).toEqual( {
				queue: [
					{
						id: 'existing',
						action: 'existing_survey',
						onSubmitLabel: 'Existing label',
					},
				],
				cesModalData: undefined,
				showCESModal: false,
				showProductMVPFeedbackModal: false,
			} );
			expect( queue ).toEqual( [
				{
					id: 'canonical',
					action: 'canonical_survey',
					onSubmitLabel: 'Canonical label',
					unrelated: { source: 'canonical' },
				},
				{
					id: 'lower-camel',
					action: 'lower_camel_survey',
					onsubmitLabel: 'Lower-camel label',
					unrelated: { source: 'lower-camel' },
				},
				{
					id: 'snake-case',
					action: 'snake_case_survey',
					onsubmit_label: 'Snake-case label',
					unrelated: { source: 'snake-case' },
				},
				{
					id: 'no-label',
					action: 'default_label_survey',
					unrelated: { source: 'default' },
				},
			] );
			expect( state ).not.toBe( initialState );
			expect( state.queue ).not.toBe( initialQueue );
			expect( state.queue[ 2 ] ).not.toBe( queue[ 1 ] );
			expect( state.queue[ 3 ] ).not.toBe( queue[ 2 ] );
			expect( state.queue[ 4 ] ).not.toBe( queue[ 3 ] );
		} );

		it( 'normalizes queue items without mutating the action or prior state', () => {
			const existingItem = Object.freeze( {
				action: 'existing_survey',
				onSubmitLabel: 'Existing label',
			} );
			const lowerCamelItem = Object.freeze( {
				action: 'lower_camel_survey',
				onsubmitLabel: 'Lower-camel label',
			} );
			const noLabelItem = Object.freeze( {
				action: 'default_label_survey',
				description: 'Uses the default submit label.',
			} );
			const initialQueue = Object.freeze( [ existingItem ] );
			const actionQueue = Object.freeze( [
				lowerCamelItem,
				noLabelItem,
			] );
			const initialState = Object.freeze( {
				queue: initialQueue,
				cesModalData: undefined,
				showCESModal: false,
				showProductMVPFeedbackModal: false,
			} );
			const action = Object.freeze( {
				type: TYPES.SET_CES_SURVEY_QUEUE,
				queue: actionQueue,
			} );

			const state = reducer( initialState, action );

			expect( initialState ).toEqual( {
				queue: [
					{
						action: 'existing_survey',
						onSubmitLabel: 'Existing label',
					},
				],
				cesModalData: undefined,
				showCESModal: false,
				showProductMVPFeedbackModal: false,
			} );
			expect( action ).toEqual( {
				type: TYPES.SET_CES_SURVEY_QUEUE,
				queue: [
					{
						action: 'lower_camel_survey',
						onsubmitLabel: 'Lower-camel label',
					},
					{
						action: 'default_label_survey',
						description: 'Uses the default submit label.',
					},
				],
			} );
			expect( state ).not.toBe( initialState );
			expect( state.queue ).not.toBe( initialQueue );
			expect( state.queue[ 1 ] ).not.toBe( lowerCamelItem );
			expect( state.queue[ 2 ] ).not.toBe( noLabelItem );
			expect( state.queue[ 1 ] ).toEqual( {
				action: 'lower_camel_survey',
				onsubmitLabel: 'Lower-camel label',
				onSubmitLabel: 'Lower-camel label',
			} );
			expect( state.queue[ 2 ] ).toEqual( {
				action: 'default_label_survey',
				description: 'Uses the default submit label.',
				onSubmitLabel: undefined,
			} );
			expect( state.queue[ 2 ] ).toHaveProperty(
				'onSubmitLabel',
				undefined
			);
		} );

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
			const queueItem = {
				action: 'save_product',
				...labels,
			};

			const state = reducer( undefined, {
				type: TYPES.SET_CES_SURVEY_QUEUE,
				queue: [ queueItem ],
			} );

			expect( state.queue[ 0 ] ).toEqual( {
				...queueItem,
				onSubmitLabel: expected,
			} );
		} );
	} );
} );
