<template>
  <aside class="fb-palette">
    <section v-for="group in groups" :key="group.name" class="fb-palette__group">
      <h4 class="fb-palette__heading">{{ group.label }}</h4>
      <draggable
        :model-value="group.types"
        :group="{ name: 'fb-fields', pull: 'clone', put: false }"
        :sort="false"
        :clone="cloneType"
        item-key="id"
        class="fb-palette__grid"
      >
        <template #item="{ element }">
          <button
            type="button"
            class="fb-palette__item"
            @click="$emit('add', element)"
          >
            <i :class="['fas', element.icon]"></i>
            <span>{{ element.name }}</span>
          </button>
        </template>
      </draggable>
    </section>
  </aside>
</template>

<script>
import draggable from 'vuedraggable';

export default {
  name: 'FieldPalette',
  components: { draggable },
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
  methods: {
    // what gets dropped into the canvas: a marker carrying the palette type so
    // the canvas can tell a new field from an existing one.
    cloneType(type) {
      return { __paletteType: type };
    },
  },
};
</script>
