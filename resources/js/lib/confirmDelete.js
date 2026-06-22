// SweetAlert delete confirmation, matching the CMS's core delete prompt
// (see core ConfirmDelete directive). Resolves true if the user confirms.
export function confirmDelete(title = 'Are you sure?') {
  const swal = window.swal;
  if (!swal) {
    // fall back to native confirm if swal isn't available
    return Promise.resolve(window.confirm(title));
  }
  return swal({
    title,
    icon: 'warning',
    buttons: true,
    dangerMode: true,
  }).then((value) => !!value);
}
