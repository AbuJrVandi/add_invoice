import { useEffect, useRef, useState } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import api from '../services/api';
import { useAuth } from '../context/AuthContext';

export default function ReceiptViewer() {
  const { paymentId } = useParams();
  const location = useLocation();
  const { user } = useAuth();
  const iframeRef = useRef(null);

  const [receiptUrl, setReceiptUrl] = useState('');
  const [receiptPdfUrl, setReceiptPdfUrl] = useState('');
  const [payment, setPayment] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [downloading, setDownloading] = useState(false);
  const [downloadError, setDownloadError] = useState('');
  const ownerBackPath = location?.state?.backTo || '/owner/operations/payments';
  const backPath = user?.role === 'owner' ? ownerBackPath : '/payments';

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      setError('');

      try {
        const response = await api.get(`/payments/${paymentId}`);
        const payload = response.data;

        setPayment(payload);

        if (!payload?.receipt_url) {
          setError('Receipt URL is not available for this payment.');
          return;
        }

        setReceiptUrl(payload.receipt_url);
        setReceiptPdfUrl(payload.receipt_pdf_url || '');
      } catch (err) {
        setError(err?.response?.data?.message || 'Unable to load receipt preview.');
      } finally {
        setLoading(false);
      }
    };

    load();
  }, [paymentId]);

  const handlePrint = () => {
    iframeRef.current?.contentWindow?.print();
  };

  const getFilenameFromDisposition = (contentDisposition, fallback) => {
    if (!contentDisposition) {
      return fallback;
    }

    const utfMatch = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (utfMatch?.[1]) {
      return decodeURIComponent(utfMatch[1]).trim();
    }

    const asciiMatch = contentDisposition.match(/filename="?([^"]+)"?/i);
    if (asciiMatch?.[1]) {
      return asciiMatch[1].trim();
    }

    return fallback;
  };

  const triggerBlobDownload = (blob, filename) => {
    const objectUrl = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(objectUrl);
  };

  const handleDownload = async () => {
    setDownloading(true);
    setDownloadError('');

    const fallbackName = `receipt-${payment?.receipt_number || paymentId}.pdf`;

    try {
      const response = await api.get(`/payments/${paymentId}/receipt/download`, {
        responseType: 'blob',
      });

      const disposition = response?.headers?.['content-disposition'] || '';
      const filename = getFilenameFromDisposition(disposition, fallbackName);
      triggerBlobDownload(response.data, filename);
      return;
    } catch (downloadErr) {
      if (receiptPdfUrl) {
        const fallbackLink = document.createElement('a');
        fallbackLink.href = receiptPdfUrl;
        fallbackLink.target = '_blank';
        fallbackLink.rel = 'noreferrer';
        fallbackLink.download = fallbackName;
        document.body.appendChild(fallbackLink);
        fallbackLink.click();
        fallbackLink.remove();
      } else {
        setDownloadError(downloadErr?.response?.data?.message || 'Unable to download receipt right now.');
      }
    } finally {
      setDownloading(false);
    }
  };

  if (loading) {
    return <p className="loading-state">Loading receipt preview...</p>;
  }

  if (error) {
    return (
      <div className="stacked">
        <h2>Receipt</h2>
        <p className="error panel">{error}</p>
        <div>
          <Link className="button button-outline" to={backPath}>
            {user?.role === 'owner' ? 'Back to Admin Activity' : 'Back to Payments'}
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="stacked">
      <div className="pdf-viewer-head">
        <h2>Receipt #{payment?.receipt_number || paymentId}</h2>
        <div className="pdf-viewer-actions">
          <button type="button" className="button" onClick={handleDownload} disabled={downloading}>
            {downloading ? 'Downloading...' : 'Download PDF'}
          </button>
          <button type="button" className="button button-outline" onClick={handlePrint}>
            Print
          </button>
          <a href={receiptUrl} target="_blank" rel="noreferrer" className="button button-outline">
            Open in New Tab
          </a>
          <Link className="button button-outline" to={backPath}>Back</Link>
        </div>
        {downloadError ? <p className="error">{downloadError}</p> : null}
      </div>

      <div className="panel pdf-viewer-panel">
        <iframe
          ref={iframeRef}
          title="Receipt Preview"
          src={receiptUrl}
          className="pdf-viewer-frame"
        />
      </div>
    </div>
  );
}
