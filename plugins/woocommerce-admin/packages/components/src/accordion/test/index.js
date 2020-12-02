/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Accordion from '../';
import AccordionPanel from '../panel';

const panels = (
	<>
		<AccordionPanel
			className="panel-1"
			count={ 10000 }
			title="panel-1"
			initialOpen={ true }
		>
			<span>Custom panel 1</span>
		</AccordionPanel>
		<AccordionPanel
			className="panel-2"
			count={ 20000 }
			title="panel-2"
			initialOpen={ false }
		>
			<span>Custom panel 1</span>
		</AccordionPanel>
	</>
);

describe( 'Accordion', () => {
	it( 'should render a panel with two rows', () => {
		render( <Accordion> { panels } </Accordion> );
		expect( screen.getByText( 'panel-1' ) ).not.toBeNull();
		expect( screen.getByText( 'panel-2' ) ).not.toBeNull();
	} );

	it( 'should render one visible panel and one hidden panel', () => {
		render( <Accordion> { panels } </Accordion> );
		expect( screen.queryByText( 'Custom panel 1' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Custom panel 2' )
		).not.toBeInTheDocument();
	} );

	it( 'should render the count of unread items', () => {
		render( <Accordion> { panels } </Accordion> );
		expect( screen.queryByText( '10000' ) ).toBeInTheDocument();
		expect( screen.queryByText( '20000' ) ).toBeInTheDocument();
	} );

	it( 'should only render title if collapsible is false', () => {
		render(
			<Accordion>
				{ ' ' }
				<AccordionPanel
					title="empty title"
					initialOpen={ false }
					collapsible={ false }
				>
					<span>Custom panel 1</span>
				</AccordionPanel>
			</Accordion>
		);
		expect( screen.queryByText( 'empty title' ) ).toBeInTheDocument();
		expect( screen.queryByRole( 'button' ) ).toBeNull();
		expect( screen.queryByText( 'Custom panel 1' ) ).toBeNull();
	} );
} );
