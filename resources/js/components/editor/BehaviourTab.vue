<template>
  <div class="fb-tab-panel fb-tab-panel--form">
    <div class="fb-field">
      <label class="fb-field__label">When a form submits, I want to</label>
      <select v-model="action" class="fb-field__input" @change="save">
        <option value="message">Display a message</option>
        <option value="redirect_page">Redirect to page</option>
        <option value="redirect_url">Redirect to URL</option>
      </select>
    </div>

    <div v-if="action === 'message'" class="fb-field">
      <label class="fb-field__label">Confirmation Message</label>
      <textarea v-model="confirmation" class="fb-field__input" rows="4" @blur="save"></textarea>
      <p class="fb-field__note">Shown on screen after a successful submission.</p>
    </div>

    <div v-if="action === 'redirect_page'" class="fb-field">
      <label class="fb-field__label">Redirect to page</label>
      <rd-link v-model="redirectPage" :settings="{ simple: true }"></rd-link>
      <p class="fb-field__note">Choose a page to send the visitor to after submitting.</p>
    </div>

    <div v-if="action === 'redirect_url'" class="fb-field">
      <label class="fb-field__label">Redirect URL</label>
      <input
        v-model="redirectUrl"
        type="text"
        class="fb-field__input"
        placeholder="https://example.com/thank-you"
        @blur="save"
      />
      <p v-if="urlError" class="fb-field__note fb-field__note--error">{{ urlError }}</p>
      <p v-else class="fb-field__note">Must start with https://</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BehaviourTab',
  props: {
    form: { type: Object, required: true },
    fields: { type: Array, default: () => [] },
  },
  emits: ['save'],
  data() {
    return {
      action: this.form.submit_action || 'message',
      confirmation: this.form.confirmation || '',
      redirectUrl: this.form.redirect_url || '',
      redirectPage: this.parseLink(this.form.redirect_page),
      urlError: '',
    };
  },
  watch: {
    redirectPage: {
      deep: true,
      handler() { this.save(); },
    },
  },
  methods: {
    parseLink(val) {
      if (!val) return {};
      try { return typeof val === 'string' ? JSON.parse(val) : val; } catch (e) { return {}; }
    },
    save() {
      this.urlError = '';
      if (this.action === 'redirect_url' && this.redirectUrl && !/^https:\/\//i.test(this.redirectUrl)) {
        this.urlError = 'The URL must start with https://';
        return;
      }
      this.$emit('save', {
        submit_action: this.action,
        confirmation: this.confirmation,
        redirect_url: this.redirectUrl,
        redirect_page: this.redirectPage ? JSON.stringify(this.redirectPage) : null,
      });
    },
  },
};
</script>
