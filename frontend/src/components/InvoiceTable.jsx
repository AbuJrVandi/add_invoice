import { Link } from 'react-router-dom';

function formatDate(value) {
  if (!value) {
    return '-';
  }

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return value;
  }

  return parsed.toLocaleDateString('en-GB');
}

function InvoiceTable({ invoices, loading }) {
  if (loading) return <div className="loading">Loading invoices...</div>;

  return (
    <div className="table-wrap">
      {invoices.length === 0 ? (
        <div className="empty-state">No invoices match your search.</div>
      ) : (
        <table className="table table-mobile">
          <thead>
            <tr>
              <th>Status</th>
              <th>Invoice #</th>
              <th>Customer</th>
              <th>Balance</th>
              <th>Total</th>
              <th>Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            {invoices.map((inv) => (
              <tr key={inv.id}>
                <td data-label="Status">
                  <span className={`status-badge status-${String(inv.status || 'pending').toLowerCase()}`}>
                    {inv.status || 'pending'}
                  </span>
                </td>
                <td data-label="Invoice #">
                  <Link className="invoice-number-link" to={`/invoices/${inv.id}/view`}>
                    {inv.invoice_number}
                  </Link>
                </td>
                <td data-label="Customer">
                  <div className="cell-content">
                    {inv.customer_name}
                    {inv.organization && <span className="sub-text">{inv.organization}</span>}
                  </div>
                </td>
                <td data-label="Balance">
                  <span className={Number(inv.balance_remaining) > 0 ? 'text-danger' : 'text-success'}>
                    NLe {Number(inv.balance_remaining).toFixed(2)}
                  </span>
                </td>
                <td data-label="Total">NLe {Number(inv.total).toFixed(2)}</td>
                <td data-label="Date">{formatDate(inv.invoice_date)}</td>
                <td data-label="Action">
                  <div className="actions">
                    <Link className="table-action-link" to={`/invoices/${inv.id}/view`}>
                      View
                    </Link>
                    {Number(inv.balance_remaining) > 0 && (
                      <Link className="table-action-link" to="/payments">
                        Pay
                      </Link>
                    )}
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}

export default InvoiceTable;
