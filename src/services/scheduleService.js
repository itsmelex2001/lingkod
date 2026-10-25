import { api } from './api.js';

export const scheduleService = {
  /**
   * Fetch all doctor schedules and active slot counts
   */
  async getSchedules() {
    try {
      const res = await api.get('/sdn/api/schedule.php');
      if (res && res.success && Array.isArray(res.data)) {
        return res.data;
      }
      return [];
    } catch (err) {
      console.error('Failed to load doctor schedules:', err);
      return [];
    }
  }
};
