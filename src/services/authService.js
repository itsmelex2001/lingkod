import { api } from './api.js';

export const authService = {
  /**
   * Submit credentials to authenticate user
   */
  async login(username, password) {
    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);
    return await api.post('/sdn/api/auth.php', formData);
  },

  /**
   * Log out current user
   */
  async logout() {
    return await api.post('/sdn/pages/logout.php', {});
  }
};
