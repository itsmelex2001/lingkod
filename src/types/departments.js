/**
 * Department Constants and Definitions for MMWGH LINKod
 */

export const DEPARTMENTS = {
  ALL: 'All',
  PSYCHIATRY: 'Psychiatry',
  NEUROLOGY: 'Neurology',
  GENERAL_MEDICAL: 'General Medical',
  GENERAL_MANAGEMENT: 'General Medical'
};

export const DEPARTMENT_LIST = [
  DEPARTMENTS.ALL,
  DEPARTMENTS.PSYCHIATRY,
  DEPARTMENTS.NEUROLOGY,
  DEPARTMENTS.GENERAL_MEDICAL
];

export const DEPARTMENT_METADATA = {
  [DEPARTMENTS.PSYCHIATRY]: {
    label: 'Psychiatry (OPU)',
    description: 'Specialized psychiatric consultations and acute mental health care.',
    code: 'PSYCH',
    badgeColor: '#ec4899',
    badgeBg: '#fdf2f8'
  },
  [DEPARTMENTS.NEUROLOGY]: {
    label: 'Neurology Department',
    description: 'Neurological evaluations, EEG interpretations, and nerve specialists.',
    code: 'NEURO',
    badgeColor: '#3b82f6',
    badgeBg: '#eff6ff'
  },
  [DEPARTMENTS.GENERAL_MEDICAL]: {
    label: 'General Medical / Triage',
    description: 'Routine check-ups, acute primary care, preventative consultations, and general medical triage.',
    code: 'GEN',
    badgeColor: '#059669',
    badgeBg: '#ecfdf5'
  }
};
