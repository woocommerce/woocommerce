# Email Editor Hacks

We intentionally call these "hacks" because they are workarounds for missing upstream APIs in WordPress/Gutenberg. **Our goal is to work upstream with the WordPress and Gutenberg teams to get proper APIs added so we can remove these workarounds.**

Each hack below represents functionality that should ideally be supported through official WordPress/Gutenberg APIs.

## Current Hacks

### 1. Notices Slot ([notices-slot.tsx](notices-slot.tsx))

**Purpose:** Renders a portal for displaying notices before the visual editor.

**Missing Upstream API:** There is currently no API to add notices with custom context to the content area in the block editor.

**What it does:** Creates a DOM portal that inserts a container as the first child of the visual editor, allowing notices to be rendered in a custom location.

**Upstream solution needed:** An official API for registering custom notice contexts and controlling notice placement within the editor.

---

### 2. Publish/Save Button Management ([publish-save.tsx](publish-save.tsx))

**Purpose:** Conditionally hides the default publish button and adds a custom "Send" button for email posts.

**Missing Upstream API:** No official way to customize or replace the publish button behavior based on specific post type requirements.

**What it does:**

- Uses DOM manipulation and MutationObserver to hide/show the default publish button
- Creates a portal to render a custom "Send" button next to the publish button
- Determines visibility based on whether changes are in the post, template, or both

**Upstream solution needed:** An official API for customizing or replacing the publish button, including the ability to register custom save actions for specific post types.

---

### 3. Move to Trash Action ([move-to-trash.tsx](move-to-trash.tsx))

**Purpose:** Replaces the default "Move to Trash" action with a custom implementation for email posts.

**Missing Upstream API:** While entity actions can be registered, there's no clean way to customize the built-in trash behavior without unregistering and re-registering.

**What it does:**

- Unregisters the default `move-to-trash` action
- Registers a custom trash action specific to email posts
- Hooks into both `core.registerPostTypeSchema` (WP 6.8+) and `core.registerPostTypeActions` (WP 6.7+) for compatibility

**Upstream solution needed:** A filter or configuration option to customize built-in entity actions without needing to unregister and re-register them.

---

### 4. Template Actions Modification ([modify-template-actions.tsx](modify-template-actions.tsx))

**Purpose:** Adapts template actions for email templates to work around the "Active Templates" feature introduced in WordPress/Gutenberg.

**What it does:**

- Removes the default "Duplicate" action (not needed for email templates)
- Removes the default Gutenberg "Reset" action
- Registers a custom reset template action that maintains previous behavior
- Applies modifications only to `wp_template` post type

**Solution needed:** We need to find out how to align the email editor with the Active Templates feature. We should aim to integrate with the Site editor to add support for email templates and that way we could support the template management and new actions introduced in the Active Templates feature.

---
