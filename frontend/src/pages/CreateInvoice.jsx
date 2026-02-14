import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import InvoiceForm from '../components/InvoiceForm';
import InvoicePreview from '../components/InvoicePreview';
import api from '../services/api';

function CreateInvoice() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(false);
  const [numberLoading, setNumberLoading] = useState(true);
  const [invoiceNumber, setInvoiceNumber] = useState('');
  const [formDraft, setFormDraft] = useState(null);
  const [draftInvoice, setDraftInvoice] = useState(null);
  const [createdInvoice, setCreatedInvoice] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    const loadNextNumber = async () => {
      setNumberLoading(true);
      try {
        const response = await api.get('/invoices/next-number');
        setInvoiceNumber(response.data.invoice_number || '');
      } catch (err) {
        if (err?.response?.status === 401) {
          setError('Your session expired. Please log in again.');
          return;
        }
        setError(err?.response?.data?.message || 'Failed to generate invoice number.');
      } finally {
        setNumberLoading(false);
      }
    };

    loadNextNumber();
  }, []);

  const handlePreview = async (payload) => {
    setFormDraft(payload);
    setDraftInvoice(payload);
  };

  const handleSubmit = async () => {
    if (!draftInvoice) return;

    setLoading(true);
    setError('');

    try {
      const response = await api.post('/invoices', draftInvoice);
      setCreatedInvoice(response.data?.data ?? null);
    } catch (err) {
      if (err?.response?.status === 401) {
        setError('Your session expired. Please log in again.');
        return;
      }
      setError(err?.response?.data?.message || 'Failed to create invoice.');
    } finally {
      setLoading(false);
    }
  };

  if (createdInvoice) {
    const backendBase = import.meta.env.VITE_BACKEND_BASE_URL || 'http://localhost:8000';
    const pdfUrl = createdInvoice.pdf_url || (createdInvoice.pdf_path ? `${backendBase}/${createdInvoice.pdf_path}` : null);

    return (
      <div className="stacked">
        <h2>Create Invoice</h2>
        <div className="panel success-card">
          <h3>Invoice Saved Successfully</h3>
          <p>Invoice <strong>{createdInvoice.invoice_number}</strong> has been created and PDF is ready.</p>
          <div className="success-actions">
            {pdfUrl ? (
              <a href={pdfUrl} className="button" target="_blank" rel="noreferrer">
                Download PDF
              </a>
            ) : null}
            <button type="button" className="button button-outline" onClick={() => navigate('/invoices')}>
              Go to Invoices
            </button>
            <button
              type="button"
              className="button button-outline"
              onClick={() => window.location.reload()}
            >
              Create Another
            </button>
          </div>
        </div>
      </div>
    );
  }

  if (draftInvoice) {
    return (
      <div className="stacked">
        <h2>Review Before Create</h2>
        {error ? <p className="error panel">{error}</p> : null}
        <InvoicePreview invoice={draftInvoice} />
        <div className="filter-actions">
          <button type="button" className="button button-outline" onClick={() => setDraftInvoice(null)}>
            Back to Edit
          </button>
          <button type="button" className="button" onClick={handleSubmit} disabled={loading}>
            {loading ? 'Creating...' : 'Create Invoice'}
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="stacked">
      <h2>Create Invoice</h2>
      {error ? <p className="error panel">{error}</p> : null}
      {numberLoading ? <p>Preparing invoice number...</p> : <InvoiceForm onPreview={handlePreview} loading={loading} invoiceNumber={invoiceNumber} initialData={formDraft} />}
    </div>
  );
}

export default CreateInvoice;
