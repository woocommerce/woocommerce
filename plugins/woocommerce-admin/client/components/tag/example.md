```jsx
import { Tag } from '@woocommerce/components';

const noop = () => {};

const MyTag = () => (
	<div>
		<Tag label="My tag" id={ 1 } />
		<Tag label="Removable tag" id={ 2 } remove={ noop } />
	</div>
);
```
