/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { DefaultProgressTitle } from '../default-progress-title';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

describe( 'default-progress-title', () => {
	( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
		fn( () => ( {
			getTaskList: () => ( {
				tasks: [],
			} ),
			hasFinishedResolution: () => true,
		} ) )
	);

	it( 'should render "Welcome to your store" when no tasks are completed and visited', () => {
		render( <DefaultProgressTitle taskListId="1" /> );
		expect(
			screen.getByText( 'Welcome to your store' )
		).toBeInTheDocument();
	} );

	it( 'should render "Welcome to your store" when all tasks are completed', () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getTaskList: () => ( {
					tasks: [
						{ isVisited: true, isComplete: true },
						{ isVisited: true, isComplete: true },
					],
				} ),
				hasFinishedResolution: () => true,
			} ) )
		);
		render( <DefaultProgressTitle taskListId="1" /> );
		expect(
			screen.getByText( 'Welcome to your store' )
		).toBeInTheDocument();
	} );

	it( 'should render "Let’s get you started" when has task visited and task completed count <= 3', () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getTaskList: () => ( {
					tasks: [ { isVisited: true, isComplete: false } ],
				} ),
				hasFinishedResolution: () => true,
			} ) )
		);
		render( <DefaultProgressTitle taskListId="1" /> );
		expect(
			screen.getByText( 'Let’s get you started', { exact: false } )
		).toBeInTheDocument();
	} );

	it( 'should render "You’re on the right track" when has task visited and task completed count > 3', () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getTaskList: () => ( {
					tasks: [
						{ isVisited: true, isComplete: true },
						{ isVisited: true, isComplete: true },
						{ isVisited: true, isComplete: true },
						{ isVisited: true, isComplete: true },
						{ isVisited: true, isComplete: false },
					],
				} ),
				hasFinishedResolution: () => true,
			} ) )
		);
		render( <DefaultProgressTitle taskListId="1" /> );
		expect(
			screen.getByText( 'You’re on the right track', { exact: false } )
		).toBeInTheDocument();
	} );

	it( 'should render "You’re almost there" when has task visited and task completed count > 5', () => {
		( useSelect as jest.Mock ).mockImplementation( ( fn ) =>
			fn( () => ( {
				getTaskList: () => ( {
					tasks: [
						{ isVisited: true, isComplete: true },
						{ isVisited: true, isComplete: true },
						{ isVisited: true, isComplete: true },
						{ isVisited: true, isComplete: true },
						{ isVisited: true, isComplete: true },
						{ isVisited: true, isComplete: true },
						{ isVisited: true, isComplete: false },
					],
				} ),
				hasFinishedResolution: () => true,
			} ) )
		);
		render( <DefaultProgressTitle taskListId="1" /> );
		expect(
			screen.getByText( 'You’re almost there', { exact: false } )
		).toBeInTheDocument();
	} );
} );
