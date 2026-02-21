import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import InvoiceForm from '../components/InvoiceForm';
import InvoicePreview from '../components/InvoicePreview';
import api from '../services/api';

function CreateInvoice({ mode = 'admin' }) {
  const navigate = useNavigate();
  const isOwnerMode = mode === 'owner';
  const listPath = isOwnerMode ? '/owner/operations/invoices' : '/invoices';
  const pageTitle = isOwnerMode ? 'Owner Invoice Studio' : 'Create Invoice';
  const [searchParams] = useSearchParams();
  const cloneId = searchParams.get('clone');
  const [loading, setLoading] = useState(false);
  const [numberLoading, setNumberLoading] = useState(true);
  const [previewLoading, setPreviewLoading] = useState(false);
  const [invoiceNumber, setInvoiceNumber] = useState('');
  const [formDraft, setFormDraft] = useState(null);
  const [draftInvoice, setDraftInvoice] = useState(null);
  const [previewPdfUrl, setPreviewPdfUrl] = useState('');
  const [createdInvoice, setCreatedInvoice] = useState(null);
  const [pdfLoading, setPdfLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    const loadInitial = async () => {
      setNumberLoading(true);
      try {
        const numberResponse = await api.get('/invoices/next-number');
        setInvoiceNumber(numberResponse.data.invoice_number || '');

        if (cloneId) {
          const cloneResponse = await api.get(`/invoices/${cloneId}`);
          const source = cloneResponse.data;

          if (source) {
            setFormDraft({
              customer_name: source.customer_name || '',
              organization: source.organization || '',
              bill_to: source.bill_to || '',
              ship_to: source.ship_to || '',
              invoice_date: source.invoice_date || '',
              due_date: source.due_date || '',
              po_number: source.po_number || '',
              tax: Number(source.tax || 0),
              items: Array.isArray(source.items) ? source.items.map((item) => ({
                description: item.description || '',
                quantity: Number(item.quantity || 1),
                unit_price: Number(item.unit_price || 0),
                amount: Number(item.amount || 0),
              })) : [],
            });
          }
        }
      } catch (err) {
        if (err?.response?.status === 401) {
          setError('Your session expired. Please log in again.');
          return;
        }
        setError(err?.response?.data?.message || 'Failed to prepare invoice form.');
      } finally {
        setNumberLoading(false);
      }
    };

    loadInitial();
  }, [cloneId]);

  useEffect(() => {
    return () => {
      if (previewPdfUrl) {
        window.URL.revokeObjectURL(previewPdfUrl);
      }
    };
  }, [previewPdfUrl]);

  const buildPreviewPdf = async (payload) => {
    const response = await api.post('/invoices/preview-pdf', payload, { responseType: 'blob' });
    return window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
  };

  const handlePreview = async (payload) => {
    setFormDraft(payload);
    setError('');
    setPreviewLoading(true);

    try {
      const nextUrl = await buildPreviewPdf(payload);

      setDraftInvoice(payload);
      setPreviewPdfUrl((previousUrl) => {
        if (previousUrl) {
          window.URL.revokeObjectURL(previousUrl);
        }
        return nextUrl;
      });
    } catch (err) {
      if (err?.response?.status === 401) {
        setError('Your session expired. Please log in again.');
      } else {
        setError(err?.response?.data?.message || 'Failed to generate invoice preview.');
      }
    } finally {
      setPreviewLoading(false);
    }
  };

  const handleSubmit = async () => {
    if (!draftInvoice) return;

    setLoading(true);
    setError('');

    try {
      const response = await api.post('/invoices', draftInvoice);
      const created = response.data?.data ?? null;
      setCreatedInvoice(created);
      if (created?.id) {
        await handleDownloadPdf(created.id, created.invoice_number);
      }
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

  const fetchPdfBlobUrl = async (invoiceId) => {
    const response = await api.get(`/invoices/${invoiceId}/pdf`, { responseType: 'blob' });
    return window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
  };

  const handleDownloadPdf = async (invoiceId, invoiceNumberToDownload) => {
    setPdfLoading(true);
    try {
      const url = await fetchPdfBlobUrl(invoiceId);
      const link = document.createElement('a');
      link.href = url;
      link.download = `invoice-${invoiceNumberToDownload || invoiceId}.pdf`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.setTimeout(() => window.URL.revokeObjectURL(url), 60000);
    } catch (err) {
      setError(err?.response?.data?.message || 'Unable to download invoice PDF right now.');
    } finally {
      setPdfLoading(false);
    }
  };

  const handleDownloadCreatedPdf = async () => {
    if (!createdInvoice?.id) return;

    await handleDownloadPdf(createdInvoice.id, createdInvoice.invoice_number);
  };

  const handleBackToEdit = () => {
    setDraftInvoice(null);
  };

  if (createdInvoice) {
    return (
      <div className="stacked">
        <h2>{pageTitle}</h2>
        {error ? <p className="error panel">{error}</p> : null}
        <div className="panel success-card">
          <h3>Invoice Saved Successfully</h3>
          <p>Invoice <strong>{createdInvoice.invoice_number}</strong> has been created and PDF has been downloaded.</p>
          <div className="success-actions">
            <button type="button" className="button" onClick={() => navigate(`/invoices/${createdInvoice.id}/view`)}>
              View PDF
            </button>
            <button type="button" className="button button-outline" onClick={handleDownloadCreatedPdf} disabled={pdfLoading}>
              {pdfLoading ? 'Preparing PDF...' : 'Download Again'}
            </button>
            <button type="button" className="button button-outline" onClick={() => navigate(listPath)}>
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
        <p className="muted">This preview is generated from the same PDF template used for final printing.</p>
        <InvoicePreview pdfUrl={previewPdfUrl} loading={previewLoading} />
        <div className="filter-actions">
          <button type="button" className="button button-outline" onClick={handleBackToEdit}>
            Back to Edit
          </button>
          <button type="button" className="button" onClick={handleSubmit} disabled={loading || previewLoading}>
            {loading ? 'Creating...' : 'Create Invoice'}
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="stacked">
      <h2>{pageTitle}</h2>
      {error ? <p className="error panel">{error}</p> : null}
      {numberLoading ? (
        <p>Preparing invoice number...</p>
      ) : (
        <InvoiceForm
          onPreview={handlePreview}
          loading={loading || previewLoading}
          invoiceNumber={invoiceNumber}
          initialData={formDraft}
        />
      )}
    </div>
  );
}

export default CreateInvoice;
