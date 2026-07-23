<template>
  <div class="fb-options">
    <draggable v-model="items" item-key="uid" handle=".fb-row__handle" class="fb-options__list" @end="emit">
      <template #item="{ element: opt, index: i }">
        <div class="fb-options__row">
          <span class="fb-row__handle" aria-hidden="true">⋮⋮</span>
          <input
            v-model="opt.label"
            type="text"
            class="fb-field__input"
            placeholder="Label"
            @input="emit"
          />
          <input
            v-model="opt.value"
            type="text"
            class="fb-field__input"
            placeholder="Value"
            @input="emit"
          />
          <button type="button" class="fb-options__remove" @click="remove(i)">&times;</button>
        </div>
      </template>
    </draggable>
    <button type="button" class="button button--grey button--small fb-options__add" @click="add">+ Add option</button>
  </div>
</template>

<script>
import draggable from 'vuedraggable';

// rows have no id of their own, and an index key would break dragging
let seq = 0;

export default {
  name: 'OptionsBuilder',
  components: { draggable },
  props: {
    modelValue: { type: Array, default: () => [] },
  },
  emits: ['update:modelValue'],
  data() {
    return {
      items: (this.modelValue || []).map((o) => ({
        uid: ++seq,
        label: o.label ?? '',
        value: o.value ?? '',
      })),
    };
  },
  methods: {
    add() {
      this.items.push({ uid: ++seq, label: '', value: '' });
      this.emit();
    },
    remove(i) {
      this.items.splice(i, 1);
      this.emit();
    },
    emit() {
      // value falls back to the label when left blank; array order is the stored order
      const cleaned = this.items
        .filter((o) => o.label !== '' || o.value !== '')
        .map((o) => ({ label: o.label, value: o.value || o.label }));
      this.$emit('update:modelValue', cleaned);
    },
  },
};
</script>
