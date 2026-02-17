import { useEffect, useRef, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import api from '../services/api';

export default function ReceiptViewer() {
  const { paymentId } = useParams();
  const iframeRef = useRef(null);

  const [receiptUrl, setReceiptUrl] = useState('');
  const [payment, setPayment] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

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

  if (loading) {
    return <p className="loading-state">Loading receipt preview...</p>;
  }

  if (error) {
    return (
      <div className="stacked">
        <h2>Receipt</h2>
        <p className="error panel">{error}</p>
        <div>
          <Link className="button button-outline" to="/payments">Back to Payments</Link>
        </div>
      </div>
    );
  }

  return (
    <div className="stacked">
      <div className="pdf-viewer-head">
        <h2>Receipt #{payment?.receipt_number || paymentId}</h2>
        <div className="pdf-viewer-actions">
          <button type="button" className="button button-outline" onClick={handlePrint}>
            Print
          </button>
          <a href={receiptUrl} target="_blank" rel="noreferrer" className="button button-outline">
            Open in New Tab
          </a>
          <Link className="button button-outline" to="/payments">Back</Link>
        </div>
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
