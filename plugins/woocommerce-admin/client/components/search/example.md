```jsx
import { Search } from '@woocommerce/components';

class MySearch extends Component {
	updateLocalValue( results ) {
		// Do something with results.
	}
	render() {
		return (
			<Search type="products" onChange={ this.updateLocalValue } />
		);
	}
}
```
