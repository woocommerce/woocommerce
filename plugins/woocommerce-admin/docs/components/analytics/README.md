Analytics Components
====================

This folder contains components internally used by `woocommmerce-admin`.

## How to use:

Analytics components can be imported by their relative or absolute path.

```jsx
import ReportChart from 'analytics/components/report-chart';

render: function() {
  return (
    <ReportChart
      charts={ charts }
      endpoint={ endpoint }
      path={ path }
      query={ query }
      selectedChart={ selectedChart }
    />
  );
}
```
