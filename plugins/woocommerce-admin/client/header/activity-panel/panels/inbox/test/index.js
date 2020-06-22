/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import InboxNoteCard from '../card';
import { getUnreadNotesCount, hasValidNotes } from '../utils';

jest.mock( '../action', () =>
	jest.fn().mockImplementation( () => <button>mocked button</button> )
);

describe( 'InboxNoteCard', () => {
	const note = {
		id: 1,
		name: 'wc-admin-wc-helper-connection',
		type: 'info',
		title: 'Connect to WooCommerce.com',
		content: 'Connect to get important product notifications and updates.',
		status: 'unactioned',
		date_created: '2020-05-10T16:57:31',
		actions: [
			{
				id: 1,
				name: 'connect',
				label: 'Connect',
				query: '',
				status: 'unactioned',
				primary: false,
				url: 'http://test.com',
			},
		],
		layout: 'plain',
		image: '',
		date_created_gmt: '2020-05-10T16:57:31',
		is_deleted: false,
	};
	const lastRead = 1589285995243;

	test( 'should render a notification type banner', () => {
		note.layout = 'banner';
		const { container } = render(
			<InboxNoteCard
				key={ note.id }
				note={ note }
				lastRead={ lastRead }
			/>
		);
		const listNoteWithBanner = container.querySelector( '.banner' );
		expect( listNoteWithBanner ).not.toBeNull();
	} );

	test( 'should render a notification type thumbnail', () => {
		note.layout = 'thumbnail';
		const { container } = render(
			<InboxNoteCard
				key={ note.id }
				note={ note }
				lastRead={ lastRead }
			/>
		);
		const listNoteWithThumbnail = container.querySelector( '.thumbnail' );
		expect( listNoteWithThumbnail ).not.toBeNull();
	} );

	test( 'should render a read notification', () => {
		note.actions = [];
		const { container } = render(
			<InboxNoteCard
				key={ note.id }
				note={ note }
				lastRead={ lastRead }
			/>
		);
		const unreadNote = container.querySelector( '.message-is-unread' );
		const readNote = container.querySelector(
			'.woocommerce-inbox-message'
		);
		expect( unreadNote ).toBeNull();
		expect( readNote ).not.toBeNull();
	} );

	test( 'should render an unread notification', () => {
		const olderLastRead = 1584015595000;
		note.actions = [];
		const { container } = render(
			<InboxNoteCard
				key={ note.id }
				note={ note }
				lastRead={ olderLastRead }
			/>
		);
		const unreadNote = container.querySelector( '.message-is-unread' );
		expect( unreadNote ).not.toBeNull();
	} );

	test( 'should not render any notification', () => {
		note.is_deleted = true;
		const { container } = render(
			<InboxNoteCard
				key={ note.id }
				note={ note }
				lastRead={ lastRead }
			/>
		);
		const unreadNote = container.querySelector(
			'.woocommerce-inbox-message'
		);
		expect( unreadNote ).toBeNull();
	} );
} );

const notes = [
	{
		id: 1,
		date_created_gmt: '2019-05-10T16:57:31',
		is_deleted: false,
		status: 'unactioned',
	},
	{
		id: 2,
		date_created_gmt: '2020-05-12T16:57:31',
		is_deleted: false,
		status: 'unactioned',
	},
	{
		id: 3,
		date_created_gmt: '2020-05-14T16:57:31',
		is_deleted: false,
		status: 'unactioned',
	},
	{
		id: 4,
		date_created_gmt: '2020-05-15T16:57:31',
		is_deleted: false,
		status: 'unactioned',
	},
	{
		id: 5,
		date_created_gmt: '2020-05-18T16:57:31',
		is_deleted: false,
		status: 'unactioned',
	},
];

describe( 'getUnreadNotesCount', () => {
	const lastRead = 1589285995243;

	test( 'should return 4, 1 of the notes was read', () => {
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 4 );
	} );

	test( 'should return 3, 1 of the notes was read and 1 is deleted', () => {
		notes[ 3 ].is_deleted = true;
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 3 );
	} );

	test( 'should return 2, 2 of the notes were read and 1 is deleted', () => {
		notes[ 1 ].date_created_gmt = '2020-05-05T16:57:31';
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 2 );
	} );

	test( 'should return 1, 2 of the notes were read, 1 was actioned and 1 is deleted', () => {
		notes[ 4 ].status = 'actioned';
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 1 );
	} );

	test( 'should return 0, 2 of the notes were read and 2 are deleted', () => {
		notes[ 2 ].is_deleted = true;
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 0 );
	} );
} );

describe( 'hasValidNotes', () => {
	test( 'should return true, 2 notes are deleted', () => {
		expect( hasValidNotes( notes ) ).toBeTruthy();
	} );
	test( 'should return false, 4 notes are deleted', () => {
		notes[ 0 ].is_deleted = true;
		notes[ 3 ].is_deleted = true;
		expect( hasValidNotes( notes ) ).toBeTruthy();
	} );
} );
