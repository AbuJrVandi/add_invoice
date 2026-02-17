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
      setError(err?.response?.data?.message || 'Login failed. Please check your credentials.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="centered-page auth-layout">
      <div className="auth-shell">
        {/* Left Brand Panel */}
        <section className="auth-panel auth-panel-brand">
          <div className="auth-brand-content">
            <img
              src="/set.png"
              alt="CIRQON Electronics"
              className="auth-logo"
            />
            <p className="auth-eyebrow">Electronics Sales & Service</p>
            <h1>CIRQON Electronics</h1>
            <p className="auth-description">
              Professional invoice management system for electronics sales, service tracking, and financial operations.
            </p>
            <ul className="auth-points">
              <li>Create & manage electronic invoices</li>
              <li>Track payments with live status updates</li>
              <li>Generate professional PDF documents</li>
              <li>Keep financial records centralized</li>
            </ul>
          </div>
        </section>

        {/* Right Login Card */}
        <form className="auth-panel auth-card" onSubmit={handleSubmit} id="login-form">
          <img
            src="/set.png"
            alt="CIRQON"
            className="auth-card-logo"
          />
          <h2>Welcome back</h2>
          <p className="auth-subtitle">Sign in to your administrator account to continue.</p>

          <div className="auth-field">
            <label htmlFor="email">Email Address</label>
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
              <span>Remember me</span>
            </label>
            <a href="#" className="auth-forgot" onClick={(e) => e.preventDefault()}>Forgot password?</a>
          </div>

          {error ? <p className="error">{error}</p> : null}

          <button className="button auth-submit" type="submit" id="login-submit" disabled={loading}>
            {loading ? (
              <>
                <span className="spinner" style={{
                  display: 'inline-block',
                  width: '16px',
                  height: '16px',
                  border: '2px solid rgba(255,255,255,0.3)',
                  borderTopColor: '#fff',
                  borderRadius: '50%',
                  animation: 'spin 0.6s linear infinite',
                }} />
                Signing in...
              </>
            ) : 'Sign In'}
          </button>
        </form>
      </div>
    </div>
  );
}

export default Login;
