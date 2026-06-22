// thin axios wrapper for the editor JSON API. Base URL is supplied by the blade
// (route('refined.form-builder.api...') with the form id baked in) so we never
// reconstruct routes on the client.
//
// Mutations (create/update/delete/reorder) are wrapped in track() so the editor's
// SaveIndicator shows saving → saved feedback. GET loads are not tracked.
import { track } from './saveStatus';

export function createApi(baseUrl) {
  const axios = window.axios;
  const base = baseUrl.replace(/\/$/, '');

  const save = (promise) => track(promise.then((r) => r.data.data));
  const load = (promise) => promise.then((r) => r.data.data);

  return {
    fieldTypes: () => load(axios.get(`${base}/field-types`)),
    fields: () => load(axios.get(`${base}/fields`)),
    createField: (payload) => save(axios.post(`${base}/fields`, payload)),
    updateField: (id, payload) => save(axios.put(`${base}/fields/${id}`, payload)),
    deleteField: (id) => save(axios.delete(`${base}/fields/${id}`)),
    reorder: (order) => save(axios.post(`${base}/fields/reorder`, { order })),
    updateForm: (payload) => save(axios.put(`${base}`, payload)),

    // email notifications
    notifications: () => load(axios.get(`${base}/notifications`)),
    createNotification: (payload) => save(axios.post(`${base}/notifications`, payload)),
    updateNotification: (id, payload) => save(axios.put(`${base}/notifications/${id}`, payload)),
    deleteNotification: (id) => save(axios.delete(`${base}/notifications/${id}`)),

    // integrations (Phase 7)
    integrations: () => load(axios.get(`${base}/integrations`)),
    updateIntegration: (key, payload) => save(axios.put(`${base}/integrations/${key}`, payload)),
  };
}
