/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import CustomizeIcon from 'gridicons/dist/customize';
import moment from 'moment';
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

	test( 'should render a basic card', () => {
		const { container } = render(
			<ActivityCard title="Inbox message">
				This card has some content
			</ActivityCard>
		);
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render an unread bubble on a card', () => {
		const { container } = render(
			<ActivityCard title="Inbox message" unread>
				This card has some content
			</ActivityCard>
		);
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render a custom icon on a card', () => {
		const { container } = render(
			<ActivityCard title="Inbox message" icon={ <CustomizeIcon /> }>
				This card has some content
			</ActivityCard>
		);
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render a timestamp on a card', () => {
		// We're generating this via moment to ensure it's always "3 days ago".
		const threeDaysAgo = moment().subtract( 3, 'days' ).format();
		const { container } = render(
			<ActivityCard title="Inbox message" date={ threeDaysAgo }>
				This card has some content
			</ActivityCard>
		);
		expect( container ).toMatchSnapshot();
	} );

	test( 'supports a non-date "date" prop on a card', () => {
		// We should be able to provide any string to the date prop.
		const { container } = render(
			<ActivityCard title="Inbox message" date="A long, long time ago">
				This card has some content
			</ActivityCard>
		);
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render an action on a card', () => {
		const noop = () => {};
		const { container } = render(
			<ActivityCard
				title="Inbox message"
				actions={
					<Button isSecondary onClick={ noop }>
						Action
					</Button>
				}
			>
				This card has some content
			</ActivityCard>
		);
		expect( container ).toMatchSnapshot();
	} );

	test( 'should render multiple actions on a card', () => {
		const noop = () => {};
		const { container } = render(
			<ActivityCard
				title="Inbox message"
				actions={ [
					<Button key="action1" isPrimary onClick={ noop }>
						Action 1
					</Button>,
					<Button key="action2" isSecondary onClick={ noop }>
						Action 2
					</Button>,
				] }
			>
				This card has some content
			</ActivityCard>
		);
		expect( container ).toMatchSnapshot();
	} );
} );
