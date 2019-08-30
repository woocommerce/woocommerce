```jsx
import { List } from '@woocommerce/components';
import Gridicon from 'gridicons';

const listItems = [
    {
        title: 'List item title',
        content: 'List item description text',
    },
    {
        before: <Gridicon icon="star" />,
        title: 'List item with before icon',
        content: 'List item description text',
    },
    {
        before: <Gridicon icon="star" />,
        after: <Gridicon icon="chevron-right" />,
        title: 'List item with before and after icons',
        content: 'List item description text',
    },
    {
        title: 'Clickable list item',
        content: 'List item description text',
        onClick: () => alert( 'List item clicked' ),
    },
];

const MyList = () => (
	<div>
		<List items={ listItems } />
	</div>
);
```
