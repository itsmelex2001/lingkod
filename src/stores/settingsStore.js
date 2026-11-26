import { writable, derived } from 'svelte/store';

function clone(obj) {
  return JSON.parse(JSON.stringify(obj));
}

const DEFAULT_STATE = {
  general: {
    hospital_name: 'Mariveles Mental Wellness and General Hospital',
    contact_phone: '',
    emergency_hotline: '',
    contact_email: '',
    hospital_address: '',
    clinic_hours: {
      monday: { is_open: true, open: '08:00', close: '17:00' },
      tuesday: { is_open: true, open: '08:00', close: '17:00' },
      wednesday: { is_open: true, open: '08:00', close: '17:00' },
      thursday: { is_open: true, open: '08:00', close: '17:00' },
      friday: { is_open: true, open: '08:00', close: '17:00' },
      saturday: { is_open: false, open: '08:00', close: '12:00' },
      sunday: { is_open: false, open: '08:00', close: '12:00' },
    },
    hospital_holidays: [],
  },
  scheduling: {
    qr_code_expiry_hours: 48,
    departments: [],
  },
  meta: {
    updated_at: null,
  },
};

export function createSettingsStore() {
  const serverState = writable(clone(DEFAULT_STATE));
  const draft = writable(clone(DEFAULT_STATE));
  const fieldErrors = writable({});
  const loading = writable(false);
  const saving = writable(false);

  const dirtySections = derived([serverState, draft], ([$server, $draft]) => ({
    general: JSON.stringify($server.general) !== JSON.stringify($draft.general),
    scheduling: JSON.stringify($server.scheduling) !== JSON.stringify($draft.scheduling),
  }));

  function hydrate(data) {
    const merged = {
      general: {
        ...clone(DEFAULT_STATE.general),
        ...(data.general || {}),
        hospital_holidays: Array.isArray(data.general?.hospital_holidays)
          ? clone(data.general.hospital_holidays)
          : [],
      },
      scheduling: { ...clone(DEFAULT_STATE.scheduling), ...(data.scheduling || {}) },
      meta: { ...(data.meta || {}) },
    };
    serverState.set(clone(merged));
    draft.set(clone(merged));
    fieldErrors.set({});
  }

  function commitSection(section, freshSectionData) {
    serverState.update((s) => ({
      ...s,
      [section]: clone(freshSectionData),
      meta: { ...s.meta, updated_at: new Date().toISOString() },
    }));
    draft.update((d) => ({
      ...d,
      [section]: clone(freshSectionData),
    }));
    fieldErrors.set({});
  }

  function discardSection(section) {
    let snapshot;
    const unsub = serverState.subscribe((s) => {
      snapshot = clone(s[section]);
    });
    unsub();
    draft.update((d) => ({ ...d, [section]: snapshot }));
    fieldErrors.set({});
  }

  return {
    serverState,
    draft,
    fieldErrors,
    loading,
    saving,
    dirtySections,
    hydrate,
    commitSection,
    discardSection,
  };
}
