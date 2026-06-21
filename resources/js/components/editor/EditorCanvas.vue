<template>
  <div class="fb-canvas">
    <header class="fb-canvas__head">
      <span class="fb-canvas__page">PAGE 1</span>
    </header>

    <div v-if="loading" class="fb-canvas__loading">Loading…</div>

    <div v-else-if="!fields.length" class="fb-canvas__empty">
      Add fields from the palette on the right to start building your form.
    </div>

    <draggable
      v-else
      :model-value="fields"
      item-key="id"
      handle=".fb-row__handle"
      class="fb-canvas__list"
      @update:model-value="$emit('reorder', $event)"
    >
      <template #item="{ element }">
        <editor-field-row
          :field="element"
          :type-meta="typeMeta(element.form_field_type_id)"
          @edit="$emit('edit-field', element)"
          @delete="$emit('delete-field', element)"
        />
      </template>
    </draggable>

    <footer class="fb-canvas__submit">
      <button type="button" class="fb-submit-btn" @click="$emit('edit-submit')">
        {{ submitText || 'Submit' }}
        <span class="fb-submit-btn__arrow">→</span>
      </button>
      <button type="button" class="fb-canvas__submit-edit" @click="$emit('edit-submit')" title="Edit submit button">
        <i class="fas fa-cog"></i>
      </button>
    </footer>
  </div>
</template>

<script>
import draggable from 'vuedraggable';
import EditorFieldRow from './EditorFieldRow.vue';

export default {
  name: 'EditorCanvas',
  components: { draggable, EditorFieldRow },
  props: {
    fields: { type: Array, default: () => [] },
    fieldTypes: { type: Array, default: () => [] },
    submitText: { type: String, default: '' },
    loading: { type: Boolean, default: false },
  },
  emits: ['reorder', 'edit-field', 'edit-submit', 'delete-field'],
  methods: {
    typeMeta(id) {
      return this.fieldTypes.find((t) => t.id === Number(id)) || {};
    },
  },
};
</script>
