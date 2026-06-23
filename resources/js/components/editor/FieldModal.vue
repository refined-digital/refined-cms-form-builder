<template>
  <div class="fb-modal__overlay" @mousedown="onOverlayMouseDown" @click="onOverlayClick">
    <div class="fb-modal">
      <header class="fb-modal__head">
        <h3 class="fb-modal__title">{{ typeMeta.name }} — Field Settings</h3>
        <button type="button" class="fb-modal__close" @click="$emit('close')">&times;</button>
      </header>

      <nav class="fb-modal__tabs">
        <button
          v-for="t in availableTabs"
          :key="t.key"
          type="button"
          class="fb-modal__tab"
          :class="{ 'fb-modal__tab--active': tab === t.key }"
          @click="tab = t.key"
        >{{ t.label }}</button>
      </nav>

      <div class="fb-modal__body">
        <!-- GENERAL -->
        <div v-show="tab === 'general'" class="fb-modal__panel">
          <div class="fb-field">
            <label class="fb-field__label">Label <span class="fb-field__req">*</span></label>
            <input v-model="model.name" type="text" class="fb-field__input" :class="{ 'fb-field__input--error': errors.name }" @input="validateField('name')" @blur="validateField('name')" />
            <p v-if="errors.name" class="fb-field__note fb-field__note--error">{{ errors.name }}</p>
            <p v-else class="fb-field__note">The label that describes the field.</p>
          </div>

          <div v-if="supportsPlaceholder" class="fb-field">
            <label class="fb-field__label">Placeholder</label>
            <input v-model="model.placeholder" type="text" class="fb-field__input" :disabled="labelPosition === 'floating'" />
            <p class="fb-field__note">
              Shown if the field doesn't have a value.
              <span v-if="labelPosition === 'floating'">(Cleared automatically for floating labels.)</span>
            </p>
          </div>

          <div class="fb-field">
            <label class="fb-field__label">Default Value</label>
            <input v-model="model.default_value" type="text" class="fb-field__input" />
            <p class="fb-field__note">Set a default value.</p>
          </div>

          <div v-if="supportsOptions" class="fb-field">
            <label class="fb-field__label">Options</label>
            <options-builder v-model="model.options" />
          </div>

          <div v-if="isCustom" class="fb-field">
            <label class="fb-field__label">Custom Field Class <span class="fb-field__req">*</span></label>
            <input v-model="model.custom_field_class" type="text" class="fb-field__input" :class="{ 'fb-field__input--error': errors.custom_field_class }" @input="validateField('custom_field_class')" @blur="validateField('custom_field_class')" />
            <p v-if="errors.custom_field_class" class="fb-field__note fb-field__note--error">{{ errors.custom_field_class }}</p>
          </div>

          <div v-if="isHidden" class="fb-field">
            <label class="fb-field__label">Hidden Field Value</label>
            <input v-model="model.hidden_field_value" type="text" class="fb-field__input" />
          </div>
        </div>

        <!-- SETTINGS -->
        <div v-show="tab === 'settings'" class="fb-modal__panel">
          <div class="fb-field">
            <label class="fb-field__label">Required Field</label>
            <toggle v-model="model.required" />
          </div>

          <div class="fb-field">
            <label class="fb-field__label">Error Message</label>
            <input v-model="model.error_message" type="text" class="fb-field__input" />
            <p class="fb-field__note">Shown when validation fails. Leave empty to use the default message.</p>
          </div>

          <div class="fb-field">
            <label class="fb-field__label">Include in Email Notifications</label>
            <toggle v-model="model.include_in_email" />
          </div>

          <div v-if="mergeFieldVisible" class="fb-field">
            <label class="fb-field__label">Merge Field</label>
            <input v-model="model.merge_field" type="text" class="fb-field__input" />
            <p class="fb-field__note">Maps this field to an external integration field.</p>
          </div>

          <div v-if="isGibberishApplicable" class="fb-field">
            <label class="fb-field__label">Spam (gibberish) check</label>
            <toggle v-model="gibberishCheck" />
            <p class="fb-field__note">Turn off for fields that legitimately accept random-looking values.</p>
          </div>

          <div v-if="isFileField" class="fb-field">
            <label class="fb-field__label">Allowed File Types</label>
            <select v-model="fileTypes" class="fb-field__input">
              <option value="">Image or Document</option>
              <option value="image">Image</option>
              <option value="document">Document</option>
              <option value="image_document">Image or Document</option>
            </select>
          </div>

          <template v-if="isPasswordField">
            <div class="fb-field">
              <label class="fb-field__label">Require a strong password</label>
              <toggle v-model="strongPassword" />
              <p class="fb-field__note">Enforce the strong-password rules configured for this site.</p>
            </div>

            <div v-if="strongPassword" class="fb-field">
              <label class="fb-field__label">Show requirements under the field</label>
              <toggle v-model="showPasswordRules" />
              <p class="fb-field__note">Displays the rules as a checklist that ticks off as the visitor types.</p>
            </div>
          </template>
        </div>

        <!-- APPEARANCE -->
        <div v-show="tab === 'appearance'" class="fb-modal__panel">
          <div class="fb-field">
            <label class="fb-field__label">Visibility</label>
            <select v-model="model.visibility" class="fb-field__input">
              <option v-for="o in visibilityOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
          </div>

          <div class="fb-field">
            <label class="fb-field__label">Label Position</label>
            <select v-model="labelPosition" class="fb-field__input">
              <option v-for="o in labelPositionOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
          </div>

          <div class="fb-field">
            <label class="fb-field__label">Note</label>
            <textarea v-model="model.note" class="fb-field__input" rows="2"></textarea>
          </div>

          <div class="fb-field">
            <label class="fb-field__label">Note Position</label>
            <select v-model.number="model.note_position" class="fb-field__input">
              <option :value="1">Above the input</option>
              <option :value="0">Below the input</option>
            </select>
          </div>

          <div class="fb-field">
            <label class="fb-field__label">Custom CSS Class</label>
            <input v-model="model.custom_class" type="text" class="fb-field__input" />
          </div>
        </div>

        <!-- CONDITIONS -->
        <div v-show="tab === 'conditions'" class="fb-modal__panel">
          <conditions-builder v-model="model.visibility_rules" :fields="otherFields" />
        </div>
      </div>

      <footer class="fb-modal__foot">
        <button type="button" class="button button--red button--small" @click="$emit('close')">Cancel</button>
        <button type="button" class="button button--blue button--small" @click="save">Save Field</button>
      </footer>
    </div>
  </div>
</template>

<script>
import {
  TYPE, TYPES_WITH_OPTIONS, TYPES_WITH_PLACEHOLDER, FILE_TYPES,
  VISIBILITY_OPTIONS, LABEL_POSITION_OPTIONS,
  labelPositionToColumns, columnsToLabelPosition,
} from '../../lib/fieldTypes';
import Toggle from './controls/Toggle.vue';
import OptionsBuilder from './controls/OptionsBuilder.vue';
import ConditionsBuilder from './controls/ConditionsBuilder.vue';
import modalAppClass from '../../lib/modalAppClass';
import modalOverlay from '../../lib/modalOverlay';

export default {
  name: 'FieldModal',
  mixins: [modalAppClass, modalOverlay],
  components: { Toggle, OptionsBuilder, ConditionsBuilder },
  props: {
    field: { type: Object, required: true },
    fieldTypes: { type: Array, default: () => [] },
    fields: { type: Array, default: () => [] },
    integrationsEnabled: { type: Boolean, default: false },
  },
  emits: ['save', 'close'],
  data() {
    return {
      tab: 'general',
      visibilityOptions: VISIBILITY_OPTIONS,
      labelPositionOptions: LABEL_POSITION_OPTIONS,
      model: this.normalise(this.field),
      labelPosition: columnsToLabelPosition(this.field),
      errors: {},
    };
  },
  computed: {
    typeId() {
      return Number(this.model.form_field_type_id);
    },
    typeMeta() {
      return this.fieldTypes.find((t) => t.id === this.typeId) || { name: 'Field' };
    },
    availableTabs() {
      const tabs = [
        { key: 'general', label: 'General' },
        { key: 'settings', label: 'Settings' },
        { key: 'appearance', label: 'Appearance' },
        { key: 'conditions', label: 'Conditions' },
      ];
      return tabs;
    },
    supportsPlaceholder() {
      return TYPES_WITH_PLACEHOLDER.includes(this.typeId);
    },
    supportsOptions() {
      return TYPES_WITH_OPTIONS.includes(this.typeId);
    },
    isFileField() {
      return FILE_TYPES.includes(this.typeId);
    },
    isCustom() {
      return this.typeId === TYPE.CUSTOM;
    },
    isHidden() {
      return this.typeId === TYPE.HIDDEN;
    },
    isGibberishApplicable() {
      return this.typeId === TYPE.TEXT || this.typeId === TYPE.TEXTAREA;
    },
    isPasswordField() {
      return this.typeId === TYPE.PASSWORD || this.typeId === TYPE.PASSWORD_CONFIRM;
    },
    mergeFieldVisible() {
      return this.integrationsEnabled;
    },
    otherFields() {
      return this.fields.filter((f) => f.id !== this.model.id);
    },
    // settings JSON sub-keys
    gibberishCheck: {
      get() {
        const v = this.model.settings?.gibberish_check;
        return v === undefined || v === null ? 1 : (v ? 1 : 0);
      },
      set(val) {
        this.ensureSettings();
        this.model.settings.gibberish_check = !!val;
      },
    },
    fileTypes: {
      get() { return this.model.settings?.file_types || ''; },
      set(val) { this.ensureSettings(); this.model.settings.file_types = val; },
    },
    strongPassword: {
      get() { return !!this.model.settings?.strong_password; },
      set(val) { this.ensureSettings(); this.model.settings.strong_password = !!val; },
    },
    showPasswordRules: {
      get() { return !!this.model.settings?.show_password_rules; },
      set(val) { this.ensureSettings(); this.model.settings.show_password_rules = !!val; },
    },
  },
  methods: {
    normalise(field) {
      const copy = JSON.parse(JSON.stringify(field || {}));
      copy.options = Array.isArray(copy.options) ? copy.options : [];
      copy.settings = copy.settings && typeof copy.settings === 'object' ? copy.settings : {};
      copy.visibility = copy.visibility || 'visible';
      copy.visibility_rules = copy.visibility_rules || null;
      return copy;
    },
    ensureSettings() {
      if (!this.model.settings || typeof this.model.settings !== 'object') {
        this.model.settings = {};
      }
    },
    fieldError(key) {
      const v = (this.model[key] || '').toString().trim();
      switch (key) {
        case 'name': return v ? null : 'A label is required.';
        case 'custom_field_class': return (this.isCustom && !v) ? 'A custom field class is required.' : null;
        default: return null;
      }
    },
    validateField(key) {
      const err = this.fieldError(key);
      if (err) {
        this.errors[key] = err;
      } else {
        delete this.errors[key];
      }
    },
    validate() {
      this.errors = {};
      ['name', 'custom_field_class'].forEach((key) => {
        const err = this.fieldError(key);
        if (err) this.errors[key] = err;
      });
      return Object.keys(this.errors).length === 0;
    },
    save() {
      if (!this.validate()) {
        this.tab = 'general';
        return;
      }
      const cols = labelPositionToColumns(this.labelPosition);
      const payload = {
        ...this.model,
        ...cols,
      };
      // floating label clears the placeholder (server enforces too)
      if (this.labelPosition === 'floating') {
        payload.placeholder = ' ';
      }
      this.$emit('save', payload);
    },
  },
};
</script>
