/**
 * Field and input validation utilities
 */

export function isValidEmail(email) {
  if (!email) return false;
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email).trim());
}

export function isValidPhilippinePhone(phone) {
  if (!phone) return false;
  const clean = String(phone).replace(/[\s\-()]/g, '');
  return /^(09|\+639)\d{9}$/.test(clean);
}

export function isValidPatientId(id) {
  if (!id) return false;
  return /^PAT-\d{4}-\d+$/i.test(String(id).trim());
}
