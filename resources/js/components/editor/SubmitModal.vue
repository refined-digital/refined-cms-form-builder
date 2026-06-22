<template>
  <div class="fb-modal__overlay" @mousedown="onOverlayMouseDown" @click="onOverlayClick">
    <div class="fb-modal fb-modal--small">
      <header class="fb-modal__head">
        <h3 class="fb-modal__title">Submit Button</h3>
        <button type="button" class="fb-modal__close" @click="$emit('close')">&times;</button>
      </header>
      <div class="fb-modal__body">
        <div class="fb-field">
          <label class="fb-field__label">Button Text</label>
          <input v-model="text" type="text" class="fb-field__input" placeholder="Submit" />
          <p class="fb-field__note">The text shown on the form's submit button.</p>
        </div>
      </div>
      <footer class="fb-modal__foot">
        <button type="button" class="button button--red button--small" @click="$emit('close')">Cancel</button>
        <button type="button" class="button button--blue button--small" @click="$emit('save', text)">Save</button>
      </footer>
    </div>
  </div>
</template>

<script>
import modalAppClass from '../../lib/modalAppClass';
import modalOverlay from '../../lib/modalOverlay';

export default {
  name: 'SubmitModal',
  mixins: [modalAppClass, modalOverlay],
  props: {
    submitText: { type: String, default: '' },
  },
  emits: ['save', 'close'],
  data() {
    return { text: this.submitText || '' };
  },
};
</script>
