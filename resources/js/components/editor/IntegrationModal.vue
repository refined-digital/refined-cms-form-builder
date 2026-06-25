<template>
  <div class="fb-modal__overlay" @mousedown="onOverlayMouseDown" @click="onOverlayClick">
    <div class="fb-modal">
      <header class="fb-modal__head">
        <h3 class="fb-modal__title">{{ integration.name }}</h3>
        <button type="button" class="fb-modal__close" @click="$emit('close')">&times;</button>
      </header>

      <nav class="fb-modal__tabs">
        <button
          v-for="t in tabs"
          :key="t.key"
          type="button"
          class="fb-modal__tab"
          :class="{ 'fb-modal__tab--active': tab === t.key }"
          @click="tab = t.key"
        >{{ t.label }}</button>
      </nav>

      <div class="fb-modal__body">
        <!-- FIELDS -->
        <div v-show="tab === 'fields'" class="fb-modal__panel">
          <p class="fb-field__note">
            Toggle which fields are sent and drag to set the column order in the sheet.
          </p>

          <draggable
            v-model="rows"
            item-key="key"
            handle=".fb-row__handle"
            class="fb-integration__fields"
          >
            <template #item="{ element }">
              <div class="fb-integration__field" :class="{ 'fb-integration__field--synthetic': element.synthetic }">
                <span class="fb-row__handle" aria-hidden="true">⋮⋮</span>
                <span class="fb-integration__field-name">
                  {{ element.label }}
                  <em v-if="element.synthetic" class="fb-integration__field-tag">added</em>
                </span>
                <toggle :model-value="element.enabled ? 1 : 0" @update:model-value="element.enabled = !!Number($event)" />
              </div>
            </template>
          </draggable>
        </div>

        <!-- CONFIG -->
        <div v-show="tab === 'config'" class="fb-modal__panel">
          <div class="fb-field">
            <label class="fb-field__label">Send Email Notifications</label>
            <toggle :model-value="sendEmail ? 1 : 0" @update:model-value="sendEmail = !!Number($event)" />
            <p class="fb-field__note">Turn off to let this integration handle delivery instead of the form's notifications.</p>
          </div>

          <div v-for="setting in integration.settings" :key="setting.name" class="fb-field">
            <label class="fb-field__label">
              {{ setting.label }}<span v-if="setting.required" class="fb-field__req">*</span>
            </label>
            <select v-if="setting.type === 'select'" v-model="config[setting.name]" class="fb-field__input">
              <option v-for="opt in setting.options" :key="opt.value ?? opt" :value="opt.value ?? opt">{{ opt.label ?? opt }}</option>
            </select>
            <input v-else v-model="config[setting.name]" type="text" class="fb-field__input" />
          </div>
        </div>
      </div>

      <footer class="fb-modal__foot">
        <button type="button" class="button button--red button--small" @click="$emit('close')">Cancel</button>
        <button type="button" class="button button--blue button--small" @click="onSave">Save</button>
      </footer>
    </div>
  </div>
</template>

<script>
import draggable from 'vuedraggable';
import Toggle from './controls/Toggle.vue';
import modalAppClass from '../../lib/modalAppClass';
import modalOverlay from '../../lib/modalOverlay';

// synthetic (non-form) columns the processor knows how to render itself; their
// key is stored verbatim in config.fields so the processor can special-case it
const SYNTHETIC = [{ key: '__date', label: 'Date / Time' }];

export default {
  name: 'IntegrationModal',
  components: { draggable, Toggle },
  mixins: [modalAppClass, modalOverlay],
  props: {
    integration: { type: Object, required: true },
    fields: { type: Array, default: () => [] },
  },
  emits: ['save', 'close'],
  data() {
    const config = { ...(this.integration.config || {}) };
    return {
      tab: 'fields',
      tabs: [
        { key: 'fields', label: 'Fields' },
        { key: 'config', label: 'Config' },
      ],
      sendEmail: this.integration.send_email ?? true,
      config,
      rows: this.buildRows(config),
    };
  },
  methods: {
    /**
     * Merge saved field config with the form's current fields: keep saved order
     * and enabled state, append any new form fields, drop fields that no longer
     * exist. Synthetic rows (Date) sit wherever they were dragged.
     */
    buildRows(config) {
      const saved = Array.isArray(config.fields) ? config.fields : [];
      const formRows = this.fields.map((f) => ({
        key: `field${f.id}`,
        label: f.name || `Field ${f.id}`,
        enabled: true,
        synthetic: false,
      }));
      const syntheticRows = SYNTHETIC.map((s) => ({
        key: s.key,
        label: s.label,
        enabled: true,
        synthetic: true,
      }));

      const all = [...syntheticRows, ...formRows];
      const byKey = Object.fromEntries(all.map((r) => [r.key, r]));

      const ordered = [];
      const seen = new Set();
      saved.forEach((s) => {
        const row = byKey[s.key];
        if (row && !seen.has(s.key)) {
          row.enabled = !!s.enabled;
          ordered.push(row);
          seen.add(s.key);
        }
      });
      all.forEach((row) => {
        if (!seen.has(row.key)) ordered.push(row);
      });

      return ordered;
    },
    onSave() {
      // one ordered list including the synthetic Date row — the processor walks
      // it in order, special-casing synthetic keys (e.g. __date)
      const config = { ...this.config };
      config.fields = this.rows.map((r) => ({ key: r.key, enabled: r.enabled }));

      this.$emit('save', { send_email: this.sendEmail, config });
    },
  },
};
</script>
