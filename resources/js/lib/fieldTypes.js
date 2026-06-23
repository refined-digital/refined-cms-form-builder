// Field-type id metadata, mirroring the load-bearing integer ids seeded in
// FieldTypeTableSeeder. Kept in sync with the server-side special-cases.
export const TYPE = {
  TEXT: 1,
  TEXTAREA: 2,
  SELECT: 3,
  RADIO: 4,
  CHECKBOX: 5,
  SINGLE_CHECKBOX: 6,
  NUMBER: 7,
  EMAIL: 8,
  TEL: 9,
  PASSWORD: 10,
  PASSWORD_CONFIRM: 11,
  HIDDEN: 12,
  YESNO: 13,
  COUNTRY: 14,
  DATE: 15,
  DATETIME: 16,
  FILE: 17,
  FILES: 18,
  STATIC: 19,
  CUSTOM: 20,
  DOB: 21,
  GROUP_START: 22,
  GROUP_END: 23,
};

// types whose values come from FormFieldOption rows (the editor shows an options builder)
export const TYPES_WITH_OPTIONS = [TYPE.SELECT, TYPE.RADIO, TYPE.CHECKBOX];

// types that accept a placeholder
export const TYPES_WITH_PLACEHOLDER = [
  TYPE.TEXT, TYPE.TEXTAREA, TYPE.SELECT, TYPE.NUMBER, TYPE.EMAIL, TYPE.TEL,
  TYPE.PASSWORD, TYPE.PASSWORD_CONFIRM, TYPE.DATE, TYPE.DATETIME,
];

// file-upload types
export const FILE_TYPES = [TYPE.FILE, TYPE.FILES];

// structural fields that don't collect input
export const STRUCTURAL_TYPES = [TYPE.GROUP_START, TYPE.GROUP_END, TYPE.STATIC, TYPE.HIDDEN];

/**
 * Validate field-group markers (Group Start/End) in field order. Groups can't
 * nest and a Start must always be closed by an End that comes after it.
 * Returns { messages: string[], badIds: Set<number> } — empty messages = valid.
 */
export function validateGroups(fields) {
  const messages = [];
  const badIds = new Set();
  let openStart = null; // the currently-open Group Start field, or null

  fields.forEach((f) => {
    const type = Number(f.form_field_type_id);

    if (type === TYPE.GROUP_START) {
      if (openStart) {
        // a second Start before the first was closed
        messages.push('A group is already open — close it with a Group End before starting another.');
        badIds.add(openStart.id);
        badIds.add(f.id);
      }
      openStart = f;
    } else if (type === TYPE.GROUP_END) {
      if (!openStart) {
        // an End with no matching Start before it
        messages.push('A Group End has no matching Group Start before it.');
        badIds.add(f.id);
      } else {
        openStart = null; // closed cleanly
      }
    }
  });

  if (openStart) {
    messages.push('A Group Start is missing its Group End. Add a Group End after it, or remove the Group Start.');
    badIds.add(openStart.id);
  }

  return { messages, badIds };
}

export const VISIBILITY_OPTIONS = [
  { value: 'visible', label: 'Visible' },
  { value: 'hidden', label: 'Hidden' },
  { value: 'disabled', label: 'Disabled' },
  { value: 'readonly', label: 'Readonly' },
];

// label_position maps to the legacy integer column: 1=Top, 0=Bottom, 2=Floating.
// "Default" and "Hidden" are expressed via show_label + a sensible position.
export const LABEL_POSITION_OPTIONS = [
  { value: 'default', label: 'Default' },
  { value: 'top', label: 'Top' },
  { value: 'bottom', label: 'Bottom' },
  { value: 'floating', label: 'Floating' },
  { value: 'hidden', label: 'Hidden' },
];

// translate the editor's label-position choice into stored columns
export function labelPositionToColumns(choice) {
  switch (choice) {
    case 'top': return { label_position: 1, show_label: 1 };
    case 'bottom': return { label_position: 0, show_label: 1 };
    case 'floating': return { label_position: 2, show_label: 1 };
    case 'hidden': return { label_position: 1, show_label: 0 };
    case 'default':
    default: return { label_position: 1, show_label: 1 };
  }
}

// translate stored columns back into the editor's label-position choice
export function columnsToLabelPosition(field) {
  if (!field) return 'default';
  if (Number(field.show_label) === 0) return 'hidden';
  if (Number(field.label_position) === 2) return 'floating';
  if (Number(field.label_position) === 0) return 'bottom';
  return 'top';
}
