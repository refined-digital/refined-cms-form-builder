<template>
  <div class="fb-tab-panel fb-tab-panel--form">
    <div class="fb-field">
      <label class="fb-field__label">Name <span class="fb-field__req">*</span></label>
      <input v-model="name" type="text" class="fb-field__input" @blur="save" />
      <p v-if="nameError" class="fb-field__note fb-field__note--error">{{ nameError }}</p>
      <p v-else class="fb-field__note">The internal name of the form.</p>
    </div>

    <div class="fb-field">
      <label class="fb-field__label">Use reCAPTCHA</label>
      <toggle v-model="recaptcha" @update:model-value="save" />
      <p class="fb-field__note">Protect this form with invisible reCAPTCHA v3.</p>
    </div>
  </div>
</template>

<script>
import Toggle from './controls/Toggle.vue';

export default {
  name: 'SettingsTab',
  components: { Toggle },
  props: {
    form: { type: Object, required: true },
  },
  emits: ['save'],
  data() {
    return {
      name: this.form.name || '',
      recaptcha: Number(this.form.recaptcha) ? 1 : 0,
      nameError: '',
    };
  },
  methods: {
    save() {
      this.nameError = '';
      if (!this.name.trim()) {
        this.nameError = 'A form name is required.';
        return;
      }
      this.$emit('save', {
        name: this.name,
        recaptcha: this.recaptcha,
      });
    },
  },
};
</script>
