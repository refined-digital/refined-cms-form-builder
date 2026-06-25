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
        <button
          v-if="integration.enabled"
          type="button"
          class="button button--blue button--small fb-integration__configure"
          @click="configuring = integration"
        >Configure</button>
        <div class="fb-integration__toggle">
          <label class="fb-field__label">Enable</label>
          <toggle :model-value="integration.enabled ? 1 : 0" @update:model-value="setEnabled(integration, $event)" />
        </div>
      </div>
    </div>

    <integration-modal
      v-if="configuring"
      :integration="configuring"
      :fields="fields"
      @save="onModalSave"
      @close="configuring = null"
    />
  </div>
</template>

<script>
import { createApi } from '../../lib/api';
import Toggle from './controls/Toggle.vue';
import IntegrationModal from './IntegrationModal.vue';

export default {
  name: 'IntegrationsTab',
  components: { Toggle, IntegrationModal },
  props: {
    formId: { type: Number, required: true },
    apiBase: { type: String, required: true },
    fields: { type: Array, default: () => [] },
  },
  emits: ['changed'],
  data() {
    return {
      api: createApi(this.apiBase),
      integrations: [],
      configuring: null,
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
    onModalSave(payload) {
      Object.assign(this.configuring, payload);
      this.save(this.configuring);
      this.configuring = null;
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
