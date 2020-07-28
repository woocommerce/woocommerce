/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import Gridicon from 'gridicons';
import { shallow } from 'enzyme';
import moment from 'moment';

/**
 * Internal dependencies
 */
import { ActivityCard } from '../';
import { Gravatar } from '@woocommerce/components';

describe( 'ActivityCard', () => {
	test( 'should have correct title', () => {
		const card = (
			<ActivityCard title="Inbox message">
				This card has some content
			</ActivityCard>
		);
		expect( card.props.title ).toBe( 'Inbox message' );
	} );

	test( 'should render a basic card', () => {
		const card = shallow(
			<ActivityCard title="Inbox message">
				This card has some content
			</ActivityCard>
		);
		expect( card ).toMatchSnapshot();
	} );

	test( 'should render an unread bubble on a card', () => {
		const card = shallow(
			<ActivityCard title="Inbox message" unread>
				This card has some content
			</ActivityCard>
		);
		expect( card ).toMatchSnapshot();
	} );

	test( 'should render a custom icon on a card', () => {
		const card = shallow(
			<ActivityCard
				title="Inbox message"
				icon={ <Gridicon icon="customize" /> }
			>
				This card has some content
			</ActivityCard>
		);
		expect( card ).toMatchSnapshot();
	} );

	test( 'should render a gravatar on a card', () => {
		const card = shallow(
			<ActivityCard
				title="Inbox message"
				icon={ <Gravatar user="admin@local.test" /> }
			>
				This card has some content
			</ActivityCard>
		);
		expect( card ).toMatchSnapshot();
	} );

	test( 'should render a timestamp on a card', () => {
		// We're generating this via moment to ensure it's always "3 days ago".
		const threeDaysAgo = moment().subtract( 3, 'days' ).format();
		const card = shallow(
			<ActivityCard title="Inbox message" date={ threeDaysAgo }>
				This card has some content
			</ActivityCard>
		);
		expect( card ).toMatchSnapshot();
	} );

	test( 'should render an action on a card', () => {
		const noop = () => {};
		const card = shallow(
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
		expect( card ).toMatchSnapshot();
	} );

	test( 'should render multiple actions on a card', () => {
		const noop = () => {};
		const card = shallow(
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
		expect( card ).toMatchSnapshot();
	} );
} );
