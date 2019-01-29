```jsx
import { Flag } from '@woocommerce/components';

const MyFlag = () => (
	<div>
		<H>Default (inherits parent font size)</H>
		<Section component={ false }>
			<Flag code="VU"  />
		</Section>
		
		<H>Large</H>
		<Section component={ false }>
			<Flag code="VU" size={ 48 } />
		</Section>
		
		<H>Invalid Country Code</H>
		<Section component={ false }>
			<Flag code="invalid country code" />
		</Section>
	</div>
);
```
