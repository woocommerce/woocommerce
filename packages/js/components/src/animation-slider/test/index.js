/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AnimationSlider from '../';

describe( 'AnimationSlider', () => {
	test( 'it renders correctly', () => {
		const component = render(
			<AnimationSlider animationKey={ 0 } animate={ 'left' }>
				{ () => <div /> }
			</AnimationSlider>
		);
		expect( component ).toMatchSnapshot();
	} );
} );
