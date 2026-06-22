<template>
  <div class="fb-conditions">
    <div class="fb-field">
      <label class="fb-field__label">Conditional Logic</label>
      <toggle :model-value="enabled ? 1 : 0" @update:model-value="toggleEnabled" />
      <p class="fb-field__note">Show or hide this field based on other fields' values.</p>
    </div>

    <template v-if="enabled">
      <div class="fb-conditions__header">
        <select v-model="rules.action" class="fb-field__input" @change="emit">
          <option value="show">Show</option>
          <option value="hide">Hide</option>
          <option value="enable">Enable</option>
          <option value="disable">Disable</option>
        </select>
        <span>this field when</span>
        <select v-model="rules.logic" class="fb-field__input" @change="emit">
          <option value="and">all</option>
          <option value="or">any</option>
        </select>
        <span>of the following match:</span>
      </div>

      <div v-for="(rule, i) in rules.rules" :key="i" class="fb-conditions__rule">
        <select v-model.number="rule.field" class="fb-field__input" @change="emit">
          <option :value="null" disabled>Select a field…</option>
          <option v-for="f in fields" :key="f.id" :value="f.id">{{ f.name }}</option>
        </select>
        <select v-model="rule.operator" class="fb-field__input" @change="emit">
          <option v-for="op in operators" :key="op.value" :value="op.value">{{ op.label }}</option>
        </select>
        <input
          v-if="needsValue(rule.operator)"
          v-model="rule.value"
          type="text"
          class="fb-field__input"
          placeholder="Value"
          @input="emit"
        />
        <button type="button" class="fb-options__remove" @click="removeRule(i)">&times;</button>
      </div>

      <button type="button" class="button button--grey button--small" @click="addRule">+ Add condition</button>
    </template>
  </div>
</template>

<script>
import Toggle from './Toggle.vue';

const OPERATORS = [
  { value: 'equals', label: 'is equal to' },
  { value: 'not_equals', label: 'is not equal to' },
  { value: 'contains', label: 'contains' },
  { value: 'empty', label: 'is empty' },
  { value: 'not_empty', label: 'is not empty' },
  { value: 'checked', label: 'is checked' },
  { value: 'unchecked', label: 'is unchecked' },
  { value: 'gt', label: 'is greater than' },
  { value: 'lt', label: 'is less than' },
];

const VALUELESS = ['empty', 'not_empty', 'checked', 'unchecked'];

export default {
  name: 'ConditionsBuilder',
  components: { Toggle },
  props: {
    modelValue: { type: Object, default: null },
    fields: { type: Array, default: () => [] },
  },
  emits: ['update:modelValue'],
  data() {
    const initial = this.modelValue && this.modelValue.rules
      ? JSON.parse(JSON.stringify(this.modelValue))
      : { action: 'show', logic: 'and', rules: [] };
    return {
      operators: OPERATORS,
      enabled: !!(this.modelValue && Array.isArray(this.modelValue.rules) && this.modelValue.rules.length),
      rules: initial,
    };
  },
  methods: {
    needsValue(op) {
      return !VALUELESS.includes(op);
    },
    toggleEnabled(val) {
      this.enabled = !!Number(val);
      if (this.enabled && !this.rules.rules.length) {
        this.addRule();
      } else {
        this.emit();
      }
    },
    addRule() {
      this.rules.rules.push({ field: null, operator: 'equals', value: '' });
      this.emit();
    },
    removeRule(i) {
      this.rules.rules.splice(i, 1);
      this.emit();
    },
    emit() {
      if (!this.enabled || !this.rules.rules.length) {
        this.$emit('update:modelValue', null);
        return;
      }
      this.$emit('update:modelValue', JSON.parse(JSON.stringify(this.rules)));
    },
  },
};
</script>
