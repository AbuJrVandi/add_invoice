import { useEffect, useMemo, useState } from 'react';
import api from '../services/api';

function PdfSettings() {
  const backendBase = import.meta.env.VITE_BACKEND_BASE_URL || 'http://localhost:8000';
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [form, setForm] = useState({
    company_name: 'CIRQON Electronics',
    company_address: [
      'No. 4 Light-Foot Boston Street',
      'Via Radwon Street, Freetown',
    ].join('\n'),
    company_phone: '+232 74 141141 | +232 79 576950',
    company_email: 'info@cirqon.example',
    company_website: 'www.cirqon.example',
    tax_id: '',
    currency_code: 'SLL',
    contact_person: 'Accounts Team',
    issuer_name: 'Administrator',
    payment_instructions: [
      'Please make payment to:',
      'Bank: EcoBank',
      'Account Name: CirQon Limited Services SL LTD',
      'Account No: 5401-1003-000922-9',
      'IBAN: 010401100300092257',
      'BIC/SWIFT CODE: UNAFSLFR',
    ].join('\n'),
    terms_conditions: [
      'Payment is due within 15 days from invoice date.',
      'Goods once delivered are not returnable.',
      'A late fee may apply on overdue balances.',
      'This invoice was generated electronically and is valid without a physical stamp.',
    ].join('\n'),
    logo: null,
    signature: null,
    stamp: null,
  });
  const [current, setCurrent] = useState({ logo_url: null, signature_url: null, stamp_url: null });

  const logoPreview = useMemo(() => (form.logo ? URL.createObjectURL(form.logo) : null), [form.logo]);
  const signaturePreview = useMemo(() => (form.signature ? URL.createObjectURL(form.signature) : null), [form.signature]);
  const stampPreview = useMemo(() => (form.stamp ? URL.createObjectURL(form.stamp) : null), [form.stamp]);

  useEffect(() => {
    const loadSettings = async () => {
      setLoading(true);
      setError('');

      try {
        const response = await api.get('/pdf-settings');
        const data = response.data;

        setForm((prev) => ({
          ...prev,
          company_name: data.company_name || 'CIRQON Electronics',
          company_address: data.company_address || prev.company_address,
          company_phone: data.company_phone || prev.company_phone,
          company_email: data.company_email || prev.company_email,
          company_website: data.company_website || prev.company_website,
          tax_id: data.tax_id || '',
          currency_code: data.currency_code || 'SLL',
          contact_person: data.contact_person || prev.contact_person,
          issuer_name: data.issuer_name || 'Administrator',
          payment_instructions: data.payment_instructions || prev.payment_instructions,
          terms_conditions: data.terms_conditions || prev.terms_conditions,
        }));

        setCurrent({
          logo_url: data.logo_url ? `${backendBase}${data.logo_url}` : null,
          signature_url: data.signature_url ? `${backendBase}${data.signature_url}` : null,
          stamp_url: data.stamp_url ? `${backendBase}${data.stamp_url}` : null,
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
      if (stampPreview) URL.revokeObjectURL(stampPreview);
    };
  }, [logoPreview, signaturePreview, stampPreview]);

  const saveSettings = async (event) => {
    event.preventDefault();

    setSaving(true);
    setError('');
    setSuccess('');

    try {
      const payload = new FormData();
      payload.append('company_name', form.company_name);
      payload.append('company_address', form.company_address);
      payload.append('company_phone', form.company_phone);
      payload.append('company_email', form.company_email);
      payload.append('company_website', form.company_website);
      payload.append('tax_id', form.tax_id);
      payload.append('currency_code', form.currency_code);
      payload.append('contact_person', form.contact_person);
      payload.append('issuer_name', form.issuer_name);
      payload.append('payment_instructions', form.payment_instructions);
      payload.append('terms_conditions', form.terms_conditions || '');
      if (form.logo) payload.append('logo', form.logo);
      if (form.signature) payload.append('signature', form.signature);
      if (form.stamp) payload.append('stamp', form.stamp);

      const response = await api.post('/pdf-settings', payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      const saved = response.data?.data;

      setCurrent({
        logo_url: saved?.logo_url ? `${backendBase}${saved.logo_url}` : null,
        signature_url: saved?.signature_url ? `${backendBase}${saved.signature_url}` : null,
        stamp_url: saved?.stamp_url ? `${backendBase}${saved.stamp_url}` : null,
      });

      setForm((prev) => ({
        ...prev,
        logo: null,
        signature: null,
        stamp: null,
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
          <div>
            <label>Contact Person</label>
            <input
              value={form.contact_person}
              onChange={(e) => setForm((prev) => ({ ...prev, contact_person: e.target.value }))}
              placeholder="Accounts Contact"
            />
          </div>
          <div>
            <label>Currency Code</label>
            <input
              value={form.currency_code}
              onChange={(e) => setForm((prev) => ({ ...prev, currency_code: e.target.value.toUpperCase() }))}
              placeholder="SLL"
              maxLength={10}
            />
          </div>
          <div>
            <label>Company Phone</label>
            <input
              value={form.company_phone}
              onChange={(e) => setForm((prev) => ({ ...prev, company_phone: e.target.value }))}
              placeholder="Phone numbers shown on invoice"
            />
          </div>
          <div>
            <label>Company Email</label>
            <input
              type="email"
              value={form.company_email}
              onChange={(e) => setForm((prev) => ({ ...prev, company_email: e.target.value }))}
              placeholder="info@company.com"
            />
          </div>
          <div>
            <label>Company Website</label>
            <input
              value={form.company_website}
              onChange={(e) => setForm((prev) => ({ ...prev, company_website: e.target.value }))}
              placeholder="www.company.com"
            />
          </div>
          <div>
            <label>Tax ID / TIN</label>
            <input
              value={form.tax_id}
              onChange={(e) => setForm((prev) => ({ ...prev, tax_id: e.target.value }))}
              placeholder="Tax registration number"
            />
          </div>
        </div>

        <div>
          <label>Company Address (Multi-line)</label>
          <textarea
            value={form.company_address}
            onChange={(e) => setForm((prev) => ({ ...prev, company_address: e.target.value }))}
            rows={3}
            placeholder="Street, City, Country"
          />
        </div>

        <div>
          <label>Payment Instructions</label>
          <p className="muted">These details will appear in the invoice printout payment instructions section.</p>
          <textarea
            value={form.payment_instructions}
            onChange={(e) => setForm((prev) => ({ ...prev, payment_instructions: e.target.value }))}
            placeholder="Enter bank or payment instructions shown on invoices"
            rows={7}
          />
        </div>

        <div>
          <label>Terms & Conditions</label>
          <p className="muted">One term per line. These terms appear in the lower left section of the invoice PDF.</p>
          <textarea
            value={form.terms_conditions}
            onChange={(e) => setForm((prev) => ({ ...prev, terms_conditions: e.target.value }))}
            rows={6}
            placeholder="Payment terms and policies"
          />
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

          <div className="upload-card">
            <h3>Stamp</h3>
            <p className="muted">Upload company stamp image shown below supplier signature.</p>
            <input
              type="file"
              accept="image/png,image/jpeg,image/webp"
              onChange={(e) => setForm((prev) => ({ ...prev, stamp: e.target.files?.[0] || null }))}
            />
            {(stampPreview || current.stamp_url) ? (
              <img className="preview-img" src={stampPreview || current.stamp_url} alt="Stamp Preview" />
            ) : (
              <div className="preview-placeholder">No stamp uploaded</div>
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
