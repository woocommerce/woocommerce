/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Tabs } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { TemplateCategoriesListSidebar } from '../template-categories-list-sidebar';
import type { TemplateCategory } from '../../../store';

const categories: Array< { name: TemplateCategory; label: string } > = [
	{ name: 'recent', label: 'Recent' },
	{ name: 'basic', label: 'Basic' },
];

describe( 'TemplateCategoriesListSidebar', () => {
	it( 'renders one tab per category', () => {
		render(
			<Tabs.Root orientation="vertical" value="basic">
				<TemplateCategoriesListSidebar
					templateCategories={ categories }
				/>
			</Tabs.Root>
		);

		const tabs = screen.getAllByRole( 'tab' );
		expect( tabs ).toHaveLength( 2 );
		expect( tabs[ 0 ] ).toHaveTextContent( 'Recent' );
		expect( tabs[ 1 ] ).toHaveTextContent( 'Basic' );
		expect( tabs[ 1 ] ).toHaveAttribute( 'aria-selected', 'true' );
	} );

	it( 'reports the clicked category', async () => {
		const onValueChange = jest.fn();
		render(
			<Tabs.Root
				orientation="vertical"
				value="basic"
				onValueChange={ onValueChange }
			>
				<TemplateCategoriesListSidebar
					templateCategories={ categories }
				/>
			</Tabs.Root>
		);

		await userEvent.click( screen.getByRole( 'tab', { name: 'Recent' } ) );

		expect( onValueChange ).toHaveBeenCalledWith(
			'recent',
			expect.anything()
		);
	} );
} );
