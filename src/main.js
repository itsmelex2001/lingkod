import { mount } from 'svelte'
import './app.css'
import App from './App.svelte'
import LoginApp from './LoginApp.svelte'
import SchedulesApp from './SchedulesApp.svelte'

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
