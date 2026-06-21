<template>
  <aside class="fb-palette">
    <section v-for="group in groups" :key="group.name" class="fb-palette__group">
      <h4 class="fb-palette__heading">{{ group.label }}</h4>
      <div class="fb-palette__grid">
        <button
          v-for="type in group.types"
          :key="type.id"
          type="button"
          class="fb-palette__item"
          @click="$emit('add', type)"
        >
          <i :class="['fas', type.icon]"></i>
          <span>{{ type.name }}</span>
        </button>
      </div>
    </section>
  </aside>
</template>

<script>
export default {
  name: 'FieldPalette',
  props: {
    fieldTypes: { type: Array, default: () => [] },
  },
  emits: ['add'],
  computed: {
    groups() {
      const order = [
        { name: 'Basic', label: 'BASIC FIELDS' },
        { name: 'Option', label: 'OPTION FIELDS' },
        { name: 'Advanced', label: 'ADVANCED FIELDS' },
      ];
      return order
        .map((g) => ({
          ...g,
          types: this.fieldTypes.filter((t) => t.group === g.name),
        }))
        .filter((g) => g.types.length);
    },
  },
};
</script>
