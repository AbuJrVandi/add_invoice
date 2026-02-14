import { Link } from 'react-router-dom';

function InvoiceTable({ invoices }) {
  return (
    <div className="panel">
      {invoices.length === 0 ? <p className="empty-cell">No invoices found.</p> : null}
      <div className="table-wrap">
        <table className="table table-mobile">
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
            {invoices.map((invoice) => (
              <tr key={invoice.id}>
                <td data-label="Invoice #">{invoice.invoice_number}</td>
                <td data-label="Customer">{invoice.customer_name}</td>
                <td data-label="Organization">{invoice.organization}</td>
                <td data-label="Invoice Date">{invoice.invoice_date}</td>
                <td data-label="Due Date">{invoice.due_date}</td>
                <td data-label="Total">NLe {Number(invoice.total).toFixed(2)}</td>
                <td data-label="PDF">
                  {invoice.pdf_path ? (
                    <Link className="table-action-link" to={`/invoices/${invoice.id}/view`}>View</Link>
                  ) : (
                    '-'
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export default InvoiceTable;
