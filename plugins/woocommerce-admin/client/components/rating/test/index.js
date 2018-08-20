/** @format */
/**
 * External dependencies
 */
import { shallow } from 'enzyme';

/**
 * Internal dependencies
 */
import Rating from '../';
import ProductRating from '../product';
import ReviewRating from '../review';

describe( 'Rating', () => {
	test( 'should render the passed rating prop', () => {
		const rating = shallow( <Rating rating={ 4 } /> );
		expect( rating ).toMatchSnapshot();
	} );

	test( 'should render the correct amount of total stars', () => {
		const rating = shallow( <Rating rating={ 4 } totalStars={ 6 } /> );
		expect( rating ).toMatchSnapshot();
	} );

	test( 'should render stars at a different size', () => {
		const rating = shallow( <Rating rating={ 1 } size={ 36 } /> );
		expect( rating ).toMatchSnapshot();
	} );
} );

describe( 'ReviewRating', () => {
	test( 'should render rating based on review object', () => {
		const review = {
			review: 'Nice T-shirt!',
			rating: 1.5,
		};
		const rating = shallow( <ReviewRating review={ review } /> );
		expect( rating ).toMatchSnapshot();
	} );
} );

describe( 'ProductRating', () => {
	test( 'should render rating based on product object', () => {
		const product = {
			name: 'Test Product',
			average_rating: 2.5,
		};
		const rating = shallow( <ProductRating product={ product } /> );
		expect( rating ).toMatchSnapshot();
	} );
} );
