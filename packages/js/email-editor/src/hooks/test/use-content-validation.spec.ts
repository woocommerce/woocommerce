/**
 * External dependencies
 */
import { addFilter, removeAllFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { validateEmailContent } from '../use-content-validation';

// The hook's imports pull in the block editor, which Jest cannot transform.
// Only the pure validateEmailContent function is under test.
jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
	subscribe: jest.fn(),
	dispatch: jest.fn(),
} ) );
jest.mock( '@wordpress/core-data', () => ( { store: { name: 'core' } } ) );
jest.mock( '../../store', () => ( { storeName: 'email-editor/editor' } ) );
jest.mock( '../use-validation-notices', () => ( {
	useValidationNotices: jest.fn(),
} ) );

const HOOK = 'woocommerce_email_editor_content_validation_rules';

function notices( existing: string[] = [] ) {
	return {
		addValidationNotice: jest.fn(),
		hasValidationNotice: jest.fn( ( id?: string ) =>
			existing.includes( id ?? '' )
		),
		removeValidationNotice: jest.fn(),
	};
}

function setRules( rules ) {
	addFilter( HOOK, 'test', () => rules );
}

describe( 'validateEmailContent', () => {
	afterEach( () => removeAllFilters( HOOK ) );

	it( 'passes when no rules are registered', () => {
		const handlers = notices();

		expect( validateEmailContent( '<p>Hi</p>', '', handlers ) ).toBe(
			true
		);
		expect( handlers.addValidationNotice ).not.toHaveBeenCalled();
	} );

	it( 'fails and adds a notice when a rule finds a problem in the email or its template', () => {
		const testContent = jest.fn(
			( content: string ) => ! content.includes( 'unsubscribe' )
		);
		setRules( [
			{
				id: 'missing-link',
				testContent,
				message: 'Add a link',
				actions: [ 'fix' ],
			},
		] );
		const handlers = notices();

		expect(
			validateEmailContent( '<p>Body</p>', '<footer />', handlers )
		).toBe( false );
		expect( testContent ).toHaveBeenCalledWith( '<p>Body</p><footer />' );
		expect( handlers.addValidationNotice ).toHaveBeenCalledWith(
			'missing-link',
			'Add a link',
			[ 'fix' ]
		);
	} );

	it( 'removes the notice of a rule that passes again', () => {
		setRules( [
			{
				id: 'missing-link',
				testContent: () => false,
				message: '',
				actions: [],
			},
			{ id: 'other', testContent: () => false, message: '', actions: [] },
		] );
		const handlers = notices( [ 'missing-link' ] );

		expect(
			validateEmailContent( '<a>unsubscribe</a>', '', handlers )
		).toBe( true );
		expect( handlers.removeValidationNotice ).toHaveBeenCalledTimes( 1 );
		expect( handlers.removeValidationNotice ).toHaveBeenCalledWith(
			'missing-link'
		);
	} );
} );
