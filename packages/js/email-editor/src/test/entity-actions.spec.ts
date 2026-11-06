/**
 * External dependencies
 */
import { applyFilters, removeAllFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { initEntityActions } from '../entity-actions';
import getTrashEmailPostAction from '../components/header/trash-email-post';

jest.mock( '../components/header/trash-email-post', () => ( {
	__esModule: true,
	default: jest.fn( () => ( {
		id: 'trash-email-post',
		label: 'Permanently delete',
		RenderModal: () => null,
	} ) ),
} ) );

const getTrashEmailPostActionMock = getTrashEmailPostAction as jest.Mock;

describe( 'initEntityActions', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		removeAllFilters( 'woocommerce_email_editor_entity_actions' );
	} );

	it( 'adds the trash email post action to the entity actions filter', () => {
		initEntityActions();

		const actions = applyFilters(
			'woocommerce_email_editor_entity_actions',
			{ 'other-action': { label: 'Other' } }
		) as Record< string, { label: string; RenderModal?: unknown } >;

		expect( actions[ 'other-action' ] ).toEqual( { label: 'Other' } );
		expect( actions[ 'trash-email-post' ].label ).toBe(
			'Permanently delete'
		);
		expect( actions[ 'trash-email-post' ].RenderModal ).toBeDefined();
	} );

	it( 'builds the action only when the filter is applied', () => {
		initEntityActions();

		expect( getTrashEmailPostActionMock ).not.toHaveBeenCalled();

		applyFilters( 'woocommerce_email_editor_entity_actions', {} );

		expect( getTrashEmailPostActionMock ).toHaveBeenCalledTimes( 1 );
	} );
} );
