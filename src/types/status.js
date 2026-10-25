/**
 * Status definitions for Doctors, Appointments, and Reminders
 */

export const DOCTOR_STATUS = {
  AVAILABLE: 'available',
  IN_CLINIC: 'in_clinic',
  ON_BREAK: 'on_break',
  EMERGENCY: 'emergency',
  ON_LEAVE: 'leave',
  FULLY_BOOKED: 'booked'
};

export const DOCTOR_STATUS_LABELS = {
  [DOCTOR_STATUS.AVAILABLE]: 'Available',
  [DOCTOR_STATUS.IN_CLINIC]: 'In Clinic',
  [DOCTOR_STATUS.ON_BREAK]: 'On Break',
  [DOCTOR_STATUS.EMERGENCY]: 'Emergency',
  [DOCTOR_STATUS.ON_LEAVE]: 'On Leave',
  [DOCTOR_STATUS.FULLY_BOOKED]: 'Fully Booked'
};

export const APPOINTMENT_STATUS = {
  PENDING: 'pending',
  FOR_APPROVAL: 'for_approval',
  APPROVED: 'approved',
  ARRIVED: 'arrived',
  ONGOING: 'ongoing',
  COMPLETED: 'completed',
  TRANSFERRED: 'transferred',
  NO_SHOW: 'no-show',
  REJECTED: 'rejected'
};

export const REMINDER_STATUS = {
  PENDING: 'Pending',
  SENT: 'Sent',
  FAILED: 'Failed'
};

/**
 * Get display status, pill class, and bookable state for a doctor schedule
 * @param {Object} doc - Doctor object
 * @returns {{ label: string, pillClass: string, isBookable: boolean, buttonText: string }}
 */
export function getDoctorDisplayStatus(doc) {
  if (!doc) return { label: 'Available', pillClass: 'bg-avail', isBookable: true, buttonText: 'Book' };

  const booked = parseInt(doc.booked_slots || 0, 10);
  const total = parseInt(doc.total_slots || 20, 10);
  const isFull = total > 0 && booked >= total;
  const raw = (doc.status || 'available').toLowerCase().trim();

  // If slots are completely booked or status is explicitly booked
  if (isFull || raw === 'booked' || raw === 'fully booked' || raw === 'full') {
    return {
      label: 'Full',
      pillClass: 'bg-full',
      isBookable: false,
      buttonText: 'Waitlist'
    };
  }

  // Doctor on leave
  if (raw === 'leave' || raw === 'on_leave' || raw === 'on leave') {
    return {
      label: 'On Leave',
      pillClass: 'bg-leave',
      isBookable: false,
      buttonText: 'On Leave'
    };
  }

  // Doctor in emergency
  if (raw === 'emergency') {
    return {
      label: 'Emergency',
      pillClass: 'bg-emergency',
      isBookable: false,
      buttonText: 'Emergency'
    };
  }

  // Doctor on break
  if (raw === 'on_break' || raw === 'break') {
    return {
      label: 'On Break',
      pillClass: 'bg-break',
      isBookable: true,
      buttonText: 'Book'
    };
  }

  // Doctor in clinic
  if (raw === 'in_clinic' || raw === 'in-clinic' || raw === 'in clinic') {
    return {
      label: 'In Clinic',
      pillClass: 'bg-inclinic',
      isBookable: true,
      buttonText: 'Book'
    };
  }

  // Default: Available
  return {
    label: 'Available',
    pillClass: 'bg-avail',
    isBookable: true,
    buttonText: 'Book'
  };
}

