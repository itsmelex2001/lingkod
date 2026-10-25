/**
 * User roles definitions and access tiers
 */

export const ROLES = {
  PATIENT: 'patient',
  STAFF: 'staff',
  ADMIN: 'admin'
};

export const ROLE_LABELS = {
  [ROLES.PATIENT]: 'Patient / Client',
  [ROLES.STAFF]: 'Medical Staff',
  [ROLES.ADMIN]: 'System Administrator'
};

export const ROLE_PORTALS = {
  [ROLES.PATIENT]: '/sdn/pages/department-select.php',
  [ROLES.STAFF]: '/sdn/pages/staff-dashboard.php',
  [ROLES.ADMIN]: '/sdn/pages/admin-dashboard.php'
};
