```jsx
import { Tag } from '@woocommerce/components';

const noop = () => {};

const MyTag = () => (
	<div>
		<Tag label="My tag" id={ 1 } />
		<Tag label="Removable tag" id={ 2 } remove={ noop } />
		<Tag label="Tag with popover" popoverContents={ ( <p>This is a popover</p> ) } />
	</div>
);
```
