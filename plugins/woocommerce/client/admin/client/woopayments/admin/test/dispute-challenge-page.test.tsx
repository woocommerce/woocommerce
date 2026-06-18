/**
 * External dependencies
 */
import { act, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { speak } from '@wordpress/a11y';
import { MemoryRouter } from 'react-router-dom';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { WooPaymentsDisputeChallengePage } from '../money-movement/dispute-challenge-page';
import {
	getWooPaymentsDispute,
	getWooPaymentsDisputeFileDetails,
	updateWooPaymentsDispute,
	uploadWooPaymentsDisputeFile,
} from '../money-movement/data';
import {
	DOCUMENT_EVIDENCE_FIELDS,
	SHIPPING_EVIDENCE_FIELDS,
	buildEvidencePayload,
} from '../money-movement/dispute-evidence-fields';
import type {
	WooPaymentsDispute,
	WooPaymentsDisputeFile,
} from '../money-movement/types';

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '@wordpress/a11y', () => ( {
	speak: jest.fn(),
} ) );

jest.mock( '../money-movement/data', () => ( {
	getWooPaymentsDispute: jest.fn(),
	getWooPaymentsDisputeFileDetails: jest.fn(),
	updateWooPaymentsDispute: jest.fn(),
	uploadWooPaymentsDisputeFile: jest.fn(),
} ) );

const mockGetDispute = getWooPaymentsDispute as jest.MockedFunction<
	typeof getWooPaymentsDispute
>;
const mockGetFileDetails =
	getWooPaymentsDisputeFileDetails as jest.MockedFunction<
		typeof getWooPaymentsDisputeFileDetails
	>;
const mockUpdateDispute = updateWooPaymentsDispute as jest.MockedFunction<
	typeof updateWooPaymentsDispute
>;
const mockUploadFile = uploadWooPaymentsDisputeFile as jest.MockedFunction<
	typeof uploadWooPaymentsDisputeFile
>;
const mockRecordEvent = recordEvent as jest.MockedFunction<
	typeof recordEvent
>;
const mockSpeak = speak as jest.MockedFunction< typeof speak >;

const makeDispute = (
	overrides: Partial< WooPaymentsDispute > = {}
): WooPaymentsDispute => ( {
	id: 'dp_test',
	reason: 'fraudulent',
	status: 'needs_response',
	amount: 5000,
	currency: 'usd',
	created: 1771423200,
	evidence: {},
	evidence_details: {
		due_by: 1772028000,
	},
	metadata: {},
	order: {
		id: 123,
		number: '123',
		suggested_product_type: 'physical_product',
	},
	...overrides,
} );

const renderChallengePage = () =>
	render(
		<MemoryRouter
			initialEntries={ [ '/woopayments/disputes/challenge?id=dp_test' ] }
		>
			<WooPaymentsDisputeChallengePage />
		</MemoryRouter>
	);

const uploadFile = async (
	label: string,
	file = new File( [ 'receipt' ], 'receipt.pdf', {
		type: 'application/pdf',
	} )
) => {
	const input = await screen.findByLabelText( label );

	await act( async () => {
		await userEvent.upload( input, file );
	} );
};

const clickButton = async ( name: string ) => {
	await act( async () => {
		await userEvent.click( screen.getByRole( 'button', { name } ) );
	} );
};

const createDeferred = < TValue, >() => {
	let resolve!: ( value: TValue ) => void;
	let reject!: ( error: unknown ) => void;
	const promise = new Promise< TValue >( ( nextResolve, nextReject ) => {
		resolve = nextResolve;
		reject = nextReject;
	} );

	return { promise, resolve, reject };
};

describe( 'buildEvidencePayload', () => {
	it( 'should preserve the dispute evidence clearing contract', () => {
		const payload = buildEvidencePayload(
			{
				productType: 'physical_product',
				metadata: {
					existing_key: 'existing_value',
				},
				existingEvidence: {
					billing_address: '123 Main St',
					enhanced_evidence: {
						visa_compliance: {
							fee_acknowledged: 'true',
						},
					},
				},
				evidence: {
					customer_communication: 'file_customer_communication',
					shipping_carrier: '',
					shipping_date: '',
					shipping_tracking_number: '1Z999',
					shipping_address: '',
					product_description: 'A custom hat.',
					uncategorized_text: '',
					customer_purchase_ip: '203.0.113.1',
				},
			},
			false
		);

		expect( payload.submit ).toBe( false );
		expect( payload.metadata ).toMatchObject( {
			existing_key: 'existing_value',
			__product_type: 'physical_product',
		} );
		DOCUMENT_EVIDENCE_FIELDS.forEach( ( field ) => {
			expect( payload.evidence ).toHaveProperty(
				field,
				field === 'customer_communication'
					? 'file_customer_communication'
					: ''
			);
		} );
		SHIPPING_EVIDENCE_FIELDS.forEach( ( field ) => {
			expect( payload.evidence ).toHaveProperty(
				field,
				field === 'shipping_tracking_number' ? '1Z999' : ''
			);
		} );
		expect( payload.evidence ).toMatchObject( {
			billing_address: '123 Main St',
			enhanced_evidence: {
				visa_compliance: {
					fee_acknowledged: 'true',
				},
			},
			product_description: 'A custom hat.',
			customer_purchase_ip: '203.0.113.1',
			uncategorized_text: '',
		} );
	} );
} );

describe( 'WooPaymentsDisputeChallengePage', () => {
	beforeEach( () => {
		jest.spyOn( window, 'confirm' ).mockReturnValue( true );
		mockGetDispute.mockReset();
		mockGetFileDetails.mockReset();
		mockUpdateDispute.mockReset();
		mockUploadFile.mockReset();
		mockRecordEvent.mockReset();
		mockSpeak.mockReset();
		mockGetFileDetails.mockResolvedValue( {
			id: 'file_unused',
			filename: 'unused.pdf',
			size: 1000,
		} );
		mockUpdateDispute.mockImplementation( async ( _id, data ) =>
			makeDispute( {
				evidence: data.evidence,
				metadata: data.metadata,
			} )
		);
		mockUploadFile.mockResolvedValue( {
			id: 'file_uploaded',
			filename: 'receipt.pdf',
			size: 7,
		} );
	} );

	afterEach( () => {
		jest.restoreAllMocks();
	} );

	it( 'should render an actionable evidence form with enabled controls', async () => {
		mockGetDispute.mockResolvedValue( makeDispute() );

		renderChallengePage();

		expect(
			await screen.findByRole( 'heading', {
				name: 'Challenge dispute',
			} )
		).toBeInTheDocument();
		expect(
			await screen.findByRole( 'combobox', { name: 'Product type' } )
		).toBeEnabled();
		expect(
			screen.getByRole( 'textbox', { name: 'Product description' } )
		).toBeEnabled();
		expect(
			screen.getByRole( 'textbox', { name: 'Additional evidence' } )
		).toBeEnabled();
		expect( screen.getByLabelText( 'Upload receipt' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'Save draft' } )
		).toBeEnabled();
		expect(
			screen.getByRole( 'button', { name: 'Submit evidence' } )
		).toBeEnabled();
		expect(
			screen.queryByText(
				'Dispute evidence submission is not available in this native WooPayments admin surface yet.'
			)
		).not.toBeInTheDocument();
	} );

	it( 'should render non-actionable disputes as read-only', async () => {
		mockGetDispute.mockResolvedValue(
			makeDispute( {
				status: 'won',
				evidence: {
					product_description: 'Delivered in full.',
				},
			} )
		);

		renderChallengePage();

		expect(
			await screen.findByText( 'This dispute is read-only.' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'textbox', { name: 'Product description' } )
		).toHaveAttribute( 'readonly' );
		expect(
			screen.queryByLabelText( 'Upload receipt' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save draft' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Submit evidence' } )
		).not.toBeInTheDocument();
	} );

	it( 'should fetch and render saved evidence file details', async () => {
		mockGetDispute.mockResolvedValue(
			makeDispute( {
				evidence: {
					receipt: 'file_receipt',
				},
			} )
		);
		mockGetFileDetails.mockResolvedValueOnce( {
			id: 'file_receipt',
			filename: 'receipt.pdf',
			size: 1000,
		} );

		renderChallengePage();

		expect( await screen.findByText( 'receipt.pdf' ) ).toBeInTheDocument();
		expect( mockGetFileDetails ).toHaveBeenCalledWith( 'file_receipt' );
	} );

	it( 'should warn when saved evidence file details cannot be verified', async () => {
		mockGetDispute.mockResolvedValue(
			makeDispute( {
				evidence: {
					receipt: 'file_receipt',
				},
			} )
		);
		mockGetFileDetails.mockRejectedValueOnce(
			new Error( 'File details unavailable' )
		);

		renderChallengePage();

		expect( await screen.findByText( 'file_receipt' ) ).toBeInTheDocument();
		expect(
			await screen.findAllByText( /could not be verified/i )
		).toHaveLength( 2 );
		expect( screen.getByRole( 'alert' ) ).toHaveTextContent(
			/could not be verified/i
		);
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_dispute_file_details_failed',
			{
				dispute_id: 'dp_test',
				dispute_status: 'needs_response',
				dispute_reason: 'fraudulent',
				field: 'receipt',
				file_id: 'file_receipt',
				message: 'File details unavailable',
			}
		);

		await uploadFile(
			'Upload customer communication',
			new File( [ 'communication' ], 'communication.pdf', {
				type: 'application/pdf',
			} )
		);

		expect( mockUploadFile ).not.toHaveBeenCalled();
		expect(
			await screen.findByText(
				'The selected files exceed the 4.5 MB dispute evidence limit.'
			)
		).toBeInTheDocument();
	} );

	it( 'should keep replacement files when saved file details resolve late', async () => {
		const savedFileDetails = createDeferred< WooPaymentsDisputeFile >();
		mockGetDispute.mockResolvedValue(
			makeDispute( {
				evidence: {
					receipt: 'file_receipt',
				},
			} )
		);
		mockGetFileDetails.mockReturnValueOnce( savedFileDetails.promise );
		mockUploadFile.mockResolvedValueOnce( {
			id: 'file_replacement',
			filename: 'replacement.pdf',
			size: 8,
		} );

		renderChallengePage();

		expect( await screen.findByText( 'file_receipt' ) ).toBeInTheDocument();
		await uploadFile(
			'Upload receipt',
			new File( [ 'replacement' ], 'replacement.pdf', {
				type: 'application/pdf',
			} )
		);
		expect(
			await screen.findByText( 'replacement.pdf' )
		).toBeInTheDocument();

		await act( async () => {
			savedFileDetails.resolve( {
				id: 'file_receipt',
				filename: 'receipt.pdf',
				size: 1000,
			} );
			await savedFileDetails.promise;
		} );

		await waitFor( () =>
			expect(
				screen.queryByText( 'receipt.pdf' )
			).not.toBeInTheDocument()
		);
		expect( screen.getByText( 'replacement.pdf' ) ).toBeInTheDocument();

		await clickButton( 'Save draft' );

		await waitFor( () => expect( mockUpdateDispute ).toHaveBeenCalled() );
		expect( mockUpdateDispute.mock.calls[ 0 ][ 1 ].evidence.receipt ).toBe(
			'file_replacement'
		);
	} );

	it( 'should reject uploads that exceed the aggregate evidence size limit', async () => {
		mockGetDispute.mockResolvedValue(
			makeDispute( {
				evidence: {
					customer_communication: 'file_customer_communication',
				},
			} )
		);
		mockGetFileDetails.mockResolvedValueOnce( {
			id: 'file_customer_communication',
			filename: 'customer-email.pdf',
			size: 4400000,
		} );
		const tooLarge = new File(
			[ new Uint8Array( 200001 ) ],
			'large-receipt.pdf',
			{
				type: 'application/pdf',
			}
		);

		renderChallengePage();
		await screen.findByText( 'customer-email.pdf' );
		await uploadFile( 'Upload receipt', tooLarge );

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			'The selected files exceed the 4.5 MB dispute evidence limit.'
		);
		expect( mockUploadFile ).not.toHaveBeenCalled();
	} );

	it( 'should upload accepted files and keep their IDs in the draft payload', async () => {
		mockGetDispute.mockResolvedValue( makeDispute() );
		mockUploadFile.mockResolvedValueOnce( {
			id: 'file_receipt',
			filename: 'receipt.pdf',
			size: 7,
		} );

		renderChallengePage();
		await uploadFile( 'Upload receipt' );
		expect( await screen.findByText( 'receipt.pdf' ) ).toBeInTheDocument();
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Receipt uploaded.',
			'polite'
		);
		await clickButton( 'Save draft' );

		await waitFor( () =>
			expect( mockUpdateDispute ).toHaveBeenCalledWith(
				'dp_test',
				expect.objectContaining( {
					submit: false,
					evidence: expect.objectContaining( {
						receipt: 'file_receipt',
					} ),
				} )
			)
		);
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_dispute_file_upload_started',
			expect.objectContaining( {
				dispute_id: 'dp_test',
				type: 'receipt',
			} )
		);
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_dispute_file_upload_success',
			expect.objectContaining( {
				dispute_id: 'dp_test',
				type: 'receipt',
			} )
		);
	} );

	it( 'should save drafts with submit=false and product metadata', async () => {
		mockGetDispute.mockResolvedValue( makeDispute() );

		renderChallengePage();
		await screen.findByRole( 'combobox', { name: 'Product type' } );
		await userEvent.selectOptions(
			screen.getByRole( 'combobox', { name: 'Product type' } ),
			'digital_product_or_service'
		);
		await userEvent.type(
			screen.getByRole( 'textbox', { name: 'Product description' } ),
			'Downloaded software.'
		);
		await clickButton( 'Save draft' );

		await waitFor( () =>
			expect( mockUpdateDispute ).toHaveBeenCalledWith(
				'dp_test',
				expect.objectContaining( {
					submit: false,
					evidence: expect.objectContaining( {
						product_description: 'Downloaded software.',
						shipping_carrier: '',
						receipt: '',
					} ),
					metadata: expect.objectContaining( {
						__product_type: 'digital_product_or_service',
					} ),
				} )
			)
		);
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_dispute_product_selected',
			expect.objectContaining( {
				dispute_id: 'dp_test',
				selection: 'digital_product_or_service',
			} )
		);
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_dispute_save_evidence_clicked',
			expect.objectContaining( {
				dispute_id: 'dp_test',
			} )
		);
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_dispute_save_evidence_success',
			expect.objectContaining( {
				dispute_id: 'dp_test',
			} )
		);
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Evidence draft saved.',
			'polite'
		);
	} );

	it( 'should block save while an evidence file upload is pending', async () => {
		mockGetDispute.mockResolvedValue( makeDispute() );
		let resolveUpload: (
			value: Awaited< ReturnType< typeof uploadWooPaymentsDisputeFile > >
		) => void = () => {};
		mockUploadFile.mockReturnValueOnce(
			new Promise( ( resolve ) => {
				resolveUpload = resolve;
			} )
		);

		renderChallengePage();
		await uploadFile( 'Upload receipt' );

		const saveButton = screen.getByRole( 'button', {
			name: 'Save draft',
		} );
		expect( saveButton ).toHaveAttribute( 'aria-disabled', 'true' );
		await userEvent.click( saveButton );
		expect( mockUpdateDispute ).not.toHaveBeenCalled();

		await act( async () => {
			resolveUpload( {
				id: 'file_receipt',
				filename: 'receipt.pdf',
				size: 7,
			} );
		} );
	} );

	it( 'should confirm final submission and post submit=true', async () => {
		mockGetDispute.mockResolvedValue( makeDispute() );

		renderChallengePage();
		await screen.findByRole( 'button', { name: 'Submit evidence' } );
		await clickButton( 'Submit evidence' );

		expect( window.confirm ).toHaveBeenCalled();
		await waitFor( () =>
			expect( mockUpdateDispute ).toHaveBeenCalledWith(
				'dp_test',
				expect.objectContaining( {
					submit: true,
				} )
			)
		);
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_dispute_submit_evidence_clicked',
			expect.objectContaining( {
				dispute_id: 'dp_test',
			} )
		);
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_dispute_submit_evidence_success',
			expect.objectContaining( {
				dispute_id: 'dp_test',
			} )
		);
	} );

	it( 'should surface update failures in an alert', async () => {
		mockGetDispute.mockResolvedValue( makeDispute() );
		mockUpdateDispute.mockRejectedValueOnce(
			new Error( 'Provider failed' )
		);

		renderChallengePage();
		await screen.findByRole( 'button', { name: 'Save draft' } );
		await clickButton( 'Save draft' );

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			'Provider failed'
		);
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Provider failed',
			'assertive'
		);
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_dispute_save_evidence_failed',
			expect.objectContaining( {
				dispute_id: 'dp_test',
			} )
		);
	} );

	it( 'should surface upload failures in an alert', async () => {
		mockGetDispute.mockResolvedValue( makeDispute() );
		mockUploadFile.mockRejectedValueOnce( new Error( 'Upload failed' ) );

		renderChallengePage();
		await uploadFile( 'Upload receipt' );

		expect( await screen.findByRole( 'alert' ) ).toHaveTextContent(
			'Upload failed'
		);
		expect( mockSpeak ).toHaveBeenCalledWith(
			'Upload failed',
			'assertive'
		);
		expect( mockRecordEvent ).toHaveBeenCalledWith(
			'wcpay_dispute_file_upload_failed',
			expect.objectContaining( {
				dispute_id: 'dp_test',
				message: 'Upload failed',
			} )
		);
	} );
} );
