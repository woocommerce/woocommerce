/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { OverwriteConfirmationModal } from '../overwrite-confirmation-modal';

const defaultProps = {
	isOpen: true,
	isImporting: false,
	onClose: () => {},
	onConfirm: () => {},
	overwrittenItems: [],
};

describe( 'OverwriteConfirmationModal', () => {
	it( 'should always state that a Blueprint is only as trustworthy as its source', () => {
		render( <OverwriteConfirmationModal { ...defaultProps } /> );

		// The notice is also announced through a live region, so the copy is
		// expected to appear more than once in the document.
		expect(
			screen.getAllByText( /Only import files from a source you trust/ )
				.length
		).toBeGreaterThan( 0 );
	} );

	it( 'should list the actions a Blueprint takes beyond writing settings', () => {
		render(
			<OverwriteConfirmationModal
				{ ...defaultProps }
				additionalActions={ [
					'Run 34 database queries',
					'Install 2 plugins',
				] }
			/>
		);

		expect( screen.getByText( 'It will also:' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Run 34 database queries' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Install 2 plugins' ) ).toBeInTheDocument();
	} );

	it( 'should not show an empty actions list for a settings-only Blueprint', () => {
		render(
			<OverwriteConfirmationModal
				{ ...defaultProps }
				overwrittenItems={ [ 'General' ] }
			/>
		);

		expect( screen.getByText( 'General' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'It will also:' ) ).not.toBeInTheDocument();
	} );

	it( 'should render nothing when closed', () => {
		const { container } = render(
			<OverwriteConfirmationModal
				{ ...defaultProps }
				isOpen={ false }
				additionalActions={ [ 'Run 34 database queries' ] }
			/>
		);

		expect( container ).toBeEmptyDOMElement();
	} );
} );
