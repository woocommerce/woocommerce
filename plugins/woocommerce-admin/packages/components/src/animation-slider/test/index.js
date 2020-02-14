/**
 * External dependencies
 */
import renderer from 'react-test-renderer';

/**
 * Internal dependencies
 */
import AnimationSlider from '../';

describe( 'AnimationSlider', () => {
	test( 'it renders correctly', () => {
		const tree = renderer
			.create(
				<AnimationSlider animationKey={ 0 } animate={ 'left' }>
					{ () => <div /> }
				</AnimationSlider>
			)
			.toJSON();
		expect( tree ).toMatchSnapshot();
	} );
} );
