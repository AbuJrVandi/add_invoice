import { useState } from 'react';
import api from '../services/api';

function InvoiceTable({ invoices }) {
  const [openingPdfId, setOpeningPdfId] = useState(null);

  const handleOpenPdf = async (invoice) => {
    setOpeningPdfId(invoice.id);
    try {
      const response = await api.get(`/invoices/${invoice.id}/pdf`, { responseType: 'blob' });
      const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
      window.open(url, '_blank', 'noopener,noreferrer');
      window.setTimeout(() => window.URL.revokeObjectURL(url), 60000);
    } catch (error) {
      window.alert(error?.response?.data?.message || 'Unable to open invoice PDF right now.');
    } finally {
      setOpeningPdfId(null);
    }
  };

  return (
    <div className="panel">
      <table className="table">
        <thead>
          <tr>
            <th>Invoice #</th>
            <th>Customer</th>
            <th>Organization</th>
            <th>Invoice Date</th>
            <th>Due Date</th>
            <th>Total</th>
            <th>PDF</th>
          </tr>
        </thead>
        <tbody>
          {invoices.length === 0 ? (
            <tr>
              <td colSpan="7" className="empty-cell">No invoices found.</td>
            </tr>
          ) : (
            invoices.map((invoice) => (
              <tr key={invoice.id}>
                <td>{invoice.invoice_number}</td>
                <td>{invoice.customer_name}</td>
                <td>{invoice.organization}</td>
                <td>{invoice.invoice_date}</td>
                <td>{invoice.due_date}</td>
                <td>NLe {Number(invoice.total).toFixed(2)}</td>
                <td>
                  {invoice.pdf_path ? (
                    <button
                      type="button"
                      className="table-action-link"
                      onClick={() => handleOpenPdf(invoice)}
                      disabled={openingPdfId === invoice.id}
                    >
                      {openingPdfId === invoice.id ? 'Opening...' : 'Open'}
                    </button>
                  ) : (
                    '-'
                  )}
                </td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
}

export default InvoiceTable;
