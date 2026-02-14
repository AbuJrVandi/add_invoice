import { useEffect, useMemo, useState } from 'react';
import api from '../services/api';

function PdfSettings() {
  const backendBase = import.meta.env.VITE_BACKEND_BASE_URL || 'http://localhost:8000';
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [form, setForm] = useState({
    company_name: 'East Repair Inc.',
    issuer_name: 'Administrator',
    logo: null,
    signature: null,
  });
  const [current, setCurrent] = useState({ logo_url: null, signature_url: null });

  const logoPreview = useMemo(() => (form.logo ? URL.createObjectURL(form.logo) : null), [form.logo]);
  const signaturePreview = useMemo(() => (form.signature ? URL.createObjectURL(form.signature) : null), [form.signature]);

  useEffect(() => {
    const loadSettings = async () => {
      setLoading(true);
      setError('');

      try {
        const response = await api.get('/pdf-settings');
        const data = response.data;

        setForm((prev) => ({
          ...prev,
          company_name: data.company_name || 'East Repair Inc.',
          issuer_name: data.issuer_name || 'Administrator',
        }));

        setCurrent({
          logo_url: data.logo_url ? `${backendBase}${data.logo_url}` : null,
          signature_url: data.signature_url ? `${backendBase}${data.signature_url}` : null,
        });
      } catch (err) {
        setError(err?.response?.data?.message || 'Failed to load PDF settings.');
      } finally {
        setLoading(false);
      }
    };

    loadSettings();
  }, []);

  useEffect(() => {
    return () => {
      if (logoPreview) URL.revokeObjectURL(logoPreview);
      if (signaturePreview) URL.revokeObjectURL(signaturePreview);
    };
  }, [logoPreview, signaturePreview]);

  const saveSettings = async (event) => {
    event.preventDefault();

    setSaving(true);
    setError('');
    setSuccess('');

    try {
      const payload = new FormData();
      payload.append('company_name', form.company_name);
      payload.append('issuer_name', form.issuer_name);
      if (form.logo) payload.append('logo', form.logo);
      if (form.signature) payload.append('signature', form.signature);

      const response = await api.post('/pdf-settings', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      const saved = response.data?.data;

      setCurrent({
        logo_url: saved?.logo_url ? `${backendBase}${saved.logo_url}` : null,
        signature_url: saved?.signature_url ? `${backendBase}${saved.signature_url}` : null,
      });

      setForm((prev) => ({
        ...prev,
        logo: null,
        signature: null,
      }));

      setSuccess('PDF settings saved. New invoices will use this updated template.');
    } catch (err) {
      setError(err?.response?.data?.message || 'Failed to save PDF settings.');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return <p>Loading PDF settings...</p>;
  }

  return (
    <div className="stacked">
      <h2>PDF Settings</h2>
      {error ? <p className="error panel">{error}</p> : null}
      {success ? <p className="success-text panel">{success}</p> : null}

      <form className="panel stacked" onSubmit={saveSettings}>
        <div className="grid-2">
          <div>
            <label>Company Name</label>
            <input
              value={form.company_name}
              onChange={(e) => setForm((prev) => ({ ...prev, company_name: e.target.value }))}
              placeholder="Company name"
              required
            />
          </div>

          <div>
            <label>Invoice Issuer Name</label>
            <input
              value={form.issuer_name}
              onChange={(e) => setForm((prev) => ({ ...prev, issuer_name: e.target.value }))}
              placeholder="Name of person issuing invoice"
              required
            />
          </div>
        </div>

        <div className="settings-grid">
          <div className="upload-card">
            <h3>Company Logo</h3>
            <p className="muted">Recommended PNG/JPG, transparent background, under 3MB.</p>
            <input
              type="file"
              accept="image/png,image/jpeg,image/webp"
              onChange={(e) => setForm((prev) => ({ ...prev, logo: e.target.files?.[0] || null }))}
            />
            {(logoPreview || current.logo_url) ? (
              <img className="preview-img" src={logoPreview || current.logo_url} alt="Logo Preview" />
            ) : (
              <div className="preview-placeholder">No logo uploaded</div>
            )}
          </div>

          <div className="upload-card">
            <h3>Signature</h3>
            <p className="muted">Upload issuer signature image used in PDF footer.</p>
            <input
              type="file"
              accept="image/png,image/jpeg,image/webp"
              onChange={(e) => setForm((prev) => ({ ...prev, signature: e.target.files?.[0] || null }))}
            />
            {(signaturePreview || current.signature_url) ? (
              <img className="preview-img" src={signaturePreview || current.signature_url} alt="Signature Preview" />
            ) : (
              <div className="preview-placeholder">No signature uploaded</div>
            )}
          </div>
        </div>

        <div>
          <button className="button" type="submit" disabled={saving}>
            {saving ? 'Saving...' : 'Save PDF Settings'}
          </button>
        </div>
      </form>
    </div>
  );
}

export default PdfSettings;
