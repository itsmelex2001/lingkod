import { writable } from 'svelte/store';

export function createAuthStore() {
  const { subscribe, set, update } = writable({
    user: null,
    role: '',
    error: '',
    isLoading: false,
    isAuthenticated: false
  });

  return {
    subscribe,
    setRole: (role) => update(s => ({ ...s, role })),
    setError: (error) => update(s => ({ ...s, error })),
    setLoading: (isLoading) => update(s => ({ ...s, isLoading })),
    setUser: (user, role) => set({
      user,
      role: role || (user ? user.role : ''),
      error: '',
      isLoading: false,
      isAuthenticated: !!user
    }),
    reset: () => set({
      user: null,
      role: '',
      error: '',
      isLoading: false,
      isAuthenticated: false
    })
  };
}

export const authStore = createAuthStore();
