function InvoicePreview({ invoice }) {
  return (
    <div className="panel stacked">
      <div className="preview-header">
        <h3>Invoice Review</h3>
        <p>Confirm all details before creating the invoice and generating PDF.</p>
      </div>

      <div className="preview-grid">
        <div>
          <h4>Invoice Info</h4>
          <p><strong>Invoice #:</strong> {invoice.invoice_number}</p>
          <p><strong>Invoice Date:</strong> {invoice.invoice_date}</p>
          <p><strong>Due Date:</strong> {invoice.due_date}</p>
          <p><strong>P.O Number:</strong> {invoice.po_number || '-'}</p>
        </div>

        <div>
          <h4>Customer</h4>
          <p><strong>Name:</strong> {invoice.customer_name}</p>
          <p><strong>Organization:</strong> {invoice.organization}</p>
        </div>
      </div>

      <div className="preview-grid">
        <div>
          <h4>Bill To</h4>
          <pre>{invoice.bill_to}</pre>
        </div>
        <div>
          <h4>Ship To</h4>
          <pre>{invoice.ship_to}</pre>
        </div>
      </div>

      <div>
        <h4>Items</h4>
        <table className="table">
          <thead>
            <tr>
              <th>Qty</th>
              <th>Description</th>
              <th>Unit Price</th>
              <th>Amount</th>
            </tr>
          </thead>
          <tbody>
            {invoice.items.map((item, index) => (
              <tr key={`preview-item-${index}`}>
                <td>{item.quantity}</td>
                <td>{item.description}</td>
                <td>NLe {Number(item.unit_price).toFixed(2)}</td>
                <td>NLe {Number(item.amount).toFixed(2)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <div className="preview-totals">
        <p><strong>Subtotal:</strong> NLe {Number(invoice.subtotal).toFixed(2)}</p>
        <p><strong>Sales Tax:</strong> NLe {Number(invoice.tax).toFixed(2)}</p>
        <p className="preview-total"><strong>Total:</strong> NLe {Number(invoice.total).toFixed(2)}</p>
      </div>
    </div>
  );
}

export default InvoicePreview;
