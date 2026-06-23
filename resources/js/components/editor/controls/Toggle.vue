<template>
  <button
    type="button"
    class="fb-toggle"
    :class="{ 'fb-toggle--on': isOn }"
    role="switch"
    :aria-checked="isOn"
    @click="toggle"
  >
    <span class="fb-toggle__track"><span class="fb-toggle__thumb"></span></span>
    <span class="fb-toggle__label">{{ isOn ? 'Yes' : 'No' }}</span>
  </button>
</template>

<script>
export default {
  name: 'Toggle',
  props: {
    // accepts 0/1, true/false
    modelValue: { type: [Number, Boolean], default: 0 },
  },
  emits: ['update:modelValue'],
  computed: {
    isOn() {
      return !!Number(this.modelValue);
    },
  },
  methods: {
    toggle() {
      this.$emit('update:modelValue', this.isOn ? 0 : 1);
    },
  },
};
</script>
