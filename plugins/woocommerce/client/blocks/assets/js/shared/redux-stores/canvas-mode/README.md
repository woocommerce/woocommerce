# Canvas Mode Store

⚠️ **Internal Use Only** - This store is for internal WooCommerce use only.

The Canvas Mode Store manages the state of the canvas mode in the template editor, tracking whether we are in edit mode based on the URL parameter `canvas=edit`.

## State

The store maintains a simple state object:

```typescript
type CanvasModeState = {
	isEditMode: boolean;
};
```

## Selectors

### isEditMode()

Returns whether the current view is in edit mode. This is determined by checking if `canvas=edit` is present in the URL.

## Actions

### setCanvasMode( isEditMode: boolean )

Updates the canvas mode state. This action is primarily used internally by the store to sync with URL changes.
