```jsx
import DateRangeFilterPicker from '@woocommerce/components';

const query = {};

const MyDateRangeFilterPicker = () => (
    <DateRangeFilterPicker
        key="daterange"
        query={ query }
        onRangeSelect={ () => {} }
    />
);
```