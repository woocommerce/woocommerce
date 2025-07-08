/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import FormStep from '..';

describe( 'FormStep', () => {
	test( 'should not render fieldset if title or legend is not provided', () => {
		render( <FormStep>Dolor sit amet</FormStep> );
		expect( screen.queryByRole( 'group' ) ).not.toBeInTheDocument();
	} );

	test( 'should apply id and className props', () => {
		const { container } = render(
			<FormStep id="my-id" className="my-classname">
				Dolor sit amet
			</FormStep>
		);

		const element = container.querySelector( '#my-id' );

		expect( element ).toBeDefined();
		// Look eslint and typescript, we're checking it above
		// eslint-disable-next-line @typescript-eslint/no-non-null-assertion
		expect( element!.classList.contains( 'my-classname' ) ).toBeTruthy();
	} );

	test( 'should render a fieldset if a legend is provided', () => {
		render( <FormStep legend="Lorem Ipsum 2">Dolor sit amet</FormStep> );
		expect(
			screen.queryByRole( 'group', { name: 'Lorem Ipsum 2' } )
		).toBeVisible();
	} );

	test( 'should render a fieldset with heading if a title is provided', () => {
		render( <FormStep title="Lorem Ipsum">Dolor sit amet</FormStep> );

		expect(
			screen.queryByRole( 'group', { name: 'Lorem Ipsum' } )
		).toBeVisible();
	} );

	test( 'fieldset legend should default to legend prop when title and legend are defined', () => {
		render(
			<FormStep title="Lorem Ipsum" legend="Lorem Ipsum 2">
				Dolor sit amet
			</FormStep>
		);

		expect(
			screen.queryByRole( 'group', { name: 'Lorem Ipsum 2' } )
		).toBeVisible();
	} );

	test( 'should remove step number CSS class if prop is false', () => {
		const { container } = render(
			<FormStep title="Lorem Ipsum" showStepNumber={ false }>
				Dolor sit amet
			</FormStep>
		);

		expect(
			container.querySelector(
				'.wc-block-components-checkout-step--with-step-number'
			)
		).not.toBeInTheDocument();
	} );

	test( 'should render step heading content', () => {
		render(
			<FormStep
				title="Lorem Ipsum"
				stepHeadingContent={ () => (
					<span>Some context to render next to the heading</span>
				) }
			>
				Dolor sit amet
			</FormStep>
		);

		expect(
			screen.getByText( 'Some context to render next to the heading' )
		).toBeVisible();
	} );

	test( 'should render step description', () => {
		render(
			<FormStep title="Lorem Ipsum" description="This is the description">
				Dolor sit amet
			</FormStep>
		);

		expect( screen.getByText( 'This is the description' ) ).toBeVisible();
	} );

	test( 'should set disabled prop to the fieldset element when disabled is true', () => {
		render(
			<FormStep title="Lorem Ipsum" disabled={ true }>
				Dolor sit amet
			</FormStep>
		);

		expect( screen.getByRole( 'group' ) ).toBeDisabled();
	} );
} );
