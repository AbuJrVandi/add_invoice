import { useEffect, useState } from 'react';
import api from '../services/api';
import FilterBar from '../components/FilterBar';
import InvoiceTable from '../components/InvoiceTable';

const defaultFilters = {
  invoice_number: '',
  customer_name: '',
  organization: '',
  date: '',
};

function InvoiceList() {
  const [filters, setFilters] = useState(defaultFilters);
  const [draftFilters, setDraftFilters] = useState(defaultFilters);
  const [invoices, setInvoices] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchInvoices = async (activeFilters) => {
    setLoading(true);
    try {
      const params = Object.fromEntries(
        Object.entries(activeFilters)
          .map(([key, value]) => [key, typeof value === 'string' ? value.trim() : value])
          .filter(([, value]) => value !== '')
      );
      const response = await api.get('/invoices', { params });
      setInvoices(response.data.data || []);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchInvoices(filters);
  }, []);

  const handleChange = (field, value) => {
    setDraftFilters((prev) => ({ ...prev, [field]: value }));
  };

  const handleApply = () => {
    setFilters(draftFilters);
    fetchInvoices(draftFilters);
  };

  const handleReset = () => {
    setDraftFilters(defaultFilters);
    setFilters(defaultFilters);
    fetchInvoices(defaultFilters);
  };

  return (
    <div className="stacked">
      <h2>Invoices</h2>
      <FilterBar filters={draftFilters} onChange={handleChange} onReset={handleReset} onApply={handleApply} />
      {loading ? <p>Loading invoices...</p> : <InvoiceTable invoices={invoices} />}
    </div>
  );
}

export default InvoiceList;
