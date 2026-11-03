<template>
  <div class="fb-pages">
    <draggable
      :model-value="pages"
      item-key="id"
      handle=".fb-pages__grip"
      class="fb-pages__list"
      direction="horizontal"
      @change="onChange"
    >
      <template #item="{ element }">
        <div class="fb-pages__tab" :class="{ 'fb-pages__tab--active': element.id === current }">
          <span class="fb-pages__grip" title="Drag to reorder"><i class="fas fa-grip-vertical"></i></span>
          <input
            v-if="editingId === element.id"
            ref="renameInput"
            v-model="draftName"
            type="text"
            class="fb-pages__input"
            @keydown.enter.prevent="commitRename(element)"
            @keydown.esc.prevent="editingId = null"
            @blur="commitRename(element)"
          />
          <button
            v-else
            type="button"
            class="fb-pages__name"
            title="Click to open, double-click to rename"
            @click="$emit('select', element.id)"
            @dblclick="startRename(element)"
          >{{ element.name || 'Untitled page' }}</button>
          <button
            v-if="pages.length > 1"
            type="button"
            class="fb-pages__delete"
            title="Delete page"
            @click.stop="$emit('delete', element.id)"
          >&times;</button>
        </div>
      </template>
    </draggable>

    <button type="button" class="fb-pages__add" @click="$emit('add')">
      <i class="fas fa-plus"></i> Add page
    </button>
  </div>
</template>

<script>
import draggable from 'vuedraggable';

export default {
  name: 'PageTabs',
  components: { draggable },
  props: {
    pages: { type: Array, default: () => [] },
    current: { type: Number, default: null },
  },
  emits: ['select', 'add', 'rename', 'delete', 'reorder'],
  data() {
    return { editingId: null, draftName: '' };
  },
  methods: {
    startRename(page) {
      this.draftName = page.name || '';
      this.editingId = page.id;
      this.$nextTick(() => {
        const input = [].concat(this.$refs.renameInput || [])[0];
        if (input) input.focus();
      });
    },
    commitRename(page) {
      if (this.editingId !== page.id) return;
      this.editingId = null;
      const name = this.draftName.trim();
      if (name && name !== page.name) this.$emit('rename', { id: page.id, name });
    },
    onChange(evt) {
      if (!evt.moved) return;
      const list = [...this.pages];
      const [item] = list.splice(evt.moved.oldIndex, 1);
      list.splice(evt.moved.newIndex, 0, item);
      this.$emit('reorder', list.map((p) => p.id));
    },
  },
};
</script>
