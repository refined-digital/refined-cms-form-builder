<template>
  <div class="fb-richtext">
    <div class="fb-richtext__toolbar">
      <button type="button" @mousedown.prevent="cmd('bold')" title="Bold"><b>B</b></button>
      <button type="button" @mousedown.prevent="cmd('italic')" title="Italic"><i>I</i></button>
      <button type="button" @mousedown.prevent="formatBlock('H2')" title="Heading">H</button>
      <button type="button" @mousedown.prevent="makeLink" title="Link"><i class="fas fa-link"></i></button>
      <button type="button" @mousedown.prevent="cmd('unlink')" title="Unlink"><i class="fas fa-unlink"></i></button>
      <span class="fb-richtext__sep"></span>
      <div class="fb-richtext__tokens">
        <button type="button" class="fb-richtext__token-btn" @mousedown.prevent="toggleMenu">
          Insert field ▾
        </button>
        <ul v-if="menuOpen" class="fb-richtext__menu">
          <li @mousedown.prevent="insertToken('all')">All form fields</li>
          <li v-for="f in fields" :key="f.id" @mousedown.prevent="insertToken(f.id, f.name)">{{ f.name }}</li>
        </ul>
      </div>
    </div>

    <div
      ref="editor"
      class="fb-richtext__editable fb-field__input"
      contenteditable="true"
      @input="onInput"
      @blur="onInput"
    ></div>
  </div>
</template>

<script>
// Lightweight rich text with field-token chips. Avoids bundling a second tiptap
// (core has it; we only share Vue). Tokens serialise to [[field:<id>]] / [[fields]]
// in the stored HTML and render as chips when loaded.
export default {
  name: 'SimpleRichText',
  props: {
    modelValue: { type: String, default: '' },
    fields: { type: Array, default: () => [] },
  },
  emits: ['update:modelValue'],
  data() {
    return { menuOpen: false };
  },
  mounted() {
    this.$refs.editor.innerHTML = this.tokensToChips(this.modelValue || '');
  },
  methods: {
    cmd(command) {
      document.execCommand(command, false, null);
      this.onInput();
    },
    formatBlock(tag) {
      document.execCommand('formatBlock', false, tag);
      this.onInput();
    },
    makeLink() {
      const url = window.prompt('Link URL (https://…)');
      if (url) document.execCommand('createLink', false, url);
      this.onInput();
    },
    toggleMenu() {
      this.menuOpen = !this.menuOpen;
    },
    insertToken(id, label) {
      const text = id === 'all' ? 'All form fields' : label;
      const token = id === 'all' ? '[[fields]]' : `[[field:${id}]]`;
      const chip = `<span class="fb-token" contenteditable="false" data-token="${token}">${text}</span>&nbsp;`;
      document.execCommand('insertHTML', false, chip);
      this.menuOpen = false;
      this.onInput();
    },
    onInput() {
      this.$emit('update:modelValue', this.chipsToTokens(this.$refs.editor.innerHTML));
    },
    // chips -> stored tokens
    chipsToTokens(html) {
      const div = document.createElement('div');
      div.innerHTML = html;
      div.querySelectorAll('[data-token]').forEach((el) => {
        el.replaceWith(document.createTextNode(el.getAttribute('data-token')));
      });
      return div.innerHTML;
    },
    // stored tokens -> chips for display
    tokensToChips(html) {
      return html
        .replace(/\[\[fields\]\]/g, '<span class="fb-token" contenteditable="false" data-token="[[fields]]">All form fields</span>')
        .replace(/\[\[field:(\d+)\]\]/g, (m, id) => {
          const f = this.fields.find((x) => String(x.id) === id);
          const label = f ? f.name : `Field ${id}`;
          return `<span class="fb-token" contenteditable="false" data-token="[[field:${id}]]">${label}</span>`;
        });
    },
  },
};
</script>
