<template>
  <div class="fb-tab-panel fb-tab-panel--list">
    <header class="fb-list__head">
      <h3 class="fb-list__title">Integrations</h3>
    </header>

    <p v-if="!integrations.length" class="fb-list__empty">
      No integrations installed. Install an integration package to push submissions to
      external services.
    </p>

    <div v-for="integration in integrations" :key="integration.key" class="fb-integration">
      <div class="fb-integration__head">
        <span class="fb-integration__icon" v-html="integration.icon"></span>
        <div class="fb-integration__meta">
          <span class="fb-integration__name">{{ integration.name }}</span>
          <span class="fb-integration__desc">{{ integration.description }}</span>
        </div>
        <div class="fb-integration__toggle">
          <label class="fb-field__label">Enable</label>
          <toggle :model-value="integration.enabled ? 1 : 0" @update:model-value="setEnabled(integration, $event)" />
        </div>
      </div>

      <div v-if="integration.enabled" class="fb-integration__body">
        <div class="fb-field">
          <label class="fb-field__label">Send Email Notifications</label>
          <toggle :model-value="integration.send_email ? 1 : 0" @update:model-value="setSendEmail(integration, $event)" />
          <p class="fb-field__note">Turn off to let this integration handle delivery instead of the form's notifications.</p>
        </div>

        <div v-for="setting in integration.settings" :key="setting.name" class="fb-field">
          <label class="fb-field__label">
            {{ setting.label }}<span v-if="setting.required" class="fb-field__req">*</span>
          </label>
          <select v-if="setting.type === 'select'" v-model="integration.config[setting.name]" class="fb-field__input" @change="save(integration)">
            <option v-for="opt in setting.options" :key="opt.value ?? opt" :value="opt.value ?? opt">{{ opt.label ?? opt }}</option>
          </select>
          <input v-else v-model="integration.config[setting.name]" type="text" class="fb-field__input" @blur="save(integration)" />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { createApi } from '../../lib/api';
import Toggle from './controls/Toggle.vue';

export default {
  name: 'IntegrationsTab',
  components: { Toggle },
  props: {
    formId: { type: Number, required: true },
    apiBase: { type: String, required: true },
  },
  emits: ['changed'],
  data() {
    return {
      api: createApi(this.apiBase),
      integrations: [],
    };
  },
  async created() {
    await this.load();
  },
  methods: {
    async load() {
      try {
        this.integrations = (await this.api.integrations()).map((i) => ({
          ...i,
          config: i.config && typeof i.config === 'object' ? i.config : {},
        }));
        this.notifyChanged();
      } catch (e) { console.error(e); }
    },
    setEnabled(integration, val) {
      integration.enabled = !!Number(val);
      this.save(integration);
    },
    setSendEmail(integration, val) {
      integration.send_email = !!Number(val);
      this.save(integration);
    },
    async save(integration) {
      try {
        await this.api.updateIntegration(integration.key, {
          enabled: integration.enabled,
          send_email: integration.send_email,
          config: integration.config,
        });
        this.notifyChanged();
      } catch (e) {
        console.error('[form-builder] save integration failed', e);
      }
    },
    notifyChanged() {
      this.$emit('changed', this.integrations.some((i) => i.enabled));
    },
  },
};
</script>
