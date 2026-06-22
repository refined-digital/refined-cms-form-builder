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
          <rd-form-email :field="{ name: 'to' }" :value="model.to" v-model="model.to"></rd-form-email>
          <p class="fb-field__note">Email addresses who will receive this email notification.</p>
        </div>

        <div class="fb-field">
          <label class="fb-field__label">CC</label>
          <rd-form-email :field="{ name: 'cc' }" :value="model.cc" v-model="model.cc"></rd-form-email>
          <p class="fb-field__note">Email addresses who will receive a CC of this notification.</p>
        </div>

        <div class="fb-field">
          <label class="fb-field__label">BCC</label>
          <rd-form-email :field="{ name: 'bcc' }" :value="model.bcc" v-model="model.bcc"></rd-form-email>
          <p class="fb-field__note">Email addresses who will receive a BCC of this notification.</p>
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
          <rd-rich-text
            id="fb-notification-content"
            :reduced="true"
            :tokens="contentTokens"
            v-model="model.content"
            :content="model.content"
          ></rd-rich-text>
          <p class="fb-field__note">
            Use the field dropdown to insert form values, or type <code>[Form Name]</code>
            for the form's name.
          </p>
        </div>
      </div>

      <footer class="fb-modal__foot">
        <button type="button" class="button button--red button--small" @click="$emit('close')">Cancel</button>
        <button type="button" class="button button--blue button--small" @click="save">Save</button>
      </footer>
    </div>
  </div>
</template>

<script>
import { TYPE } from '../../lib/fieldTypes';
import modalAppClass from '../../lib/modalAppClass';

export default {
  name: 'NotificationModal',
  mixins: [modalAppClass],
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
        content: this.notification.content || '<p>You have a new [Form Name] submission</p><p>[[fields]]</p>',
      },
    };
  },
  computed: {
    emailFields() {
      return this.fields.filter((f) => Number(f.form_field_type_id) === TYPE.EMAIL);
    },
    // field-token dropdown for the rich editor: all-fields + each field.
    // Disambiguate duplicate field names by appending (2), (3)…
    contentTokens() {
      const seen = {};
      const fieldTokens = this.fields.map((f) => {
        const base = f.name || `Field ${f.id}`;
        seen[base] = (seen[base] || 0) + 1;
        const label = seen[base] > 1 ? `${base} (${seen[base]})` : base;
        return { label, token: `[[field:${f.id}]]` };
      });
      return [
        { label: 'Form Name', token: '[Form Name]' },
        { label: 'All form fields', token: '[[fields]]' },
        ...fieldTokens,
      ];
    },
  },
  methods: {
    save() {
      this.$emit('save', { ...this.model });
    },
  },
};
</script>
