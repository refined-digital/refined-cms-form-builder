<?php

namespace RefinedDigital\FormBuilder\Module\Enums;

/**
 * The form-field types, mirroring the load-bearing integer ids seeded in
 * FieldTypeTableSeeder. Use these in place of the raw ids for readability —
 * compare against the stored int via ->value (e.g.
 * $field->form_field_type_id == FormFieldType::HIDDEN->value).
 *
 * Never renumber a case: the ids are referenced across the codebase and stored
 * in the database. Append new types at the end (and to the seeder).
 */
enum FormFieldType: int
{
    case TEXT = 1;

    case TEXTAREA = 2;

    case SELECT = 3;

    case RADIO = 4;

    case CHECKBOX = 5;

    case SINGLE_CHECKBOX = 6;

    case NUMBER = 7;

    case EMAIL = 8;

    case TEL = 9;

    case PASSWORD = 10;

    case PASSWORD_CONFIRM = 11;

    case HIDDEN = 12;

    case YESNO_SELECT = 13;

    case COUNTRY_SELECT = 14;

    case DATE = 15;

    case DATE_TIME = 16;

    case FILE = 17;

    case MULTIPLE_FILES = 18;

    case STATIC = 19;

    case CUSTOM = 20;

    case DOB = 21;

    case GROUP_START = 22;

    case GROUP_END = 23;
}
