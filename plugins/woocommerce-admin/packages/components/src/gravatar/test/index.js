/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Gravatar from '../';

describe( 'Gravatar', () => {
	test( 'should fallback to default mystery person Gravatar', () => {
		const { getByRole } = render( <Gravatar /> );
		expect( getByRole( 'img' ) ).toHaveAttribute(
			'src',
			'https://www.gravatar.com/avatar/0?s=60&d=mp'
		);
	} );

	test( 'should return a resized avatar from a user object', () => {
		const user = {
			avatar_URLs: {
				24: 'https://www.gravatar.com/avatar/098f6bcd4621d373cade4e832627b4f6?s=24',
				48: 'https://www.gravatar.com/avatar/098f6bcd4621d373cade4e832627b4f6?s=48',
				96: 'https://www.gravatar.com/avatar/098f6bcd4621d373cade4e832627b4f6?s=96',
			},
		};
		const { getByRole } = render( <Gravatar user={ user } /> );
		expect( getByRole( 'img' ) ).toHaveAttribute(
			'src',
			'https://www.gravatar.com/avatar/098f6bcd4621d373cade4e832627b4f6?s=60&d=mp'
		);
	} );

	test( 'should return an avatar from an email address', () => {
		const { getByRole } = render( <Gravatar user="test@example.com" /> );
		expect( getByRole( 'img' ) ).toHaveAttribute(
			'src',
			'https://www.gravatar.com/avatar/55502f40dc8b7c769880b10874abc9d0?s=60&d=mp'
		);
	} );

	test( 'should return a resized image when passed a size', () => {
		const { getByRole } = render(
			<Gravatar user="test@example.com" size={ 40 } />
		);
		expect( getByRole( 'img' ) ).toHaveAttribute(
			'src',
			'https://www.gravatar.com/avatar/55502f40dc8b7c769880b10874abc9d0?s=40&d=mp'
		);
		expect( getByRole( 'img' ) ).toHaveAttribute( 'height', '40' );
		expect( getByRole( 'img' ) ).toHaveAttribute( 'width', '40' );
	} );

	test( 'should return an alt attribute', () => {
		const { getByRole } = render( <Gravatar alt="test" /> );
		expect( getByRole( 'img' ) ).toHaveAttribute( 'alt', 'test' );
	} );
	test( 'should return an alt attribute from a user name', () => {
		const user = {
			avatar_URLs: {
				24: 'https://www.gravatar.com/avatar/098f6bcd4621d373cade4e832627b4f6?s=24',
				48: 'https://www.gravatar.com/avatar/098f6bcd4621d373cade4e832627b4f6?s=48',
				96: 'https://www.gravatar.com/avatar/098f6bcd4621d373cade4e832627b4f6?s=96',
			},
			display_name: 'Justin',
			name: 'Justin',
		};
		const { getByRole } = render( <Gravatar user={ user } /> );
		expect( getByRole( 'img' ) ).toHaveAttribute( 'alt', 'Justin' );
	} );
} );
