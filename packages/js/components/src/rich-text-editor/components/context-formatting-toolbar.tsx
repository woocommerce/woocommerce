/**
 * External dependencies
 */
import { createElement } from "@wordpress/element";
import { Slot, Toolbar, ToolbarGroup } from "@wordpress/components";

import { HeadingTransform } from "../transforms/heading-transform";
// import { ListTransform } from "../transforms/ListTransform";

export const CONTEXT_FORMAT_TOOLBAR_SLOT_NAME = "dayone/context-format-toolbar";

export const ContextFormattingToolbar = () => {
  return (
    <Toolbar>
      {/* Rich text formatting options  */}
      <ToolbarGroup>
        <Slot name={CONTEXT_FORMAT_TOOLBAR_SLOT_NAME} />
      </ToolbarGroup>
      {/* Heading transforms */}
      <ToolbarGroup>
        <HeadingTransform isContextMenu headingLevel={1} />
        <HeadingTransform isContextMenu headingLevel={2} />
        <HeadingTransform isContextMenu headingLevel={3} />
      </ToolbarGroup>
      {/* List transforms */}
      {/* <ToolbarGroup>
        <ListTransform isContextMenu listType="unordered" />
        <ListTransform isContextMenu listType="ordered" />
        <ListTransform isContextMenu listType="checklist" />
      </ToolbarGroup> */}
    </Toolbar>
  );
};
