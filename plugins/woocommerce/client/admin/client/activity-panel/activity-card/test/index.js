/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ActivityCard } from '../';

describe( 'ActivityCard', () => {
	test( 'should have correct title', () => {
		const { getByRole } = render(
			<ActivityCard title="Inbox message">
				This card has some content
			</ActivityCard>
		);
		expect(
			getByRole( 'heading', { name: 'Inbox message' } )
		).toBeInTheDocument();
	} );
} );
