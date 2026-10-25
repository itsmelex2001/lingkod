import { api } from './api.js';

export const statsService = {
  /**
   * Fetch public hospital statistics for landing hero section
   */
  async getPublicStats() {
    try {
      const res = await api.get('/sdn/api/public-stats.php');
      if (res && res.status === 'success') {
        return {
          appointmentsBooked: res.appointmentsBooked || '0',
          slotsAvailable: res.slotsAvailable || '0',
          registeredPatients: res.registeredPatients || '0'
        };
      }
      return { appointmentsBooked: '0', slotsAvailable: '0', registeredPatients: '0' };
    } catch (err) {
      console.error('Failed to load public stats:', err);
      return { appointmentsBooked: '0', slotsAvailable: '0', registeredPatients: '0' };
    }
  }
};
