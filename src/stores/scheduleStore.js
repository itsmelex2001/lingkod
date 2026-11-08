import { writable, derived } from 'svelte/store';
import { scheduleService } from '../services/scheduleService.js';

export function createScheduleStore() {
  const doctors = writable([]);
  const loading = writable(false);
  const error = writable(null);
  const searchQuery = writable('');
  const selectedDepartment = writable('All');
  const showOnlyAvailable = writable(false);

  // Derived filtered doctors list
  const filteredDoctors = derived(
    [doctors, searchQuery, selectedDepartment, showOnlyAvailable],
    ([$doctors, $searchQuery, $selectedDepartment, $showOnlyAvailable]) => {
      const q = $searchQuery.toLowerCase().trim();
      const dept = $selectedDepartment.toLowerCase().split(' ')[0];

      return $doctors.filter(d => {
        const nameMatch = (d.name || '').toLowerCase().includes(q);
        const specMatch = (d.specialization || '').toLowerCase().includes(q);
        const matchSearch = !q || nameMatch || specMatch;

        const matchDept = $selectedDepartment === 'All' || (d.department || '').toLowerCase().includes(dept);

        const isAvail = (d.status || 'active').toLowerCase() !== 'leave';
        const matchAvail = $showOnlyAvailable ? isAvail : true;

        return matchSearch && matchDept && matchAvail;
      });
    }
  );

  return {
    doctors,
    loading,
    error,
    searchQuery,
    selectedDepartment,
    showOnlyAvailable,
    filteredDoctors,
    loadSchedules: async () => {
      loading.set(true);
      error.set(null);
      try {
        const data = await scheduleService.getSchedules();
        doctors.set(data);
      } catch (err) {
        error.set(err.message || 'Failed to load schedules');
      } finally {
        loading.set(false);
      }
    },
    setSearch: (q) => searchQuery.set(q),
    setDepartment: (dept) => selectedDepartment.set(dept),
    toggleAvailableOnly: () => showOnlyAvailable.update(v => !v)
  };
}

export const scheduleStore = createScheduleStore();
