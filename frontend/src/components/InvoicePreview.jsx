import { useRef } from 'react';

function InvoicePreview({ pdfUrl, loading }) {
  const frameRef = useRef(null);

  const handlePrintPreview = () => {
    frameRef.current?.contentWindow?.print();
  };

  if (loading) {
    return (
      <div className="panel">
        <div className="loading">Preparing exact print preview...</div>
      </div>
    );
  }

  if (!pdfUrl) {
    return (
      <div className="panel">
        <div className="empty-state">Preview is not available yet.</div>
      </div>
    );
  }

  return (
    <div className="stacked">
      <div className="pdf-viewer-head">
        <h2>Invoice Print Preview</h2>
        <div className="pdf-viewer-actions">
          <button type="button" className="button button-outline" onClick={handlePrintPreview}>
            Print Preview
          </button>
        </div>
      </div>

      <div className="panel pdf-viewer-panel">
        <iframe
          ref={frameRef}
          title="Draft Invoice Print Preview"
          src={pdfUrl}
          className="pdf-viewer-frame"
        />
      </div>
    </div>
  );
}

export default InvoicePreview;
