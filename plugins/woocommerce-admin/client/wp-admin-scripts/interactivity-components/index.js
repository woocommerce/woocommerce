/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { TextControlWrapper } from './components/text-control-wrapper';
import { getPropertyNameFromInput } from './utils';

const componentElements = document.querySelectorAll( '[data-react-component]' );

componentElements.forEach( ( element ) => {
	const property = getPropertyNameFromInput(
		element.querySelector( 'input' )?.name || ''
	);
	const label = element.querySelector( 'label' )?.textContent;

	render(
		<TextControlWrapper property={ property } label={ label } />,
		element
	);
} );
