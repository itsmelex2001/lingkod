import { mount } from 'svelte'
import './app.css'
import App from './App.svelte'
import LoginApp from './LoginApp.svelte'
import SchedulesApp from './SchedulesApp.svelte'
import NewPatientRegistrationApp from './NewPatientRegistrationApp.svelte'
import BookingSelectionModal from './components/booking/BookingSelectionModal.svelte'
import PatientDashboardApp from './PatientDashboardApp.svelte'

if (document.getElementById('app')) {
  const app = mount(App, {
    target: document.getElementById('app'),
  })
}

if (document.getElementById('login-app')) {
  const loginAppEl = document.getElementById('login-app');
  const loginApp = mount(LoginApp, {
    target: loginAppEl,
    props: {
      error: loginAppEl.dataset.error || '',
      input: loginAppEl.dataset.input || '',
      role: loginAppEl.dataset.role || ''
    }
  })
}

if (document.getElementById('schedules-app')) {
  const schedulesApp = mount(SchedulesApp, {
    target: document.getElementById('schedules-app'),
  })
}

if (document.getElementById('new-patient-registration-app')) {
  const newPatientApp = mount(NewPatientRegistrationApp, {
    target: document.getElementById('new-patient-registration-app'),
  })
}

if (document.getElementById('booking-selection-modal-mount')) {
  const bookingModal = mount(BookingSelectionModal, {
    target: document.getElementById('booking-selection-modal-mount'),
  })
}

if (document.getElementById('patient-dashboard-app')) {
  const patientDashboardEl = document.getElementById('patient-dashboard-app');
  const patientDashboard = mount(PatientDashboardApp, {
    target: patientDashboardEl,
    props: {
      patientName: patientDashboardEl.dataset.patientName || '',
      patientId: patientDashboardEl.dataset.patientId || ''
    }
  })
}
