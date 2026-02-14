import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const SAVED_EMAIL_KEY = 'ims_saved_email';

function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();

  const [form, setForm] = useState({ email: 'admin@invoicesystem.com', password: 'password' });
  const [saveAccount, setSaveAccount] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const savedEmail = localStorage.getItem(SAVED_EMAIL_KEY);
    if (savedEmail) {
      setForm((prev) => ({ ...prev, email: savedEmail }));
      setSaveAccount(true);
    }
  }, []);

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError('');
    setLoading(true);

    try {
      await login(form.email, form.password);
      if (saveAccount) {
        localStorage.setItem(SAVED_EMAIL_KEY, form.email.trim());
      } else {
        localStorage.removeItem(SAVED_EMAIL_KEY);
      }
      navigate('/');
    } catch (err) {
      setError(err?.response?.data?.message || 'Login failed.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="centered-page auth-layout">
      <div className="auth-shell">
        <section className="auth-panel auth-panel-brand">
          <p className="auth-eyebrow">Invoice Platform</p>
          <h1>Welcome back</h1>
          <p className="auth-description">
            Sign in to manage invoices, monitor payment status, and export professional PDF records.
          </p>
          <ul className="auth-points">
            <li>Track invoices with live status updates</li>
            <li>Create polished documents in minutes</li>
            <li>Keep financial records centralized</li>
          </ul>
        </section>

        <form className="panel auth-card" onSubmit={handleSubmit}>
          <h2>Sign in</h2>
          <p className="auth-subtitle">Use your administrator account to continue.</p>

          <div className="auth-field">
            <label htmlFor="email">Email</label>
            <input
              id="email"
              type="email"
              required
              placeholder="admin@invoicesystem.com"
              value={form.email}
              onChange={(e) => setForm((prev) => ({ ...prev, email: e.target.value }))}
              autoComplete="email"
            />
          </div>

          <div className="auth-field">
            <label htmlFor="password">Password</label>
            <div className="password-wrap">
              <input
                id="password"
                type={showPassword ? 'text' : 'password'}
                required
                placeholder="Enter your password"
                value={form.password}
                onChange={(e) => setForm((prev) => ({ ...prev, password: e.target.value }))}
                autoComplete="current-password"
              />
              <button
                type="button"
                className="password-toggle"
                onClick={() => setShowPassword((prev) => !prev)}
                aria-label={showPassword ? 'Hide password' : 'Show password'}
              >
                {showPassword ? 'Hide' : 'Show'}
              </button>
            </div>
          </div>

          <div className="auth-options">
            <label className="auth-check">
              <input
                type="checkbox"
                checked={saveAccount}
                onChange={(e) => setSaveAccount(e.target.checked)}
              />
              <span>Save account</span>
            </label>
          </div>

          {error ? <p className="error">{error}</p> : null}

          <button className="button auth-submit" type="submit" disabled={loading}>
            {loading ? 'Signing in...' : 'Sign In'}
          </button>
        </form>
      </div>
    </div>
  );
}

export default Login;
