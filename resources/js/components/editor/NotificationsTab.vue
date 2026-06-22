<template>
  <div class="fb-tab-panel fb-tab-panel--list">
    <header class="fb-list__head">
      <h3 class="fb-list__title">Email Notifications</h3>
      <button type="button" class="button button--blue button--small" @click="openNew">+ Add notification</button>
    </header>

    <p v-if="!notifications.length" class="fb-list__empty">
      No notifications yet. Add one so submissions get emailed.
    </p>

    <ul v-else class="fb-list">
      <li v-for="n in notifications" :key="n.id" class="fb-list__item">
        <div class="fb-list__main" @click="openEdit(n)">
          <span class="fb-list__name">{{ n.name }}</span>
          <span class="fb-list__sub">{{ n.to }}</span>
        </div>
        <div class="fb-list__actions">
          <button
            type="button"
            class="fb-list__badge"
            :class="{ 'fb-list__badge--off': !Number(n.active) }"
            :title="Number(n.active) ? 'Click to disable' : 'Click to enable'"
            @click="toggleActive(n)"
          >{{ Number(n.active) ? 'Active' : 'Inactive' }}</button>
          <button type="button" class="fb-row__action" @click="openEdit(n)" title="Edit"><i class="fas fa-pen"></i></button>
          <button type="button" class="fb-row__action fb-row__action--danger" @click="remove(n)" title="Delete"><i class="fas fa-trash"></i></button>
        </div>
      </li>
    </ul>

    <notification-modal
      v-if="editing"
      :notification="editing"
      :fields="fields"
      @save="save"
      @close="editing = null"
    />
  </div>
</template>

<script>
import { createApi } from '../../lib/api';
import { confirmDelete } from '../../lib/confirmDelete';
import NotificationModal from './NotificationModal.vue';

export default {
  name: 'NotificationsTab',
  components: { NotificationModal },
  props: {
    formId: { type: Number, required: true },
    fields: { type: Array, default: () => [] },
    apiBase: { type: String, required: true },
  },
  data() {
    return {
      api: createApi(this.apiBase),
      notifications: [],
      editing: null,
    };
  },
  async created() {
    await this.load();
  },
  methods: {
    async load() {
      try { this.notifications = await this.api.notifications(); } catch (e) { console.error(e); }
    },
    openNew() {
      this.editing = {};
    },
    openEdit(n) {
      this.editing = { ...n };
    },
    async save(payload) {
      try {
        if (payload.id) {
          await this.api.updateNotification(payload.id, payload);
        } else {
          await this.api.createNotification(payload);
        }
        this.editing = null;
        await this.load();
      } catch (e) {
        console.error('[form-builder] save notification failed', e);
      }
    },
    async toggleActive(n) {
      const active = Number(n.active) ? 0 : 1;
      try {
        await this.api.updateNotification(n.id, { ...n, active });
        n.active = active;
      } catch (e) {
        console.error('[form-builder] toggle notification failed', e);
      }
    },
    async remove(n) {
      if (!(await confirmDelete('Delete this notification?'))) return;
      try {
        await this.api.deleteNotification(n.id);
        await this.load();
      } catch (e) {
        console.error('[form-builder] delete notification failed', e);
      }
    },
  },
};
</script>
