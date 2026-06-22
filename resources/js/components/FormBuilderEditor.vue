<template>
  <div class="fb-editor">
    <nav class="fb-editor__tabs">
      <button
        v-for="t in tabs"
        :key="t.key"
        type="button"
        class="fb-editor__tab"
        :class="{ 'fb-editor__tab--active': tab === t.key }"
        @click="tab = t.key"
      >{{ t.label }}</button>
    </nav>

    <div class="fb-editor__body">
      <!-- FIELDS -->
      <div v-show="tab === 'fields'" class="fb-editor__layout">
        <editor-canvas
          :fields="fields"
          :field-types="fieldTypes"
          :submit-text="form.submit_text"
          :loading="loading"
          @reorder="onReorder"
          @edit-field="openFieldModal"
          @edit-submit="submitModalOpen = true"
          @delete-field="onDeleteField"
          @add-field-at="onAddFieldAt"
        />
        <field-palette
          :field-types="fieldTypes"
          @add="onAddField"
        />
      </div>

      <!-- OTHER TABS (filled in later phases) -->
      <behaviour-tab v-if="tab === 'behaviour'" :form="form" :fields="fields" @save="onFormSave" />
      <notifications-tab v-if="tab === 'notifications'" :form-id="form.id" :fields="fields" :api-base="apiBase" />
      <integrations-tab v-if="tab === 'integrations'" :form-id="form.id" :api-base="apiBase" @changed="integrationsEnabled = $event" />
      <settings-tab v-if="tab === 'settings'" :form="form" @save="onFormSave" />
    </div>

    <field-modal
      v-if="modalField"
      :field="modalField"
      :field-types="fieldTypes"
      :fields="fields"
      :integrations-enabled="integrationsEnabled"
      @save="onFieldSave"
      @close="modalField = null"
    />

    <submit-modal
      v-if="submitModalOpen"
      :submit-text="form.submit_text"
      @save="onSubmitTextSave"
      @close="submitModalOpen = false"
    />
  </div>
</template>

<script>
import { createApi } from '../lib/api';
import EditorCanvas from './editor/EditorCanvas.vue';
import FieldPalette from './editor/FieldPalette.vue';
import FieldModal from './editor/FieldModal.vue';
import SubmitModal from './editor/SubmitModal.vue';
import BehaviourTab from './editor/BehaviourTab.vue';
import NotificationsTab from './editor/NotificationsTab.vue';
import IntegrationsTab from './editor/IntegrationsTab.vue';
import SettingsTab from './editor/SettingsTab.vue';

export default {
  name: 'FormBuilderEditor',
  components: {
    EditorCanvas, FieldPalette, FieldModal, SubmitModal,
    BehaviourTab, NotificationsTab, IntegrationsTab, SettingsTab,
  },
  props: {
    // base url for the form's JSON api, e.g. /refined/form-builder/3/api
    apiBase: { type: String, required: true },
    // initial form record (json from the blade)
    initialForm: { type: Object, required: true },
  },
  data() {
    return {
      tab: 'fields',
      tabs: [
        { key: 'fields', label: 'Fields' },
        { key: 'behaviour', label: 'Behaviour' },
        { key: 'notifications', label: 'Email Notifications' },
        { key: 'integrations', label: 'Integrations' },
        { key: 'settings', label: 'Settings' },
      ],
      api: createApi(this.apiBase),
      form: { ...this.initialForm },
      fields: [],
      fieldTypes: [],
      loading: true,
      modalField: null,
      submitModalOpen: false,
      integrationsEnabled: false,
    };
  },
  async created() {
    this.watchCoreModals();
    try {
      const [types, fields, integrations] = await Promise.all([
        this.api.fieldTypes(),
        this.api.fields(),
        this.api.integrations().catch(() => []),
      ]);
      this.fieldTypes = types;
      this.fields = fields;
      this.integrationsEnabled = integrations.some((i) => i.enabled);
    } catch (e) {
      console.error('[form-builder] failed to load editor data', e);
    } finally {
      this.loading = false;
    }
  },
  methods: {
    // The core link/sitemap pickers (rd-link) open inside .app__right, which
    // sits below the sidebar in the stacking order. Their own app--has-link /
    // app--has-sitemap flags don't reliably lift the content layer here, so we
    // mirror their open state onto the proven app--has-modal flag.
    watchCoreModals() {
      const ui = window.RefinedCMS && window.RefinedCMS.ui;
      const { watch } = window.RefinedCMS.Vue;
      if (!ui || !watch) return;

      const appEl = document.getElementById('app');
      watch(
        () => !!(ui.link?.active || ui.sitemap?.showModal || ui.media?.showModal),
        (open) => {
          if (!appEl) return;
          appEl.classList.toggle('app--has-modal', open);
        },
      );
    },
    typeMeta(id) {
      return this.fieldTypes.find((t) => t.id === Number(id));
    },
    defaultFieldPayload(type) {
      return {
        form_field_type_id: type.id,
        name: type.name,
        required: 0,
        active: 1,
        show_label: 1,
        label_position: 1,
        include_in_email: 1,
        visibility: 'visible',
        options: [],
      };
    },
    // click-to-add: append, then open the modal
    async onAddField(type) {
      try {
        const field = await this.api.createField(this.defaultFieldPayload(type));
        this.fields.push(field);
        this.openFieldModal(field);
      } catch (e) {
        console.error('[form-builder] create field failed', e);
      }
    },
    // drag-to-add: create, insert at the dropped index, persist the new order
    async onAddFieldAt({ type, index }) {
      try {
        const field = await this.api.createField(this.defaultFieldPayload(type));
        this.fields.splice(index, 0, field);
        await this.api.reorder(this.fields.map((f) => f.id));
        this.openFieldModal(field);
      } catch (e) {
        console.error('[form-builder] drag-add field failed', e);
      }
    },
    openFieldModal(field) {
      this.modalField = field;
    },
    async onFieldSave(payload) {
      try {
        const saved = await this.api.updateField(payload.id, payload);
        const idx = this.fields.findIndex((f) => f.id === saved.id);
        if (idx > -1) this.fields.splice(idx, 1, saved);
        this.modalField = null;
      } catch (e) {
        console.error('[form-builder] save field failed', e);
      }
    },
    async onDeleteField(field) {
      if (!window.confirm('Delete this field?')) return;
      try {
        await this.api.deleteField(field.id);
        this.fields = this.fields.filter((f) => f.id !== field.id);
      } catch (e) {
        console.error('[form-builder] delete field failed', e);
      }
    },
    async onReorder(orderedFields) {
      this.fields = orderedFields;
      try {
        await this.api.reorder(orderedFields.map((f) => f.id));
      } catch (e) {
        console.error('[form-builder] reorder failed', e);
      }
    },
    async onSubmitTextSave(text) {
      this.form.submit_text = text;
      await this.onFormSave({ submit_text: text });
      this.submitModalOpen = false;
    },
    async onFormSave(partial) {
      try {
        const saved = await this.api.updateForm({ ...this.form, ...partial });
        this.form = { ...this.form, ...saved };
      } catch (e) {
        console.error('[form-builder] form save failed', e);
      }
    },
  },
};
</script>
