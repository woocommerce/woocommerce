ActivityCard
============

A card designed for use in the activity panel. This is a very structured component, which expects at minimum a label and content. It can optionally also include a date, actions, an image, and a dropdown menu.

## How to use:

```jsx
import { ActivityCard } from 'components/activity-card';

render: function() {
  return (
    <ActivityCard
      title="Insight"
      icon={ <Gridicon icon="search" /> }
      date="2018-07-10T00:00:00Z"
      actions={ [ <a href="/">Action link</a>, <a href="/">Action link 2</a> ] }
    >
      Insight content goes in this area here. It will probably be a couple of lines long and may
      include an accompanying image. We might consider color-coding the icon for quicker
      scanning.
    </ActivityCard>
  );
}
```

## Props

* `title`: A title for this card (required).
* `subtitle`: An element rendered right under the title.
* `children`: Content used in the body of the action card (required).
* `actions`: A list of links or buttons shown in the footer of the card.
* `date`: The timestamp associated with this activity.
* `icon`: An icon or avatar used to identify this activity. Defaults to Gridicon "notice-outline".
* `unread`: If this prop is present, the card has a small red bubble indicating an "unread" item. Defaults to false.

ActivityCardPlaceholder
=======================

This component is similar to `ActivityCard` in output, but renders no real content, just loading placeholders. This is also hidden from any interaction with screen readers using `aria-hidden`.

## How to use:

```jsx
import { ActivityCardPlaceholder } from 'components/activity-card';

render: function() {
  return (
    <ActivityCardPlaceholder hasDate />
  );
}
```

## Props

* `hasAction`: Boolean. If true, shows a placeholder block for an action. Default false.
* `hasDate`: Boolean. If true, shows a placeholder block for the date. Default false.
* `hasSubtitle`: Boolean. If true, shows a placeholder block for the subtitle. Default false.
* `lines`: Number. How many lines of placeholder content we should show. Default 1.
