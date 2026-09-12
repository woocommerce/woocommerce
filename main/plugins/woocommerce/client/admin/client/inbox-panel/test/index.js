/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect } from 'react';
import { InboxNoteCard } from '@woocommerce/experimental';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { getUnreadNotesCount, hasValidNotes } from '../utils';
import InboxPanel from '../';

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

jest.mock( '@woocommerce/experimental', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@woocommerce/experimental' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		InboxNoteCard: jest.fn().mockImplementation( ( { note } ) => {
			return <div>{ note.id }</div>;
		} ),
	};
} );

const NOTES = [
	{
		id: 1,
		date_created_gmt: '2019-05-10T16:57:31',
		is_deleted: false,
		status: 'unactioned',
		actions: [],
		content: 'content1',
		name: 'name1',
		title: 'title1',
		type: 'type1',
	},
	{
		id: 2,
		date_created_gmt: '2020-05-12T16:57:31',
		is_deleted: false,
		status: 'unactioned',
		actions: [],
		content: 'content2',
		name: 'name2',
		title: 'title2',
		type: 'type2',
	},
	{
		id: 3,
		date_created_gmt: '2020-05-14T16:57:31',
		is_deleted: false,
		status: 'unactioned',
		actions: [],
		content: 'content3',
		name: 'name3',
		title: 'title3',
		type: 'type3',
	},
	{
		id: 4,
		date_created_gmt: '2020-05-15T16:57:31',
		is_deleted: false,
		status: 'unactioned',
		actions: [],
		content: 'content4',
		name: 'name4',
		title: 'title4',
		type: 'type4',
	},
	{
		id: 5,
		date_created_gmt: '2020-05-18T16:57:31',
		is_deleted: false,
		status: 'unactioned',
		actions: [],
		content: 'content5',
		name: 'name5',
		title: 'title5',
		type: 'type5',
	},
];

describe( 'getUnreadNotesCount', () => {
	const lastRead = 1589285995243;

	test( 'should return 4, 1 of the notes was read', () => {
		const unreadCount = getUnreadNotesCount( NOTES, lastRead );
		expect( unreadCount ).toEqual( 4 );
	} );

	test( 'should return 3, 1 of the notes was read and 1 is deleted', () => {
		const notes = [
			{ ...NOTES[ 0 ] },
			{ ...NOTES[ 1 ] },
			{ ...NOTES[ 2 ] },
			{ ...NOTES[ 3 ], is_deleted: true },
			{ ...NOTES[ 4 ] },
		];
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 3 );
	} );

	test( 'should return 2, 2 of the notes were read and 1 is deleted', () => {
		const notes = [
			{ ...NOTES[ 0 ] },
			{ ...NOTES[ 1 ], date_created_gmt: '2020-05-05T16:57:31' },
			{ ...NOTES[ 2 ] },
			{ ...NOTES[ 3 ], is_deleted: true },
			{ ...NOTES[ 4 ] },
		];
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 2 );
	} );

	test( 'should return 1, 2 of the notes were read, 1 was actioned and 1 is deleted', () => {
		const notes = [
			{ ...NOTES[ 0 ] },
			{ ...NOTES[ 1 ], date_created_gmt: '2020-05-05T16:57:31' },
			{ ...NOTES[ 2 ] },
			{ ...NOTES[ 3 ], is_deleted: true },
			{ ...NOTES[ 4 ], status: 'actioned' },
		];
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 1 );
	} );

	test( 'should return 0, 2 of the notes were read and 2 are deleted', () => {
		const notes = [
			{ ...NOTES[ 0 ] },
			{ ...NOTES[ 1 ], date_created_gmt: '2020-05-05T16:57:31' },
			{ ...NOTES[ 2 ], is_deleted: true },
			{ ...NOTES[ 3 ], is_deleted: true },
			{ ...NOTES[ 4 ], status: 'actioned' },
		];
		const unreadCount = getUnreadNotesCount( notes, lastRead );
		expect( unreadCount ).toEqual( 0 );
	} );
} );

describe( 'hasValidNotes', () => {
	test( 'should return true, 2 notes are deleted', () => {
		const notes = [
			{ ...NOTES[ 0 ] },
			{ ...NOTES[ 1 ] },
			{ ...NOTES[ 2 ], is_deleted: true },
			{ ...NOTES[ 3 ], is_deleted: true },
			{ ...NOTES[ 4 ] },
		];
		expect( hasValidNotes( notes ) ).toBeTruthy();
	} );
	test( 'should return false, all notes are deleted', () => {
		const notes = [
			{ ...NOTES[ 0 ], is_deleted: true },
			{
				...NOTES[ 1 ],
				is_deleted: true,
			},
			{ ...NOTES[ 2 ], is_deleted: true },
			{ ...NOTES[ 3 ], is_deleted: true },
			{ ...NOTES[ 4 ], is_deleted: true },
		];
		expect( hasValidNotes( notes ) ).toBeFalsy();
	} );
} );

describe( 'inbox_panel_view_event', () => {
	test( 'should fire when panel rendered', () => {
		useSelect.mockImplementation( () => ( {
			notes: NOTES,
			isError: false,
			notesHaveResolved: true,
			isBatchUpdating: false,
		} ) );
		render( <InboxPanel /> );

		expect( recordEvent ).toHaveBeenCalledWith( 'inbox_panel_view', {
			total: 5,
		} );
	} );
} );

describe( 'inbox_note_view event', () => {
	test( 'should fire when inbox note card calls onNoteVisible', () => {
		useSelect.mockImplementation( () => ( {
			notes: NOTES,
			isError: false,
			notesHaveResolved: true,
			isBatchUpdating: false,
		} ) );
		// The original InboxNotecard has a VisibilityDetector so I preferred to mock it and always call onNoteVisible
		InboxNoteCard.mockImplementation( ( { onNoteVisible, note } ) => {
			useEffect( () => onNoteVisible( note ), [] );
			return <div>{ note.id }</div>;
		} );
		render( <InboxPanel /> );

		expect( recordEvent ).toHaveBeenCalledWith( 'inbox_note_view', {
			note_content: NOTES[ 0 ].content,
			note_name: NOTES[ 0 ].name,
			note_title: NOTES[ 0 ].title,
			note_type: NOTES[ 0 ].type,
			screen: '',
		} );
	} );
} );

describe( 'inbox_action_click event', () => {
	test( 'should fire tracks event when inboxNoteCard fires onBodyLinkClick', () => {
		useSelect.mockImplementation( () => ( {
			notes: NOTES,
			isError: false,
			notesHaveResolved: true,
			isBatchUpdating: false,
		} ) );
		InboxNoteCard.mockImplementation( ( { onBodyLinkClick, note } ) => {
			return (
				<button onClick={ () => onBodyLinkClick( note, 'innerLink' ) }>
					Trigger action
				</button>
			);
		} );
		const { getAllByText } = render( <InboxPanel /> );
		const buttons = getAllByText( 'Trigger action' );
		userEvent.click( buttons[ 0 ] );
		expect( recordEvent ).toHaveBeenCalledWith( 'inbox_action_click', {
			note_name: NOTES[ 0 ].name,
			note_title: NOTES[ 0 ].title,
			note_content_inner_link: 'innerLink',
		} );
	} );
} );
