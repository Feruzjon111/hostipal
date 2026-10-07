import { reactive } from 'vue';
import { api } from './api';

export const auth = reactive({
  user: null,
  initialized: false,
});

export const lookups = reactive({
  regions: [],
  districts: [],
  diseases: [],
  hospitals: [],
  doctors: [],
});

export const ui = reactive({ toast: null });
let toastTimer;

export async function initializeAuth() {
  if (auth.initialized) {
    return;
  }

  try {
    const data = await api('/me');
    auth.user = data.user;
  } catch (error) {
    if (error.status !== 401) {
      notify(error.message, 'error');
    }
  } finally {
    auth.initialized = true;
  }
}

export async function loadLookups() {
  const data = await api('/lookups');
  lookups.regions = data.regions;
  lookups.districts = data.districts;
  lookups.diseases = data.diseases;
  lookups.hospitals = data.hospitals;
  lookups.doctors = data.doctors;
}

export function notify(message, type = 'success') {
  clearTimeout(toastTimer);
  ui.toast = { message, type };

  toastTimer = setTimeout(() => {
    ui.toast = null;
  }, 5000);
}

export function confirmDelete(title, message) {
  return window.confirm(title + '\n\n' + message);
}
