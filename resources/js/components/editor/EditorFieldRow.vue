<template>
  <div class="fb-row" :class="{ 'fb-row--structural': isStructural }" @click="$emit('edit')">
    <span class="fb-row__handle" title="Drag to reorder" @click.stop>
      <i class="fas fa-grip-vertical"></i>
    </span>

    <div class="fb-row__body">
      <label v-if="showLabel" class="fb-row__label">
        {{ field.name }}<span v-if="Number(field.required)" class="fb-row__req">*</span>
      </label>
      <p v-if="field.note" class="fb-row__note">{{ field.note }}</p>

      <!-- lightweight input preview by type -->
      <component :is="previewTag" v-bind="previewAttrs" class="fb-row__preview" v-if="hasPreview">
        <template v-if="previewTag === 'select'">
          <option>{{ field.placeholder || 'Select…' }}</option>
          <option v-for="(opt, i) in previewOptions" :key="i">{{ opt }}</option>
        </template>
      </component>

      <div v-else-if="isChoiceList" class="fb-row__options">
        <label v-for="(opt, i) in previewOptions" :key="i" class="fb-row__option">
          <input :type="optionInputType" disabled /> {{ opt }}
        </label>
      </div>

      <div v-else-if="isStructural" class="fb-row__structural">
        <i :class="['far', typeMeta.icon ? 'fas ' + typeMeta.icon : 'fa-square']"></i>
        {{ typeMeta.name }}
      </div>
    </div>

    <div class="fb-row__actions" @click.stop>
      <span class="fb-row__type">{{ typeMeta.name }}</span>
      <button type="button" class="fb-row__action" @click="$emit('edit')" title="Edit">
        <i class="fas fa-pen"></i>
      </button>
      <button type="button" class="fb-row__action fb-row__action--danger" @click="$emit('delete')" title="Delete">
        <i class="fas fa-trash"></i>
      </button>
    </div>
  </div>
</template>

<script>
import { TYPE, STRUCTURAL_TYPES } from '../../lib/fieldTypes';

export default {
  name: 'EditorFieldRow',
  props: {
    field: { type: Object, required: true },
    typeMeta: { type: Object, default: () => ({}) },
  },
  emits: ['edit', 'delete'],
  computed: {
    typeId() {
      return Number(this.field.form_field_type_id);
    },
    showLabel() {
      return Number(this.field.show_label) !== 0 && this.typeId !== TYPE.HIDDEN;
    },
    // radio / checkbox render as a list of choices; select renders as a dropdown
    isChoiceList() {
      return this.typeId === TYPE.RADIO || this.typeId === TYPE.CHECKBOX;
    },
    isStructural() {
      return STRUCTURAL_TYPES.includes(this.typeId);
    },
    optionInputType() {
      return this.typeId === TYPE.RADIO ? 'radio' : 'checkbox';
    },
    previewOptions() {
      if (Array.isArray(this.field.options) && this.field.options.length) {
        return this.field.options.map((o) => o.label || o.value);
      }
      return ['Option one', 'Option two'];
    },
    hasPreview() {
      return !this.isChoiceList && !this.isStructural;
    },
    previewTag() {
      if (this.typeId === TYPE.TEXTAREA) return 'textarea';
      if (this.typeId === TYPE.SELECT) return 'select';
      return 'input';
    },
    previewAttrs() {
      const attrs = { disabled: true };
      if (this.previewTag === 'input') {
        attrs.type = this.inputType;
        attrs.placeholder = this.field.placeholder || '';
      }
      if (this.previewTag === 'textarea') {
        attrs.placeholder = this.field.placeholder || '';
        attrs.rows = 3;
      }
      return attrs;
    },
    inputType() {
      switch (this.typeId) {
        case TYPE.EMAIL: return 'email';
        case TYPE.NUMBER: return 'number';
        case TYPE.TEL: return 'tel';
        case TYPE.PASSWORD:
        case TYPE.PASSWORD_CONFIRM: return 'password';
        case TYPE.DATE: return 'date';
        case TYPE.DATETIME: return 'datetime-local';
        case TYPE.FILE:
        case TYPE.FILES: return 'file';
        default: return 'text';
      }
    },
  },
};
</script>
