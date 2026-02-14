function InvoiceTable({ invoices }) {
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
                    <a href={`${import.meta.env.VITE_BACKEND_BASE_URL || 'http://localhost:8000'}/${invoice.pdf_path}`} target="_blank" rel="noreferrer">
                      Open
                    </a>
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
