import { createContext, useContext, useMemo, useState } from 'react';
import { useEffect } from 'react';
import api from '../services/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [token, setToken] = useState(localStorage.getItem('ims_token'));
  const [user, setUser] = useState(() => {
    const raw = localStorage.getItem('ims_user');
    return raw ? JSON.parse(raw) : null;
  });

  useEffect(() => {
    const clearSession = () => {
      setToken(null);
      setUser(null);
    };

    window.addEventListener('ims:session-expired', clearSession);
    return () => window.removeEventListener('ims:session-expired', clearSession);
  }, []);

  const login = async (email, password) => {
    const response = await api.post('/login', { email, password });
    const payload = response.data;

    localStorage.setItem('ims_token', payload.token);
    localStorage.setItem('ims_user', JSON.stringify(payload.user));

    setToken(payload.token);
    setUser(payload.user);
  };

  const logout = async () => {
    try {
      await api.post('/logout');
    } finally {
      localStorage.removeItem('ims_token');
      localStorage.removeItem('ims_user');
      setToken(null);
      setUser(null);
    }
  };

  const value = useMemo(
    () => ({ token, user, login, logout }),
    [token, user]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}
