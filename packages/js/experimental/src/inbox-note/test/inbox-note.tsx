/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { InboxNoteCard } from '../inbox-note';

window.open = jest.fn();

// Mock react-intersection-observer
jest.mock( 'react-intersection-observer', () => {
	return {
		useInView: (
			options: { onChange?: ( inView: boolean ) => void } = {}
		) => {
			if ( options.onChange ) {
				// Call onChange with true to simulate element coming into view
				options.onChange( true );
			}
			return {
				ref: jest.fn(),
				inView: true,
			};
		},
	};
} );

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
			{
				id: 2,
				name: 'learnmore',
				label: 'Learn More',
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
		is_read: false,
	};

	it( 'should render the defined action buttons', () => {
		const { queryByText } = render(
			<InboxNoteCard key={ note.id } note={ note } />
		);

		expect( queryByText( 'Connect' ) ).toBeInTheDocument();
		expect( queryByText( 'Learn More' ) ).toBeInTheDocument();
	} );

	it( 'should render anchor when href is defined', () => {
		const { queryByRole } = render(
			<InboxNoteCard key={ note.id } note={ note } />
		);
		expect(
			queryByRole( 'link', {
				name: 'Connect',
			} )
		).toHaveAttribute( 'href', 'http://test.com' );

		expect(
			queryByRole( 'link', {
				name: 'Learn More',
			} )
		).toHaveAttribute( 'href', 'http://test.com' );
	} );

	it( 'should render button when href is not defined', () => {
		const noteWithoutHref = {
			...note,
			actions: [
				{
					id: 1,
					name: 'connect',
					label: 'Connect',
					query: '',
					status: 'unactioned',
					primary: false,
					url: '',
				},
				{
					id: 2,
					name: 'learnmore',
					label: 'Learn More',
					query: '',
					status: 'unactioned',
					primary: false,
					url: '',
				},
			],
		};

		const { queryByRole } = render(
			<InboxNoteCard key={ note.id } note={ noteWithoutHref } />
		);
		expect(
			queryByRole( 'link', {
				name: 'Connect',
			} )
		).not.toBeInTheDocument();

		expect(
			queryByRole( 'link', {
				name: 'Learn More',
			} )
		).not.toBeInTheDocument();

		expect(
			queryByRole( 'button', {
				name: 'Connect',
			} )
		).toBeInTheDocument();

		expect(
			queryByRole( 'button', {
				name: 'Learn More',
			} )
		).toBeInTheDocument();
	} );

	it( 'should render a dismiss button', () => {
		const { queryByText } = render(
			<InboxNoteCard key={ note.id } note={ note } />
		);
		expect( queryByText( 'Dismiss' ) ).toBeInTheDocument();
	} );

	it( 'should render a notification type thumbnail', () => {
		const thumbnailNote = { ...note, layout: 'thumbnail' };
		const { container } = render(
			<InboxNoteCard key={ thumbnailNote.id } note={ thumbnailNote } />
		);
		const listNoteWithThumbnail = container.querySelector( '.thumbnail' );
		expect( listNoteWithThumbnail ).not.toBeNull();
	} );

	it( 'should render a read notification', () => {
		const noteWithoutActions = {
			...{ ...note, is_read: true },
			actions: [],
		};
		const { container } = render(
			<InboxNoteCard key={ note.id } note={ noteWithoutActions } />
		);
		const unreadNote = container.querySelector( '.message-is-unread' );
		const readNote = container.querySelector(
			'.woocommerce-inbox-message'
		);
		expect( unreadNote ).toBeNull();
		expect( readNote ).not.toBeNull();
	} );

	it( 'should render an unread notification', () => {
		const noteWithoutActions = {
			...note,
			actions: [],
		};
		const { container } = render(
			<InboxNoteCard key={ note.id } note={ noteWithoutActions } />
		);
		const unreadNote = container.querySelector( '.message-is-unread' );
		expect( unreadNote ).not.toBeNull();
	} );

	it( 'should not render any notification', () => {
		const deletedNote = { ...note, is_deleted: true };
		const { container } = render(
			<InboxNoteCard key={ note.id } note={ deletedNote } />
		);
		const unreadNote = container.querySelector(
			'.woocommerce-inbox-message'
		);
		expect( unreadNote ).toBeNull();
	} );

	describe( 'callbacks', () => {
		it( 'should call onDismiss with note when "Dismiss this message" is clicked', () => {
			const onDismiss = jest.fn();
			const { getByText } = render(
				<InboxNoteCard
					key={ note.id }
					note={ note }
					onDismiss={ onDismiss }
				/>
			);
			userEvent.click( getByText( 'Dismiss' ) );
			expect( onDismiss ).toHaveBeenCalledWith( note );
		} );

		it( 'should call onNoteActionClick with specific action when action is clicked', () => {
			const onNoteActionClick = jest.fn();
			const { getByText } = render(
				<InboxNoteCard
					key={ note.id }
					note={ note }
					onNoteActionClick={ onNoteActionClick }
				/>
			);
			userEvent.click( getByText( 'Learn More' ) );
			expect( onNoteActionClick ).toHaveBeenCalledWith(
				note,
				note.actions[ 1 ]
			);
		} );

		it( 'should call onBodyLinkClick with innerLink if link within content is clicked', () => {
			const onBodyLinkClick = jest.fn();
			const noteWithInnerLink = {
				...note,
				content:
					note.content +
					' <a href="http://somewhere.com">Somewhere</a>',
			};
			const { getByText } = render(
				<InboxNoteCard
					key={ noteWithInnerLink.id }
					note={ noteWithInnerLink }
					onBodyLinkClick={ onBodyLinkClick }
				/>
			);
			userEvent.click( getByText( 'Somewhere' ) );
			expect( onBodyLinkClick ).toHaveBeenCalledWith(
				noteWithInnerLink,
				'http://somewhere.com/'
			);
		} );

		it( 'should call onVisible when element is in view', () => {
			const onVisible = jest.fn();
			render(
				<InboxNoteCard
					key={ note.id }
					note={ note }
					onNoteVisible={ onVisible }
				/>
			);

			expect( onVisible ).toHaveBeenCalledWith( note );
		} );
	} );
} );
