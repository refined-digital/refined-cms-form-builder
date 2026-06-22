<template>
  <transition name="fb-save">
    <div v-if="status" class="fb-save" :class="`fb-save--${status}`">
      <span v-if="status === 'saving'" class="fb-save__spinner spinner"></span>
      <i v-else-if="status === 'saved'" class="fas fa-check fb-save__icon"></i>
      <i v-else-if="status === 'error'" class="fas fa-exclamation-triangle fb-save__icon"></i>
      <span class="fb-save__text">{{ label }}</span>
    </div>
  </transition>
</template>

<script>
export default {
  name: 'SaveIndicator',
  props: {
    // '', 'saving', 'saved', 'error'
    status: { type: String, default: '' },
  },
  computed: {
    label() {
      switch (this.status) {
        case 'saving': return 'Saving…';
        case 'saved': return 'Saved!';
        case 'error': return 'Save failed';
        default: return '';
      }
    },
  },
};
</script>
