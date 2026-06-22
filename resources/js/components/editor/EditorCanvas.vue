<template>
  <div class="fb-canvas">
    <div v-if="loading" class="fb-canvas__loading">Loading…</div>

    <template v-else>
      <p v-if="!fields.length" class="fb-canvas__empty">
        Click or drag a field from the palette on the right to start building your form.
      </p>

      <draggable
        :model-value="fields"
        :group="{ name: 'fb-fields', pull: true, put: true }"
        item-key="id"
        handle=".fb-row__handle"
        class="fb-canvas__list"
        :class="{ 'fb-canvas__list--empty': !fields.length }"
        @change="onChange"
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
    </template>

    <footer class="fb-canvas__submit">
      <button type="button" class="fb-submit-btn" @click="$emit('edit-submit')">
        {{ submitText || 'Submit' }}
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
  emits: ['reorder', 'edit-field', 'edit-submit', 'delete-field', 'add-field-at'],
  methods: {
    typeMeta(id) {
      return this.fieldTypes.find((t) => t.id === Number(id)) || {};
    },
    // vuedraggable @change: a palette clone arrives as `added` carrying a
    // __paletteType marker; a normal drag is `moved`.
    onChange(evt) {
      if (evt.added) {
        const { element, newIndex } = evt.added;
        if (element && element.__paletteType) {
          this.$emit('add-field-at', { type: element.__paletteType, index: newIndex });
          return;
        }
      }
      if (evt.moved) {
        const list = [...this.fields];
        const [item] = list.splice(evt.moved.oldIndex, 1);
        list.splice(evt.moved.newIndex, 0, item);
        this.$emit('reorder', list);
      }
    },
  },
};
</script>
