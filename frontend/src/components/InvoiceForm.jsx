import { useMemo, useState } from 'react';

const emptyItem = { description: '', quantity: 1, unit_price: 0, amount: 0 };

function InvoiceForm({ onPreview, loading, invoiceNumber, initialData }) {
  const [form, setForm] = useState({
    customer_name: initialData?.customer_name ?? '',
    organization: initialData?.organization ?? '',
    bill_to: initialData?.bill_to ?? '',
    ship_to: initialData?.ship_to ?? '',
    invoice_date: initialData?.invoice_date ?? '',
    due_date: initialData?.due_date ?? '',
    po_number: initialData?.po_number ?? '',
    tax: initialData?.tax ?? 0,
    items: initialData?.items?.length ? initialData.items.map((item) => ({
      description: item.description ?? '',
      quantity: Number(item.quantity ?? 1),
      unit_price: Number(item.unit_price ?? 0),
      amount: Number(item.amount ?? 0),
    })) : [{ ...emptyItem }],
  });

  const subtotal = useMemo(
    () => form.items.reduce((sum, item) => sum + Number(item.amount || 0), 0),
    [form.items]
  );

  const total = useMemo(() => subtotal + Number(form.tax || 0), [subtotal, form.tax]);

  const updateField = (field, value) => setForm((prev) => ({ ...prev, [field]: value }));

  const updateItem = (index, field, value) => {
    setForm((prev) => {
      const items = [...prev.items];
      const current = { ...items[index], [field]: value };

      const quantity = Number(current.quantity || 0);
      const unitPrice = Number(current.unit_price || 0);
      current.amount = Number((quantity * unitPrice).toFixed(2));

      items[index] = current;
      return { ...prev, items };
    });
  };

  const addItem = () => setForm((prev) => ({ ...prev, items: [...prev.items, { ...emptyItem }] }));

  const removeItem = (index) => {
    setForm((prev) => {
      if (prev.items.length === 1) return prev;
      return { ...prev, items: prev.items.filter((_, i) => i !== index) };
    });
  };

  const handleSubmit = async (event) => {
    event.preventDefault();

    await onPreview({
      ...form,
      invoice_number: invoiceNumber,
      subtotal: Number(subtotal.toFixed(2)),
      tax: Number(Number(form.tax || 0).toFixed(2)),
      total: Number(total.toFixed(2)),
      items: form.items.map((item) => ({
        ...item,
        quantity: Number(item.quantity),
        unit_price: Number(item.unit_price),
        amount: Number(item.amount),
      })),
    });
  };

  return (
    <form onSubmit={handleSubmit} className="panel stacked">
      <div className="grid-2">
        <div>
          <label>Invoice Number</label>
          <input value={invoiceNumber || 'Generating...'} readOnly />
        </div>
        <div>
          <label>Customer Name</label>
          <input required placeholder="Enter customer name" value={form.customer_name} onChange={(e) => updateField('customer_name', e.target.value)} />
        </div>
        <div>
          <label>Organization</label>
          <input required placeholder="Enter organization" value={form.organization} onChange={(e) => updateField('organization', e.target.value)} />
        </div>
        <div>
          <label>P.O Number</label>
          <input placeholder="Optional" value={form.po_number} onChange={(e) => updateField('po_number', e.target.value)} />
        </div>
        <div>
          <label>Invoice Date</label>
          <input required type="date" value={form.invoice_date} onChange={(e) => updateField('invoice_date', e.target.value)} />
        </div>
        <div>
          <label>Due Date</label>
          <input required type="date" value={form.due_date} onChange={(e) => updateField('due_date', e.target.value)} />
        </div>
      </div>

      <div className="grid-2">
        <div>
          <label>Bill To</label>
          <textarea required placeholder="Billing address" value={form.bill_to} onChange={(e) => updateField('bill_to', e.target.value)} />
        </div>
        <div>
          <label>Ship To</label>
          <textarea required placeholder="Shipping address" value={form.ship_to} onChange={(e) => updateField('ship_to', e.target.value)} />
        </div>
      </div>

      <div>
        <h3>Invoice Items</h3>
        {form.items.map((item, index) => (
          <div className="item-row" key={`item-${index}`}>
            <input
              required
              placeholder="Description"
              value={item.description}
              onChange={(e) => updateItem(index, 'description', e.target.value)}
            />
            <input
              required
              type="number"
              min="1"
              placeholder="Quantity"
              value={item.quantity}
              onChange={(e) => updateItem(index, 'quantity', e.target.value)}
            />
            <input
              required
              type="number"
              min="0"
              step="0.01"
              placeholder="Unit Price"
              value={item.unit_price}
              onChange={(e) => updateItem(index, 'unit_price', e.target.value)}
            />
            <input readOnly value={Number(item.amount || 0).toFixed(2)} />
            <button type="button" className="button button-outline" onClick={() => removeItem(index)}>Remove</button>
          </div>
        ))}
        <button type="button" className="button button-outline" onClick={addItem}>Add Item</button>
      </div>

      <div className="totals-box">
        <div>Subtotal: NLe {subtotal.toFixed(2)}</div>
        <label>
          Tax
          <input
            type="number"
            min="0"
            step="0.01"
            value={form.tax}
            onChange={(e) => updateField('tax', e.target.value)}
          />
        </label>
        <div className="total">Total: NLe {total.toFixed(2)}</div>
      </div>

      <button type="submit" className="button" disabled={loading}>
        {loading ? 'Preparing...' : 'Review Invoice'}
      </button>
    </form>
  );
}

export default InvoiceForm;
