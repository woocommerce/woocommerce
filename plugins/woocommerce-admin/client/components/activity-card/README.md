ActivityCard
============

A card designed for use in the timeline/sidebar. This is a very structured component, which expects at minimum a label and content. It can optionally also include a date, actions, an image, and a dropdown menu.

## How to use:

```jsx
import ActivityCard from 'components/activity-card';

render: function() {
  return (
    <ActivityCard
      label="Insight"
      icon={ <Dashicon icon="search" /> }
      date="30 minutes ago"
      actions={ [ <a href="/">Action link</a>, <a href="/">Action link 2</a> ] }
      image={ <Dashicon icon="palmtree" /> }
    >
      Insight content goes in this area here. It will probably be a couple of lines long and may
      include an accompanying image. We might consider color-coding the icon for quicker
      scanning.
    </ActivityCard>
  );
}
```

## Props

* `label`: A title for this card (required).
* `children`: Content used in the body of the action card (required).
* `actions`: A list of links or buttons shown in the footer of the card.
* `date`: The timestamp associated with this activity.
* `icon`: An icon used to label this activity. Defaults to "warning".
* `image`: An image show in this card. Can be `img` or `Dashicon` (or any renderable element).
* `menu`: A dropdown menu (EllipsisMenu) shown at the top-right of the card.
