/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import StarIcon from 'gridicons/dist/star';
import StarOutlineIcon from 'gridicons/dist/star-outline';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Rating from '../';
import ProductRating from '../product';
import ReviewRating from '../review';

describe( 'Rating', () => {
	test( 'should render the passed rating prop', () => {
		const { container } = render( <Rating rating={ 4 } /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render the correct amount of total stars', () => {
		const { container } = render(
			<Rating rating={ 4 } totalStars={ 6 } />
		);
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render stars at a different size', () => {
		const { container } = render( <Rating rating={ 1 } size={ 36 } /> );
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render different icons if specified', () => {
		const { container } = render(
			<Rating
				rating={ 2 }
				icon={ StarOutlineIcon }
				outlineIcon={ StarIcon }
			/>
		);
		expect( container ).toMatchSnapshot();
	} );
} );

describe( 'ReviewRating', () => {
	test( 'should render rating based on review object', () => {
		const review = {
			review: 'Nice T-shirt!',
			rating: 1.5,
		};
		const { container } = render( <ReviewRating review={ review } /> );
		expect( container ).toMatchSnapshot();
	} );
} );

describe( 'ProductRating', () => {
	test( 'should render rating based on product object', () => {
		const product = {
			name: 'Test Product',
			average_rating: 2.5,
		};
		const { container } = render( <ProductRating product={ product } /> );
		expect( container ).toMatchSnapshot();
	} );
} );
