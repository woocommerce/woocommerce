/**
 * Internal dependencies
 */
import { getActionsList, WhatsNextProps } from '../WhatsNext';

describe( 'getActionsList', () => {
	const defaultProps: WhatsNextProps = {
		allTasklists: [
			{
				id: 'setup',
				title: 'Setup',
				isHidden: false,
				isVisible: true,
				isComplete: false,
				eventPrefix: 'tasklist_setup',
				displayProgressHeader: true,
				keepCompletedTaskList: 'yes',
				tasks: [
					{
						id: 'payments',
						isComplete: false,
						content: '',
						parentId: 'setup',
						isDismissable: false,
						isDismissed: false,
						time: '',
						title: '',
						actionLabel: '',
						additionalInfo: '',
						canView: true,
						isActioned: false,
						isDisabled: false,
						isSnoozed: false,
						isSnoozeable: false,
						snoozedUntil: 0,
						isVisible: true,
						isVisited: false,
						recordViewEvent: false,
						eventPrefix: '',
						level: 3,
					},
				],
			},
			{
				id: 'extended',
				title: 'Extended',
				isHidden: false,
				isVisible: true,
				isComplete: false,
				eventPrefix: 'tasklist_extended',
				displayProgressHeader: true,
				keepCompletedTaskList: 'yes',
				tasks: [
					{
						id: 'marketing',
						isComplete: false,
						content: '',
						parentId: 'extended',
						isDismissable: false,
						isDismissed: false,
						time: '',
						title: '',
						actionLabel: '',
						additionalInfo: '',
						canView: true,
						isActioned: false,
						isDisabled: false,
						isSnoozed: false,
						isSnoozeable: false,
						snoozedUntil: 0,
						isVisible: true,
						isVisited: false,
						recordViewEvent: false,
						eventPrefix: '',
						level: 3,
					},
				],
			},
		],
		activePlugins: [],
	};

	it( 'should return 3 actions', () => {
		const actions = getActionsList( defaultProps );
		expect( actions ).toHaveLength( 3 );
	} );

	it( 'should include marketing, payment and extensions actions', () => {
		const actions = getActionsList( defaultProps );
		expect( actions ).toContainEqual(
			expect.objectContaining( { title: 'Promote your products' } )
		);
		expect( actions ).toContainEqual(
			expect.objectContaining( { title: 'Provide more ways to pay' } )
		);
		expect( actions ).toContainEqual(
			expect.objectContaining( { title: 'Power up your store' } )
		);
	} );

	it( 'should not include marketing action when marketing task is completed', () => {
		const props = {
			...defaultProps,
			allTasklists: [
				{
					id: 'setup',
					tasks: [ { id: 'payments', isComplete: false } ],
				},
				{
					id: 'extended',
					tasks: [ { id: 'marketing', isComplete: true } ],
				},
			],
		};
		const actions = getActionsList( props as WhatsNextProps );
		const marketingAction = actions.find(
			( action ) => action.title === 'Promote your products'
		);
		expect( marketingAction ).toBeUndefined();
	} );

	it( 'should not include payments action when payments task is completed', () => {
		const props = {
			...defaultProps,
			allTasklists: [
				{
					id: 'setup',
					tasks: [ { id: 'payments', isComplete: true } ],
				},
				{
					id: 'extended',
					tasks: [ { id: 'marketing', isComplete: false } ],
				},
			],
		};
		const actions = getActionsList( props as WhatsNextProps );
		const paymentsAction = actions.find(
			( action ) => action.title === 'Provide more ways to pay'
		);
		expect( paymentsAction ).toBeUndefined();
	} );

	it( 'should include mobileApp, mailchimp actions when first three actions are completed', () => {
		const props = {
			...defaultProps,
			allTasklists: [
				{
					id: 'setup',
					tasks: [ { id: 'payments', isComplete: true } ],
				},
				{
					id: 'extended',
					tasks: [ { id: 'marketing', isComplete: true } ],
				},
			],
		};
		const actions = getActionsList( props as WhatsNextProps );
		expect( actions ).toContainEqual(
			expect.objectContaining( { linkText: 'Get the app' } )
		);
		expect( actions ).toContainEqual(
			expect.objectContaining( { title: 'Build customer relationships' } )
		);
	} );

	it( 'should not include Mailchimp action when Mailchimp is activated', () => {
		const props = {
			...defaultProps,
			allTasklists: [
				{
					id: 'setup',
					tasks: [
						{ id: 'marketing', isComplete: true },
						{ id: 'payments', isComplete: true },
					],
				},
			],
			activePlugins: [ 'mailchimp-for-woocommerce' ],
		};
		const actions = getActionsList( props as WhatsNextProps );
		const mailchimpAction = actions.find(
			( action ) => action.title === 'Build customer relationships'
		);
		expect( mailchimpAction ).toBeUndefined();
	} );

	it( 'should include payments, extensions and externalDocumentation actions', () => {
		const props = {
			...defaultProps,
			allTasklists: [
				{
					id: 'setup',
					tasks: [ { id: 'payments', isComplete: true } ],
				},
				{
					id: 'extended',
					tasks: [
						{ id: 'marketing', isComplete: true },
						{ id: 'get-mobile-app', isComplete: true },
					],
				},
			],
			activePlugins: [ 'mailchimp-for-woocommerce' ],
		};
		const actions = getActionsList( props as WhatsNextProps );
		expect( actions ).toContainEqual(
			expect.objectContaining( { title: 'Provide more ways to pay' } )
		);
		expect( actions ).toContainEqual(
			expect.objectContaining( { title: 'Power up your store' } )
		);
		expect( actions ).toContainEqual(
			expect.objectContaining( { linkText: 'Explore support resources' } )
		);
	} );
} );
