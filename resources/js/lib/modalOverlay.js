// Mixin for modal overlays: only close when a click both STARTS and ENDS on the
// overlay itself. Prevents the modal closing when a text selection drag begins
// inside the modal and the mouseup lands on the overlay (which would otherwise
// register as an overlay click).
export default {
  data() {
    return { _overlayMouseDown: false };
  },
  methods: {
    onOverlayMouseDown(e) {
      this._overlayMouseDown = e.target === e.currentTarget;
    },
    onOverlayClick(e) {
      if (this._overlayMouseDown && e.target === e.currentTarget) {
        this.$emit('close');
      }
      this._overlayMouseDown = false;
    },
  },
};
