import { useEffect, useState } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import api from '../services/api';
import { useAuth } from '../context/AuthContext';

function InvoicePdfViewer() {
  const { invoiceId } = useParams();
  const location = useLocation();
  const { user } = useAuth();
  const [pdfUrl, setPdfUrl] = useState('');
  const [invoice, setInvoice] = useState(null);
  const [loading, setLoading] = useState(true);
  const [downloading, setDownloading] = useState(false);
  const [error, setError] = useState('');
  const ownerBackPath = location?.state?.backTo || '/owner/operations/invoices';
  const backPath = user?.role === 'owner' ? ownerBackPath : '/invoices';

  useEffect(() => {
    let objectUrl = '';

    const load = async () => {
      setLoading(true);
      setError('');

      try {
        const [invoiceResponse, pdfResponse] = await Promise.all([
          api.get(`/invoices/${invoiceId}`),
          api.get(`/invoices/${invoiceId}/pdf`, { responseType: 'blob' }),
        ]);

        setInvoice(invoiceResponse.data);
        objectUrl = window.URL.createObjectURL(new Blob([pdfResponse.data], { type: 'application/pdf' }));
        setPdfUrl(objectUrl);
      } catch (err) {
        setError(err?.response?.data?.message || 'Unable to load the PDF preview.');
      } finally {
        setLoading(false);
      }
    };

    load();

    return () => {
      if (objectUrl) {
        window.URL.revokeObjectURL(objectUrl);
      }
    };
  }, [invoiceId]);

  const handleDownload = async () => {
    setDownloading(true);
    try {
      const response = await api.get(`/invoices/${invoiceId}/pdf`, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
      const filename = invoice?.invoice_number ? `invoice-${invoice.invoice_number}.pdf` : `invoice-${invoiceId}.pdf`;
      const link = document.createElement('a');
      link.href = url;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.setTimeout(() => window.URL.revokeObjectURL(url), 60000);
    } finally {
      setDownloading(false);
    }
  };

  if (loading) {
    return <p>Loading PDF preview...</p>;
  }

  if (error) {
    return (
      <div className="stacked">
        <h2>Invoice PDF</h2>
        <p className="error panel">{error}</p>
        <div>
          <Link className="button button-outline" to={backPath}>
            {user?.role === 'owner' ? 'Back to Admin Activity' : 'Back to Invoices'}
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="stacked">
      <div className="pdf-viewer-head">
        <h2>Invoice PDF</h2>
        <div className="pdf-viewer-actions">
          <button type="button" className="button button-outline" onClick={handleDownload} disabled={downloading}>
            {downloading ? 'Downloading...' : 'Download PDF'}
          </button>
          <Link className="button button-outline" to={backPath}>Back</Link>
        </div>
      </div>

      <div className="panel pdf-viewer-panel">
        <iframe title="Invoice PDF Preview" src={pdfUrl} className="pdf-viewer-frame" />
      </div>
    </div>
  );
}

export default InvoicePdfViewer;
