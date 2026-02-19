import { useCallback, useEffect, useMemo, useState } from 'react';
import api from '../services/api';
import useResponsive from '../hooks/useResponsive';

const emptyForm = {
  name: '',
  email: '',
  password: '',
};

function formatDateTime(value) {
  if (!value) {
    return '-';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return '-';
  }

  return date.toLocaleString();
}

function buildPassword() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
  const length = 12;
  let out = '';
  for (let i = 0; i < length; i += 1) {
    out += chars[Math.floor(Math.random() * chars.length)];
  }
  return out;
}

function extractErrorMessage(requestError, fallback) {
  const validationErrors = requestError?.response?.data?.errors;
  if (validationErrors && typeof validationErrors === 'object') {
    const first = Object.values(validationErrors).flat()?.[0];
    if (typeof first === 'string' && first.trim() !== '') {
      return first;
    }
  }

  return requestError?.response?.data?.message || fallback;
}

export default function OwnerAdminCredentials() {
  const { isMobile } = useResponsive();
  const [form, setForm] = useState(emptyForm);
  const [admins, setAdmins] = useState([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [updatingUserId, setUpdatingUserId] = useState(null);
  const [editingUserId, setEditingUserId] = useState(null);
  const [togglingUserId, setTogglingUserId] = useState(null);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const fetchAdmins = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const response = await api.get('/owner/admin-credentials');
      const rows = Array.isArray(response.data?.data) ? response.data.data : [];
      setAdmins(rows);
    } catch (requestError) {
      setError(requestError?.response?.data?.message || 'Unable to load admin credentials.');
      setAdmins([]);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchAdmins();
  }, [fetchAdmins]);

  const createAdmin = async (event) => {
    event.preventDefault();
    setSaving(true);
    setError('');
    setSuccess('');

    try {
      const response = await api.post('/owner/admin-credentials', {
        name: form.name.trim(),
        email: form.email.trim(),
        password: form.password,
      });

      setSuccess(response.data?.message || 'Admin credentials created.');
      setForm(emptyForm);
      await fetchAdmins();
    } catch (requestError) {
      setError(extractErrorMessage(requestError, 'Failed to create admin credentials.'));
    } finally {
      setSaving(false);
    }
  };

  const changePassword = async (admin) => {
    const suggestedPassword = buildPassword();
    const newPassword = window.prompt(`Set new password for ${admin.name}`, suggestedPassword);

    if (!newPassword) {
      return;
    }

    setUpdatingUserId(admin.id);
    setError('');
    setSuccess('');

    try {
      const response = await api.put(`/owner/admin-credentials/${admin.id}/password`, {
        password: newPassword,
      });

      setSuccess(`${response.data?.message || 'Password changed.'} New password: ${newPassword}`);
      await fetchAdmins();
    } catch (requestError) {
      setError(extractErrorMessage(requestError, 'Unable to change password.'));
    } finally {
      setUpdatingUserId(null);
    }
  };

  const editAdmin = async (admin) => {
    const nextName = window.prompt('Edit full name', admin.name || '');
    if (nextName === null) {
      return;
    }

    const nextEmail = window.prompt('Edit email address', admin.email || '');
    if (nextEmail === null) {
      return;
    }

    setEditingUserId(admin.id);
    setError('');
    setSuccess('');

    try {
      const response = await api.put(`/owner/admin-credentials/${admin.id}`, {
        name: nextName.trim(),
        email: nextEmail.trim(),
      });

      setSuccess(response.data?.message || 'Admin account updated.');
      await fetchAdmins();
    } catch (requestError) {
      setError(extractErrorMessage(requestError, 'Unable to edit admin account.'));
    } finally {
      setEditingUserId(null);
    }
  };

  const toggleAdminStatus = async (admin) => {
    const nextStatus = !Boolean(admin.is_active);
    const confirmed = window.confirm(
      nextStatus
        ? `Activate ${admin.name}'s account?`
        : `Deactivate ${admin.name}'s account? They will not be able to login.`
    );

    if (!confirmed) {
      return;
    }

    setTogglingUserId(admin.id);
    setError('');
    setSuccess('');

    try {
      const response = await api.patch(`/owner/admin-credentials/${admin.id}/status`, {
        is_active: nextStatus,
      });

      setSuccess(response.data?.message || 'Admin account status updated.');
      await fetchAdmins();
    } catch (requestError) {
      setError(extractErrorMessage(requestError, 'Unable to update admin status.'));
    } finally {
      setTogglingUserId(null);
    }
  };

  const credentialCount = useMemo(() => admins.length, [admins]);

  return (
    <div className="owner-credentials-page stacked">
      <section className="panel owner-credentials-header">
        <div>
          <h2>Admin Credential Management</h2>
          <p>Create admin login accounts and control password access centrally as owner.</p>
        </div>
        <div className="owner-credentials-count">
          <span>Total Admin Accounts</span>
          <strong>{credentialCount}</strong>
        </div>
      </section>

      {error ? <p className="error">{error}</p> : null}
      {success ? <p className="success-text panel">{success}</p> : null}

      <form className="panel owner-credentials-form" onSubmit={createAdmin}>
        <h3>Create Admin Credentials</h3>
        <div className="owner-credentials-form-grid">
          <div>
            <label>Full Name</label>
            <input
              required
              placeholder="e.g. Abu Junior Vandi"
              value={form.name}
              onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))}
            />
          </div>
          <div>
            <label>Email</label>
            <input
              required
              type="email"
              placeholder="admin@company.com"
              value={form.email}
              onChange={(event) => setForm((prev) => ({ ...prev, email: event.target.value }))}
            />
          </div>
          <div>
            <label>Password</label>
            <input
              required
              minLength={8}
              type="text"
              placeholder="Minimum 8 characters"
              value={form.password}
              onChange={(event) => setForm((prev) => ({ ...prev, password: event.target.value }))}
            />
          </div>
        </div>
        <div className="owner-credentials-form-actions">
          <button type="submit" className="button" disabled={saving}>
            {saving ? 'Creating...' : 'Create Credentials'}
          </button>
        </div>
      </form>

      <section className="panel">
        <div className="panel-header">
          <h3>Admin Credentials</h3>
          <button type="button" className="button button-outline" onClick={fetchAdmins}>
            Refresh
          </button>
        </div>

        {loading ? (
          <div className="loading">Loading admin credentials...</div>
        ) : isMobile ? (
          <div className="owner-credentials-mobile-list">
            {admins.length === 0 ? (
              <div className="empty-state">No admin credentials created yet.</div>
            ) : (
              admins.map((admin) => (
                <article className="owner-credentials-mobile-card" key={admin.id}>
                  <div className="owner-credentials-row">
                    <span>Name</span>
                    <strong>{admin.name}</strong>
                  </div>
                  <div className="owner-credentials-row">
                    <span>Email</span>
                    <strong>{admin.email}</strong>
                  </div>
                  <div className="owner-credentials-row">
                    <span>Password</span>
                    <strong className="owner-password-value">{admin.password || 'Not available'}</strong>
                  </div>
                  <div className="owner-credentials-row">
                    <span>Status</span>
                    <strong className={admin.is_active ? 'owner-status-pill active' : 'owner-status-pill inactive'}>
                      {admin.is_active ? 'Active' : 'Deactivated'}
                    </strong>
                  </div>
                  <div className="owner-credentials-row">
                    <span>Changed</span>
                    <strong>{formatDateTime(admin.owner_password_changed_at)}</strong>
                  </div>
                  <div className="owner-credentials-actions">
                    <button
                      type="button"
                      className="button btn-sm button-outline"
                      onClick={() => editAdmin(admin)}
                      disabled={editingUserId === admin.id}
                    >
                      {editingUserId === admin.id ? 'Saving...' : 'Edit'}
                    </button>
                    <button
                      type="button"
                      className={`button btn-sm ${admin.is_active ? 'button-danger' : 'button-outline'}`}
                      onClick={() => toggleAdminStatus(admin)}
                      disabled={togglingUserId === admin.id}
                    >
                      {togglingUserId === admin.id
                        ? 'Updating...'
                        : admin.is_active
                          ? 'Deactivate'
                          : 'Activate'}
                    </button>
                    <button
                      type="button"
                      className="button btn-sm"
                      onClick={() => changePassword(admin)}
                      disabled={updatingUserId === admin.id}
                    >
                      {updatingUserId === admin.id ? 'Updating...' : 'Change Password'}
                    </button>
                  </div>
                </article>
              ))
            )}
          </div>
        ) : (
          <div className="table-wrap">
            <table className="table owner-credentials-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>Password</th>
                  <th>Changed By Owner</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {admins.length === 0 ? (
                  <tr>
                    <td colSpan="6" className="empty-state">No admin credentials created yet.</td>
                  </tr>
                ) : (
                  admins.map((admin) => (
                    <tr key={admin.id}>
                      <td>{admin.name}</td>
                      <td>{admin.email}</td>
                      <td>
                        <span className={admin.is_active ? 'owner-status-pill active' : 'owner-status-pill inactive'}>
                          {admin.is_active ? 'Active' : 'Deactivated'}
                        </span>
                      </td>
                      <td>
                        <code className="owner-password-value">{admin.password || 'Not available'}</code>
                      </td>
                      <td>{formatDateTime(admin.owner_password_changed_at)}</td>
                      <td>
                        <button
                          type="button"
                          className="button btn-sm button-outline"
                          onClick={() => editAdmin(admin)}
                          disabled={editingUserId === admin.id}
                        >
                          {editingUserId === admin.id ? 'Saving...' : 'Edit'}
                        </button>
                        {' '}
                        <button
                          type="button"
                          className={`button btn-sm ${admin.is_active ? 'button-danger' : 'button-outline'}`}
                          onClick={() => toggleAdminStatus(admin)}
                          disabled={togglingUserId === admin.id}
                        >
                          {togglingUserId === admin.id
                            ? 'Updating...'
                            : admin.is_active
                              ? 'Deactivate'
                              : 'Activate'}
                        </button>
                        {' '}
                        <button
                          type="button"
                          className="button btn-sm"
                          onClick={() => changePassword(admin)}
                          disabled={updatingUserId === admin.id}
                        >
                          {updatingUserId === admin.id ? 'Updating...' : 'Change Password'}
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </div>
  );
}
