/**
 * Clinical and presentation formatting helpers
 */

/**
 * Format 24h or HH:mm time string to 12h AM/PM
 * @param {string} timeStr - e.g. "14:30" or "09:00"
 * @returns {string} e.g. "2:30 PM"
 */
export function formatTime12h(timeStr) {
  if (!timeStr) return 'N/A';
  const parts = String(timeStr).split(':');
  if (parts.length < 2) return timeStr;
  let hr = parseInt(parts[0], 10);
  const min = parts[1].padStart(2, '0');
  const ampm = hr >= 12 ? 'PM' : 'AM';
  hr = hr % 12 || 12;
  return `${hr}:${min} ${ampm}`;
}

/**
 * Format live clock string
 * @param {Date} [date]
 * @returns {string} e.g. "10:30 PM"
 */
export function getLiveClockString(date = new Date()) {
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

/**
 * Extract 2-letter uppercase initials from full name
 * @param {string} name 
 * @returns {string} e.g. "Dr. Leo Cruz" -> "LC"
 */
export function getInitials(name) {
  if (!name) return '??';
  const clean = name.replace(/^Dr\.\s*/i, '').trim();
  const parts = clean.split(/\s+/).filter(Boolean);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return clean.slice(0, 2).toUpperCase();
}

/**
 * Calculate slot capacity percentage
 * @param {number|string} booked 
 * @param {number|string} total 
 * @returns {number} 0-100
 */
export function getCapacityPercent(booked, total) {
  const b = parseInt(booked, 10) || 0;
  const t = parseInt(total, 10) || 0;
  if (t <= 0) return 0;
  return Math.min(100, Math.round((b / t) * 100));
}
