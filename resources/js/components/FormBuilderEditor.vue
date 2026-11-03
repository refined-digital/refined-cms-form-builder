<template>
  <div class="fb-editor">
    <save-indicator :status="saveStatus"></save-indicator>

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
        <div class="fb-editor__main">
        <page-tabs
          v-if="usesPages && pages.length"
          :pages="pages"
          :current="currentPageId"
          @select="currentPageId = $event"
          @add="onAddPage"
          @rename="onRenamePage"
          @delete="onDeletePage"
          @reorder="onReorderPages"
        />
        <editor-canvas
          :fields="visibleFields"
          :field-types="fieldTypes"
          :submit-text="form.submit_text"
          :loading="loading"
          @reorder="onReorder"
          @edit-field="openFieldModal"
          @edit-submit="submitModalOpen = true"
          @delete-field="onDeleteField"
          @add-field-at="onAddFieldAt"
        />
        </div>
        <field-palette
          :field-types="fieldTypes"
          @add="onAddField"
        />
      </div>

      <!-- OTHER TABS (filled in later phases) -->
      <behaviour-tab v-if="tab === 'behaviour'" :form="form" :fields="fields" @save="onFormSave" />
      <notifications-tab v-if="tab === 'notifications'" :form-id="form.id" :fields="fields" :api-base="apiBase" />
      <integrations-tab v-if="tab === 'integrations'" :form-id="form.id" :fields="fields" :api-base="apiBase" @changed="integrationsEnabled = $event" />
      <settings-tab v-if="tab === 'settings'" :form="form" @save="onFormSave" />
    </div>

    <field-modal
      v-if="modalField"
      :field="modalField"
      :field-types="fieldTypes"
      :fields="fields"
      :integrations-enabled="integrationsEnabled"
      :pages="pages"
      :uses-pages="usesPages"
      @save="onFieldSave"
      @close="onFieldModalClose"
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
import { confirmDelete } from '../lib/confirmDelete';
import { saveStatus } from '../lib/saveStatus';
import EditorCanvas from './editor/EditorCanvas.vue';
import FieldPalette from './editor/FieldPalette.vue';
import FieldModal from './editor/FieldModal.vue';
import SubmitModal from './editor/SubmitModal.vue';
import BehaviourTab from './editor/BehaviourTab.vue';
import NotificationsTab from './editor/NotificationsTab.vue';
import IntegrationsTab from './editor/IntegrationsTab.vue';
import SettingsTab from './editor/SettingsTab.vue';
import PageTabs from './editor/PageTabs.vue';
import SaveIndicator from './editor/SaveIndicator.vue';

export default {
  name: 'FormBuilderEditor',
  components: {
    EditorCanvas, FieldPalette, FieldModal, SubmitModal,
    BehaviourTab, NotificationsTab, IntegrationsTab, SettingsTab, SaveIndicator, PageTabs,
  },
  computed: {
    usesPages() {
      return !!Number(this.form.use_pages);
    },
    // fields on the current page, in global order; every field when pages are off
    visibleFields() {
      if (!this.usesPages) return this.fields;
      return this.fields.filter((f) => Number(f.form_page_id) === this.currentPageId);
    },
    saveStatus() {
      return saveStatus.value;
    },
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
      pages: [],
      currentPageId: null,
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
      const [types, fields, pages, integrations] = await Promise.all([
        this.api.fieldTypes(),
        this.api.fields(),
        this.api.pages().catch(() => []),
        this.api.integrations().catch(() => []),
      ]);
      this.fieldTypes = types;
      this.fields = fields;
      this.pages = pages;
      this.integrationsEnabled = integrations.some((i) => i.enabled);
      await this.ensurePages();
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
    /**
     * When the form uses pages, make sure there is a first page ("Page 1") and
     * that every field belongs to a page, then select a valid current page.
     */
    async ensurePages() {
      if (!this.usesPages) return;
      try {
        if (!this.pages.length) {
          this.pages = [await this.api.createPage('Page 1')];
        }
        const first = this.pages[0];
        const orphans = this.fields.filter((f) => !f.form_page_id);
        for (const field of orphans) {
          const saved = await this.api.updateField(field.id, { ...field, form_page_id: first.id });
          const idx = this.fields.findIndex((f) => f.id === saved.id);
          if (idx > -1) this.fields.splice(idx, 1, saved);
        }
      } catch (e) {
        console.error('[form-builder] page setup failed', e);
      }
      if (!this.pages.some((p) => p.id === this.currentPageId)) {
        this.currentPageId = this.pages.length ? this.pages[0].id : null;
      }
    },
    /**
     * Rebuild the full field order after the current page's fields changed:
     * pages in tab order, each page's fields in the given order.
     */
    composeFields(currentFields) {
      const rest = this.fields.filter((f) => !currentFields.includes(f));
      const ordered = [];
      this.pages.forEach((p) => {
        const list = p.id === this.currentPageId
          ? currentFields
          : rest.filter((f) => Number(f.form_page_id) === p.id);
        ordered.push(...list);
      });
      const placed = new Set(ordered);
      return [...ordered, ...rest.filter((f) => !placed.has(f))];
    },
    async onAddPage() {
      try {
        const page = await this.api.createPage(`Page ${this.pages.length + 1}`);
        this.pages.push(page);
        this.currentPageId = page.id;
      } catch (e) {
        console.error('[form-builder] create page failed', e);
      }
    },
    async onRenamePage({ id, name }) {
      try {
        const saved = await this.api.renamePage(id, name);
        const idx = this.pages.findIndex((p) => p.id === id);
        if (idx > -1) this.pages.splice(idx, 1, saved);
      } catch (e) {
        console.error('[form-builder] rename page failed', e);
      }
    },
    async onDeletePage(id) {
      if (!(await confirmDelete('Delete this page? Its fields will move to the previous page.'))) return;
      try {
        const idx = this.pages.findIndex((p) => p.id === id);
        await this.api.deletePage(id);
        this.pages = this.pages.filter((p) => p.id !== id);
        this.fields = await this.api.fields();
        if (this.currentPageId === id) {
          this.currentPageId = this.pages[Math.max(0, idx - 1)].id;
        }
      } catch (e) {
        console.error('[form-builder] delete page failed', e);
      }
    },
    async onReorderPages(ids) {
      this.pages = ids.map((id) => this.pages.find((p) => p.id === id));
      try {
        await this.api.reorderPages(ids);
      } catch (e) {
        console.error('[form-builder] reorder pages failed', e);
      }
    },
    defaultFieldPayload(type) {
      return {
        ...(this.usesPages && this.currentPageId ? { form_page_id: this.currentPageId } : {}),
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
    // click-to-add: append, then open the modal (as a new field)
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
        if (this.usesPages) {
          const list = [...this.visibleFields];
          list.splice(index, 0, field);
          this.fields = this.composeFields(list);
        } else {
          this.fields.splice(index, 0, field);
        }
        await this.api.reorder(this.fields.map((f) => f.id));
        this.openFieldModal(field);
      } catch (e) {
        console.error('[form-builder] drag-add field failed', e);
      }
    },
    openFieldModal(field) {
      this.modalField = field;
    },
    onFieldModalClose() {
      this.modalField = null;
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
      if (!(await confirmDelete('Delete this field?'))) return;
      try {
        await this.api.deleteField(field.id);
        this.fields = this.fields.filter((f) => f.id !== field.id);
      } catch (e) {
        console.error('[form-builder] delete field failed', e);
      }
    },
    async onReorder(orderedFields) {
      this.fields = this.usesPages ? this.composeFields(orderedFields) : orderedFields;
      try {
        await this.api.reorder(this.fields.map((f) => f.id));
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
        await this.ensurePages();
      } catch (e) {
        console.error('[form-builder] form save failed', e);
      }
    },
  },
};
</script>
