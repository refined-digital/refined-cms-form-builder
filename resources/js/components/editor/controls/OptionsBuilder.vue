<template>
  <div class="fb-options">
    <div v-for="(opt, i) in items" :key="i" class="fb-options__row">
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
    <button type="button" class="button button--grey fb-options__add" @click="add">+ Add option</button>
  </div>
</template>

<script>
export default {
  name: 'OptionsBuilder',
  props: {
    modelValue: { type: Array, default: () => [] },
  },
  emits: ['update:modelValue'],
  data() {
    return {
      items: (this.modelValue || []).map((o) => ({ label: o.label ?? '', value: o.value ?? '' })),
    };
  },
  methods: {
    add() {
      this.items.push({ label: '', value: '' });
      this.emit();
    },
    remove(i) {
      this.items.splice(i, 1);
      this.emit();
    },
    emit() {
      // value falls back to the label when left blank
      const cleaned = this.items
        .filter((o) => o.label !== '' || o.value !== '')
        .map((o) => ({ label: o.label, value: o.value || o.label }));
      this.$emit('update:modelValue', cleaned);
    },
  },
};
</script>
