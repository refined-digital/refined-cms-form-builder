// thin axios wrapper for the editor JSON API. Base URL is supplied by the blade
// (route('refined.form-builder.api...') with the form id baked in) so we never
// reconstruct routes on the client.
export function createApi(baseUrl) {
  const axios = window.axios;
  const base = baseUrl.replace(/\/$/, '');

  return {
    fieldTypes: () => axios.get(`${base}/field-types`).then((r) => r.data.data),
    fields: () => axios.get(`${base}/fields`).then((r) => r.data.data),
    createField: (payload) => axios.post(`${base}/fields`, payload).then((r) => r.data.data),
    updateField: (id, payload) => axios.put(`${base}/fields/${id}`, payload).then((r) => r.data.data),
    deleteField: (id) => axios.delete(`${base}/fields/${id}`).then((r) => r.data.data),
    reorder: (order) => axios.post(`${base}/fields/reorder`, { order }).then((r) => r.data.data),
    updateForm: (payload) => axios.put(`${base}`, payload).then((r) => r.data.data),

    // email notifications
    notifications: () => axios.get(`${base}/notifications`).then((r) => r.data.data),
    createNotification: (payload) => axios.post(`${base}/notifications`, payload).then((r) => r.data.data),
    updateNotification: (id, payload) => axios.put(`${base}/notifications/${id}`, payload).then((r) => r.data.data),
    deleteNotification: (id) => axios.delete(`${base}/notifications/${id}`).then((r) => r.data.data),

    // integrations (Phase 7)
    integrations: () => axios.get(`${base}/integrations`).then((r) => r.data.data),
    updateIntegration: (key, payload) => axios.put(`${base}/integrations/${key}`, payload).then((r) => r.data.data),
  };
}
