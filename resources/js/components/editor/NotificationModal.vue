<template>
  <div class="fb-modal__overlay" @click.self="$emit('close')">
    <div class="fb-modal">
      <header class="fb-modal__head">
        <h3 class="fb-modal__title">{{ notification.id ? 'Edit' : 'New' }} Notification</h3>
        <button type="button" class="fb-modal__close" @click="$emit('close')">&times;</button>
      </header>

      <div class="fb-modal__body fb-modal__panel">
        <div class="fb-field">
          <label class="fb-field__label">Name <span class="fb-field__req">*</span></label>
          <input v-model="model.name" type="text" class="fb-field__input" />
          <p class="fb-field__note">The internal name of the notification.</p>
        </div>

        <div class="fb-field">
          <label class="fb-field__label">Recipient Emails <span class="fb-field__req">*</span></label>
          <rd-form-email v-model="model.to"></rd-form-email>
          <p class="fb-field__note">Email addresses who will receive this notification.</p>
        </div>

        <div class="fb-field">
          <label class="fb-field__label">CC</label>
          <rd-form-email v-model="model.cc"></rd-form-email>
        </div>

        <div class="fb-field">
          <label class="fb-field__label">BCC</label>
          <rd-form-email v-model="model.bcc"></rd-form-email>
        </div>

        <div v-if="emailFields.length" class="fb-field">
          <label class="fb-field__label">Reply-To Email</label>
          <select v-model="model.reply_to" class="fb-field__input">
            <option value="">— None —</option>
            <option v-for="f in emailFields" :key="f.id" :value="`field${f.id}`">{{ f.name }}</option>
          </select>
          <p class="fb-field__note">Reply to an email-type field submitted on the form.</p>
        </div>

        <div class="fb-field">
          <label class="fb-field__label">Subject <span class="fb-field__req">*</span></label>
          <input v-model="model.subject" type="text" class="fb-field__input" />
          <p class="fb-field__note">Use <code>[Form Name]</code> to insert the form's name.</p>
        </div>

        <div class="fb-field">
          <label class="fb-field__label">Content</label>
          <simple-rich-text v-model="model.content" :fields="fields"></simple-rich-text>
        </div>
      </div>

      <footer class="fb-modal__foot">
        <button type="button" class="button button--grey" @click="$emit('close')">Cancel</button>
        <button type="button" class="button button--blue" @click="save">Save</button>
      </footer>
    </div>
  </div>
</template>

<script>
import SimpleRichText from './controls/SimpleRichText.vue';
import { TYPE } from '../../lib/fieldTypes';
import modalAppClass from '../../lib/modalAppClass';

export default {
  name: 'NotificationModal',
  mixins: [modalAppClass],
  components: { SimpleRichText },
  props: {
    notification: { type: Object, required: true },
    fields: { type: Array, default: () => [] },
  },
  emits: ['save', 'close'],
  data() {
    return {
      model: {
        id: this.notification.id || null,
        active: this.notification.active ?? 1,
        name: this.notification.name || '',
        to: this.notification.to || '',
        cc: this.notification.cc || '',
        bcc: this.notification.bcc || '',
        reply_to: this.notification.reply_to || '',
        subject: this.notification.subject || "A new submission from '[Form Name]'",
        content: this.notification.content || '[[fields]]',
      },
    };
  },
  computed: {
    emailFields() {
      return this.fields.filter((f) => Number(f.form_field_type_id) === TYPE.EMAIL);
    },
  },
  methods: {
    save() {
      this.$emit('save', { ...this.model });
    },
  },
};
</script>
