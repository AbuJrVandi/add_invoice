function FilterBar({
  filters = { invoice_number: '', customer_name: '', organization: '', date: '' },
  onChange = () => {},
  onReset = () => {},
  onApply = () => {},
}) {
  const handleSubmit = (event) => {
    event.preventDefault();
    onApply();
  };

  return (
    <form className="panel stacked" onSubmit={handleSubmit}>
      <div className="filter-grid">
        <div>
          <label>Invoice Number</label>
          <input
            placeholder="e.g. INV-2026"
            value={filters.invoice_number}
            onChange={(e) => onChange('invoice_number', e.target.value)}
          />
        </div>
        <div>
          <label>Customer Name</label>
          <input
            placeholder="e.g. John Doe"
            value={filters.customer_name}
            onChange={(e) => onChange('customer_name', e.target.value)}
          />
        </div>
        <div>
          <label>Organization</label>
          <input
            placeholder="e.g. DataWeb Solutions"
            value={filters.organization}
            onChange={(e) => onChange('organization', e.target.value)}
          />
        </div>
        <div>
          <label>Invoice Date</label>
          <input type="date" value={filters.date} onChange={(e) => onChange('date', e.target.value)} />
        </div>
      </div>
      <div className="filter-actions">
        <button type="submit" className="button">Search</button>
        <button type="button" className="button button-outline" onClick={onReset}>Reset</button>
      </div>
    </form>
  );
}

export default FilterBar;
