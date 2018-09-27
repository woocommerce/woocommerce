```jsx
import { Pagination } from '@woocommerce/components';

const MyPagination = withState( {
	page: 2,
	perPage: 50,
} )( ( { page, perPage, setState } ) => (
	<Pagination
		page={ page }
		perPage={ perPage }
		total={ 500 }
		onPageChange={ ( page ) => setState( { page } ) }
		onPerPageChange={ ( perPage ) => setState( { perPage } ) }
	/>
) );
```
