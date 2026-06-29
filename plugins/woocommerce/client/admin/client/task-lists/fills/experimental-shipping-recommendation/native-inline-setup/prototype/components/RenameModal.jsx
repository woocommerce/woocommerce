import { useState } from 'react';
import { Modal, Button, TextControl } from '@wordpress/components';

// Small Modal (size="small") for renaming a zone. Real WPDS Modal — focus
// management, Esc to close, and click-outside-to-close come for free.
export default function RenameModal({ zone, onSave, onCancel }) {
  const [value, setValue] = useState(zone.name);

  return (
    <Modal
      title="Rename zone"
      size="small"
      onRequestClose={onCancel}
      focusOnMount="firstContentElement"
    >
      <TextControl
        label="Zone name"
        value={value}
        onChange={setValue}
        help="Zone names are only visible to you in the admin."
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
      <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 16 }}>
        <Button variant="tertiary" onClick={onCancel} __next40pxDefaultSize>
          Cancel
        </Button>
        <Button
          variant="primary"
          onClick={() => onSave(value.trim() || zone.name)}
          disabled={!value.trim()}
          __next40pxDefaultSize
        >
          Save
        </Button>
      </div>
    </Modal>
  );
}
