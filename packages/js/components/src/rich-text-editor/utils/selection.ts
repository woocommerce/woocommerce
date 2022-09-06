const isSelectionWithin = (querySelector: string) => {
    const selection = window.getSelection();
    if (selection?.rangeCount) {
      const node = selection.getRangeAt(0).startContainer as Element;
      const nodeParent = node.parentElement as Element; // there's always a parent element unless you are trying to run this with `document`
      return (
        (node.closest && !!node.closest(querySelector)) ||
        (nodeParent.closest && !!nodeParent.closest(querySelector))
      );
    }
    return false;
  };
  
  export const isCurrentSelectionWithinEditor = () => {
    return isSelectionWithin(".block-editor-writing-flow");
  };
  