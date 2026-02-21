import { useMemo, useState } from 'react';
import useResponsive from '../hooks/useResponsive';

const emptyItem = { description: '', quantity: 1, unit_price: 0, amount: 0 };
const mobileSteps = ['Customer', 'Dates', 'Addresses', 'Items', 'Totals'];

function formatNumberForDisplay(value) {
  const numeric = Number(value || 0);
  if (!Number.isFinite(numeric)) {
    return '0';
  }

  return String(parseFloat(numeric.toFixed(2)));
}

function InvoiceForm({ onPreview, loading, invoiceNumber, initialData }) {
  const { isMobile } = useResponsive();
  const [mobileStep, setMobileStep] = useState(0);
  const [stepError, setStepError] = useState('');
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

  const total = useMemo(() => subtotal - Number(form.tax || 0), [subtotal, form.tax]);

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

  const buildPayload = () => ({
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

  const submitPreview = async () => {
    await onPreview(buildPayload());
  };

  const validateStep = (stepIndex) => {
    if (stepIndex === 0) {
      return Boolean(form.customer_name.trim() && form.organization.trim());
    }
    if (stepIndex === 1) {
      return Boolean(form.invoice_date && form.due_date);
    }
    if (stepIndex === 2) {
      return Boolean(form.bill_to.trim() && form.ship_to.trim());
    }
    if (stepIndex === 3) {
      return form.items.every((item) => item.description.trim() && Number(item.quantity) > 0 && Number(item.unit_price) >= 0);
    }

    return true;
  };

  const validateAll = () => [0, 1, 2, 3].every((step) => validateStep(step));

  const handleSubmit = async (event) => {
    event.preventDefault();
    if (!validateAll()) {
      setStepError('Please complete all required fields before previewing the invoice.');
      return;
    }
    setStepError('');
    await submitPreview();
  };

  const handleNextStep = async () => {
    if (!validateStep(mobileStep)) {
      setStepError('Please complete required fields to continue.');
      return;
    }

    setStepError('');
    if (mobileStep === mobileSteps.length - 1) {
      await submitPreview();
      return;
    }

    setMobileStep((prev) => prev + 1);
  };

  if (isMobile) {
    return (
      <form onSubmit={handleSubmit} className="panel invoice-wizard">
        <header className="invoice-wizard-head">
          <p>Step {mobileStep + 1} of {mobileSteps.length}</p>
          <h3>{mobileSteps[mobileStep]}</h3>
        </header>

        <div className="invoice-wizard-panel">
          {mobileStep === 0 ? (
            <div className="stacked">
              <div>
                <label>Invoice Number</label>
                <input value={invoiceNumber || 'Generating...'} readOnly />
              </div>
              <div>
                <label>Customer Name</label>
                <input
                  required
                  placeholder="Enter customer name"
                  value={form.customer_name}
                  onChange={(e) => updateField('customer_name', e.target.value)}
                />
              </div>
              <div>
                <label>Organization</label>
                <input
                  required
                  placeholder="Enter organization"
                  value={form.organization}
                  onChange={(e) => updateField('organization', e.target.value)}
                />
              </div>
              <div>
                <label>P.O Number</label>
                <input placeholder="Optional" value={form.po_number} onChange={(e) => updateField('po_number', e.target.value)} />
              </div>
            </div>
          ) : null}

          {mobileStep === 1 ? (
            <div className="stacked">
              <div>
                <label>Invoice Date</label>
                <input required type="date" value={form.invoice_date} onChange={(e) => updateField('invoice_date', e.target.value)} />
              </div>
              <div>
                <label>Due Date</label>
                <input required type="date" value={form.due_date} onChange={(e) => updateField('due_date', e.target.value)} />
              </div>
            </div>
          ) : null}

          {mobileStep === 2 ? (
            <div className="stacked">
              <div>
                <label>Bill To</label>
                <textarea required placeholder="Billing address" value={form.bill_to} onChange={(e) => updateField('bill_to', e.target.value)} />
              </div>
              <div>
                <label>Ship To</label>
                <textarea required placeholder="Shipping address" value={form.ship_to} onChange={(e) => updateField('ship_to', e.target.value)} />
              </div>
            </div>
          ) : null}

          {mobileStep === 3 ? (
            <div className="stacked">
              {form.items.map((item, index) => (
                <div className="item-row-mobile" key={`item-mobile-${index}`}>
                  <label>Description</label>
                  <input
                    required
                    placeholder="Description"
                    value={item.description}
                    onChange={(e) => updateItem(index, 'description', e.target.value)}
                  />
                  <div className="grid-2">
                    <div>
                      <label>Qty</label>
                      <input
                        required
                        type="number"
                        min="1"
                        inputMode="numeric"
                        value={item.quantity}
                        onChange={(e) => updateItem(index, 'quantity', e.target.value)}
                      />
                    </div>
                    <div>
                      <label>Price</label>
                      <input
                        required
                        type="number"
                        min="0"
                        step="0.01"
                        inputMode="decimal"
                        value={item.unit_price}
                        onChange={(e) => updateItem(index, 'unit_price', e.target.value)}
                      />
                    </div>
                  </div>
                  <div>
                    <label>Amount</label>
                    <input readOnly value={formatNumberForDisplay(item.amount)} />
                  </div>
                  {form.items.length > 1 ? (
                    <button type="button" className="button button-outline" onClick={() => removeItem(index)}>Remove Item</button>
                  ) : null}
                </div>
              ))}
              <button type="button" className="button button-outline" onClick={addItem}>Add Item</button>
            </div>
          ) : null}

          {mobileStep === 4 ? (
            <div className="stacked">
              <div className="totals-box totals-box-mobile">
                <div>Subtotal: NLe {formatNumberForDisplay(subtotal)}</div>
                <label>
                  Discount
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    value={form.tax}
                    onChange={(e) => updateField('tax', e.target.value)}
                    inputMode="decimal"
                  />
                </label>
                <div className="total">Total: NLe {formatNumberForDisplay(total)}</div>
              </div>
            </div>
          ) : null}
        </div>

        {stepError ? <p className="error">{stepError}</p> : null}

        <footer className="invoice-wizard-footer">
          <button
            type="button"
            className="button button-outline"
            onClick={() => setMobileStep((prev) => Math.max(0, prev - 1))}
            disabled={mobileStep === 0 || loading}
          >
            Back
          </button>
          <button type="button" className="button" onClick={handleNextStep} disabled={loading}>
            {loading ? 'Preparing...' : mobileStep === mobileSteps.length - 1 ? 'Review Invoice' : 'Next'}
          </button>
        </footer>
      </form>
    );
  }

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
        <div className="invoice-items-head">
          <span>Description</span>
          <span>Qty</span>
          <span>Price</span>
          <span>Amount</span>
          <span>Action</span>
        </div>
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
              inputMode="numeric"
            />
            <input
              required
              type="number"
              min="0"
              step="0.01"
              placeholder="Price"
              value={item.unit_price}
              onChange={(e) => updateItem(index, 'unit_price', e.target.value)}
              inputMode="decimal"
            />
            <input readOnly value={formatNumberForDisplay(item.amount)} />
            <button type="button" className="button button-outline" onClick={() => removeItem(index)}>Remove</button>
          </div>
        ))}
        <button type="button" className="button button-outline" onClick={addItem}>Add Item</button>
      </div>

      <div className="totals-box">
        <div>Subtotal: NLe {formatNumberForDisplay(subtotal)}</div>
        <label>
          Discount
          <input
            type="number"
            min="0"
            step="0.01"
            value={form.tax}
            onChange={(e) => updateField('tax', e.target.value)}
            inputMode="decimal"
          />
        </label>
        <div className="total">Total: NLe {formatNumberForDisplay(total)}</div>
      </div>

      {stepError ? <p className="error">{stepError}</p> : null}

      <button type="submit" className="button" disabled={loading}>
        {loading ? 'Preparing...' : 'Review Invoice'}
      </button>
    </form>
  );
}

export default InvoiceForm;
