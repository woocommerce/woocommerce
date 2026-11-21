/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { TaskType } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import DefaultTaskHeader from '../default';

jest.mock( '@wordpress/components', () => ( {
	Button: ( {
		children,
		onClick,
	}: {
		children: React.ReactNode;
		onClick?: () => void;
	} ) => <button onClick={ onClick }>{ children }</button>,
} ) );

const baseTask = {
	id: 'third-party-task',
	title: 'Third party task',
	content: 'A task added by an extension.',
	actionLabel: 'Get started',
	imageUrl: 'https://example.com/custom-illustration.png',
	imageAlt: 'Custom extension illustration',
	isComplete: false,
} as TaskType;

describe( 'DefaultTaskHeader', () => {
	it( 'renders the task title, content, image and action button', () => {
		const goToTask = jest.fn();
		render( <DefaultTaskHeader task={ baseTask } goToTask={ goToTask } /> );

		expect( screen.getByText( 'Third party task' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'A task added by an extension.' )
		).toBeInTheDocument();

		const image = screen.getByRole( 'img', {
			name: 'Custom extension illustration',
		} );
		expect( image ).toHaveAttribute(
			'src',
			'https://example.com/custom-illustration.png'
		);

		expect(
			screen.getByRole( 'button', { name: 'Get started' } )
		).toBeInTheDocument();
	} );

	it( 'calls goToTask when the action button is clicked', async () => {
		const goToTask = jest.fn();
		render( <DefaultTaskHeader task={ baseTask } goToTask={ goToTask } /> );

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Get started' } )
		);
		expect( goToTask ).toHaveBeenCalled();
	} );

	it( 'falls back to the default action label when none is provided', () => {
		const goToTask = jest.fn();
		const task = { ...baseTask, actionLabel: undefined } as TaskType;
		render( <DefaultTaskHeader task={ task } goToTask={ goToTask } /> );

		expect(
			screen.getByRole( 'button', { name: "Let's go" } )
		).toBeInTheDocument();
	} );

	it( 'renders nothing when the task has no image', () => {
		const goToTask = jest.fn();
		const task = { ...baseTask, imageUrl: undefined } as TaskType;
		const { container } = render(
			<DefaultTaskHeader task={ task } goToTask={ goToTask } />
		);

		expect( container ).toBeEmptyDOMElement();
	} );
} );
