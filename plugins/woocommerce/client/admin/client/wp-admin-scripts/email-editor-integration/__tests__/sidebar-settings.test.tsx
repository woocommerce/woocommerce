/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentType, JSX, ReactNode } from 'react';

type WooCommerceData = Record< string, unknown >;
type RichTextWithButtonProps = {
	attributeName: string;
	attributeValue: string;
	updateProperty: ( name: string, value: string | boolean ) => void;
	label: string;
	placeholder: string;
	help?: ReactNode;
};
type SidebarFilter = (
	RichTextWithButton: ComponentType< RichTextWithButtonProps >,
	tracking: {
		recordEvent: jest.Mock;
		debouncedRecordEvent: jest.Mock;
	}
) => ComponentType;

const mockRegisteredPlugins = new Map<
	string,
	{ render: () => JSX.Element }
>();
const mockSidebarFilters: SidebarFilter[] = [];

const mockEntityState: {
	woocommerceData: WooCommerceData;
	editedWooCommerceData: Record< string, unknown >;
} = {
	woocommerceData: {},
	editedWooCommerceData: {},
};
const mockEditEntityRecord = jest.fn();
const mockEmailEditorRecordEvent = jest.fn();
const mockEntitySubscribers = new Set< () => void >();

jest.mock( '@wordpress/core-data', () => ( {
	store: { name: 'core' },
	useEntityProp: () => {
		const { useEffect, useReducer } =
			jest.requireActual( '@wordpress/element' );
		const [ , refresh ] = useReducer( ( count: number ) => count + 1, 0 );
		useEffect( () => {
			mockEntitySubscribers.add( refresh );
			return () => mockEntitySubscribers.delete( refresh );
		}, [ refresh ] );
		return [ mockEntityState.woocommerceData ];
	},
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	select: () => ( {
		getEditedEntityRecord: () => ( {
			woocommerce_data: mockEntityState.editedWooCommerceData,
		} ),
	} ),
	dispatch: () => ( {
		editEntityRecord: mockEditEntityRecord,
	} ),
} ) );

jest.mock( '@wordpress/hooks', () => ( {
	addFilter: (
		filterName: string,
		_namespace: string,
		callback: SidebarFilter
	) => {
		if (
			filterName ===
			'woocommerce_email_editor_setting_sidebar_extension_component'
		) {
			mockSidebarFilters.push( callback );
		}
	},
} ) );

jest.mock( '@wordpress/plugins', () => ( {
	registerPlugin: ( name: string, settings: { render: () => JSX.Element } ) =>
		mockRegisteredPlugins.set( name, settings ),
} ) );

jest.mock( '@woocommerce/email-editor', () => ( {
	EmailActionsFill: ( { children }: { children: ReactNode } ) => children,
	TemplateSelection: () => null,
	recordEvent: mockEmailEditorRecordEvent,
} ) );

/**
 * Internal dependencies
 */
import { modifySidebar } from '../sidebar_settings';

const defaultWooCommerceData: WooCommerceData = {
	recipient: 'merchant@example.com',
	cc: null,
	bcc: null,
	preheader: 'Order update preview',
	email_type: 'new_order',
	subject: 'New order',
	subject_full: null,
	subject_partial: null,
	default_subject: 'Default subject',
	is_manual: false,
	enabled: true,
};

const RichTextWithButton = ( {
	attributeName,
	attributeValue,
	updateProperty,
	label,
}: {
	attributeName: string;
	attributeValue: string;
	updateProperty: ( name: string, value: string | boolean ) => void;
	label: string;
} ) => (
	<label htmlFor={ `rich-text-${ attributeName }` }>
		{ label }
		<input
			id={ `rich-text-${ attributeName }` }
			value={ attributeValue }
			onChange={ ( event ) =>
				updateProperty( attributeName, event.target.value )
			}
		/>
	</label>
);

const renderSettings = ( {
	recordEvent = jest.fn(),
	debouncedRecordEvent = jest.fn(),
}: {
	recordEvent?: jest.Mock;
	debouncedRecordEvent?: jest.Mock;
} = {} ) => {
	modifySidebar();

	const SidebarSettings = mockSidebarFilters.at( -1 )!( RichTextWithButton, {
		recordEvent,
		debouncedRecordEvent,
	} );
	const EmailStatus = mockRegisteredPlugins.get(
		'woocommerce-email-editor-email-status'
	)!.render;

	return {
		...render(
			<>
				<EmailStatus />
				<SidebarSettings />
			</>
		),
		recordEvent,
		debouncedRecordEvent,
		SidebarSettings,
		EmailStatus,
	};
};

describe( 'Email editor sidebar settings', () => {
	beforeEach( () => {
		mockRegisteredPlugins.clear();
		mockSidebarFilters.length = 0;
		mockEntitySubscribers.clear();
		mockEditEntityRecord.mockClear();
		mockEditEntityRecord.mockImplementation(
			(
				_kind: string,
				_name: string,
				_id: string,
				{ woocommerce_data }: { woocommerce_data: WooCommerceData }
			) => {
				mockEntityState.woocommerceData = woocommerce_data;
				mockEntityState.editedWooCommerceData = woocommerce_data;
				mockEntitySubscribers.forEach( ( refresh ) => refresh() );
			}
		);
		mockEmailEditorRecordEvent.mockClear();
		mockEntityState.woocommerceData = { ...defaultWooCommerceData };
		mockEntityState.editedWooCommerceData = {
			...defaultWooCommerceData,
			unrelated_setting: 'preserved',
		};
		window.WooCommerceEmailEditor = {
			current_post_id: '42',
			current_post_type: 'woo_email',
			email_types: [],
			sender_settings: { from_name: '', from_address: '' },
		};
	} );

	it( 'updates email status and tracks it', async () => {
		const { rerender } = renderSettings();
		const initialEditedWooCommerceData = {
			...mockEntityState.editedWooCommerceData,
		};

		expect(
			screen.getByRole( 'button', { name: 'Change status: Active' } )
		).toBeEnabled();

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Change status: Active' } )
		);
		await userEvent.click(
			screen.getByRole( 'radio', { name: 'Inactive' } )
		);

		expect( mockEditEntityRecord ).toHaveBeenLastCalledWith(
			'postType',
			'woo_email',
			'42',
			{
				woocommerce_data: {
					...initialEditedWooCommerceData,
					unrelated_setting: 'preserved',
					enabled: false,
				},
			}
		);
		expect( mockEmailEditorRecordEvent ).toHaveBeenLastCalledWith(
			'email_status_changed',
			{ status: 'inactive' }
		);

		mockEntityState.woocommerceData = {
			...defaultWooCommerceData,
			is_manual: true,
		};
		rerender(
			<>
				{ mockRegisteredPlugins
					.get( 'woocommerce-email-editor-email-status' )!
					.render() }
			</>
		);

		expect(
			screen.getByRole( 'button', {
				name: 'Change status: Manually sent',
			} )
		).toBeDisabled();
	} );

	it( 'passes subject and preheader values through the sidebar data owner', async () => {
		renderSettings();
		const initialEditedWooCommerceData = {
			...mockEntityState.editedWooCommerceData,
		};

		const subject = screen.getByRole( 'textbox', { name: 'Subject' } );
		const preheader = screen.getByRole( 'textbox', {
			name: 'Preview text',
		} );
		await userEvent.clear( subject );
		await userEvent.type( subject, 'Your order is on its way' );
		await userEvent.clear( preheader );
		await userEvent.type( preheader, 'Track it from your account' );

		expect( mockEditEntityRecord ).toHaveBeenNthCalledWith(
			1,
			'postType',
			'woo_email',
			'42',
			{
				woocommerce_data: {
					...initialEditedWooCommerceData,
					subject: '',
				},
			}
		);
		expect( mockEditEntityRecord ).toHaveBeenLastCalledWith(
			'postType',
			'woo_email',
			'42',
			{
				woocommerce_data: {
					...initialEditedWooCommerceData,
					subject: 'Your order is on its way',
					preheader: 'Track it from your account',
				},
			}
		);
	} );

	it( 'updates and clears CC and BCC recipients', async () => {
		const recordEvent = jest.fn();
		const debouncedRecordEvent = jest.fn();
		mockEntityState.woocommerceData = {
			...defaultWooCommerceData,
			recipient: null,
			cc: null,
			bcc: null,
		};
		mockEntityState.editedWooCommerceData = {
			...defaultWooCommerceData,
			recipient: null,
			cc: null,
			bcc: null,
			unrelated_setting: 'preserved',
		};
		const { rerender, SidebarSettings, EmailStatus } = renderSettings( {
			recordEvent,
			debouncedRecordEvent,
		} );
		const initialEditedWooCommerceData = {
			...mockEntityState.editedWooCommerceData,
		};

		expect(
			screen.getByText( 'This email is sent to Customer.' )
		).toBeInTheDocument();
		expect(
			screen.queryByDisplayValue( 'merchant@example.com' )
		).not.toBeInTheDocument();

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'Add CC' } )
		);
		expect( screen.getByTestId( 'email_cc' ) ).toBeInTheDocument();
		expect( recordEvent ).toHaveBeenCalledWith( 'email_cc_toggle_clicked', {
			isEnabled: true,
		} );

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'Add BCC' } )
		);
		expect( screen.getByTestId( 'email_bcc' ) ).toBeInTheDocument();
		expect( recordEvent ).toHaveBeenCalledWith(
			'email_bcc_toggle_clicked',
			{ isEnabled: true }
		);

		await userEvent.type(
			screen.getByTestId( 'email_cc' ),
			'copy@example.com'
		);
		await userEvent.type(
			screen.getByTestId( 'email_bcc' ),
			'hidden@example.com'
		);

		expect( mockEditEntityRecord ).toHaveBeenLastCalledWith(
			'postType',
			'woo_email',
			'42',
			{
				woocommerce_data: {
					...initialEditedWooCommerceData,
					cc: 'copy@example.com',
					bcc: 'hidden@example.com',
				},
			}
		);
		expect( debouncedRecordEvent ).toHaveBeenCalledWith(
			'email_cc_input_updated',
			{ value: 'copy@example.com' }
		);
		expect( debouncedRecordEvent ).toHaveBeenLastCalledWith(
			'email_bcc_input_updated',
			{ value: 'hidden@example.com' }
		);

		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'Add CC' } )
		);
		await userEvent.click(
			screen.getByRole( 'checkbox', { name: 'Add BCC' } )
		);

		expect( mockEditEntityRecord ).toHaveBeenLastCalledWith(
			'postType',
			'woo_email',
			'42',
			{
				woocommerce_data: {
					...initialEditedWooCommerceData,
					cc: '',
					bcc: '',
				},
			}
		);
		expect( recordEvent ).toHaveBeenCalledWith( 'email_cc_toggle_clicked', {
			isEnabled: false,
		} );
		expect( recordEvent ).toHaveBeenCalledWith(
			'email_bcc_toggle_clicked',
			{ isEnabled: false }
		);

		mockEntityState.woocommerceData = {
			...defaultWooCommerceData,
			recipient: 'team@example.com',
		};
		rerender(
			<>
				<EmailStatus />
				<SidebarSettings />
			</>
		);
		expect(
			screen.getByDisplayValue( 'team@example.com' )
		).toBeInTheDocument();
	} );
} );
