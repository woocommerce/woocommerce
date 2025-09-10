const HIDDEN_DOC_IDS = [
  'apis', // doc id or slug, relative to docs root
  'cli',
  'extensions',
];

export function filterSidebarItems(items) {
  return items
    .filter(item => {
      // Hide by doc id
      if (item.type === 'category' && HIDDEN_DOC_IDS.includes(item.customProps?.id)) {
        return false;
      }
      return true;
    });
}