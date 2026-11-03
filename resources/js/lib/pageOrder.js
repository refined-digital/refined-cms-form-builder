/**
 * Rebuild the full field order for a paged form: pages in tab order, each
 * page's fields in their current order, with the current page's fields in the
 * given order. Fields on no known page keep their order at the end.
 *
 * @param {Array} fields every field, in their current global order
 * @param {Array} pages the pages, in tab order
 * @param {number|null} currentPageId the page whose order currentFields gives
 * @param {Array} currentFields the current page's fields, in their new order
 * @returns {Array}
 */
export function composeFields(fields, pages, currentPageId, currentFields) {
  const rest = fields.filter((f) => !currentFields.includes(f));
  const ordered = [];
  pages.forEach((p) => {
    const list = p.id === currentPageId
      ? currentFields
      : rest.filter((f) => Number(f.form_page_id) === p.id);
    ordered.push(...list);
  });
  const placed = new Set(ordered);
  return [...ordered, ...rest.filter((f) => !placed.has(f))];
}
