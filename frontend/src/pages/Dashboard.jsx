import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';

function Dashboard() {
  const [data, setData] = useState({ total_invoices: 0, total_revenue: 0, recent_invoices: [] });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const load = async () => {
      try {
        const response = await api.get('/dashboard');
        setData(response.data);
      } finally {
        setLoading(false);
      }
    };

    load();
  }, []);

  if (loading) {
    return <p>Loading dashboard...</p>;
  }

  return (
    <div className="stacked">
      <h2>Dashboard</h2>

      <div className="stats-grid">
        <article className="panel">
          <p>Total Invoices</p>
          <h3>{data.total_invoices}</h3>
        </article>
        <article className="panel">
          <p>Total Revenue</p>
          <h3>NLe {Number(data.total_revenue).toFixed(2)}</h3>
        </article>
        <article className="panel">
          <p>Quick Action</p>
          <Link className="button" to="/invoices/create">Create Invoice</Link>
        </article>
      </div>

      <section className="panel">
        <h3>Recent Invoices</h3>
        <div className="table-wrap">
          <table className="table table-mobile">
            <thead>
              <tr>
                <th>Invoice #</th>
                <th>Customer</th>
                <th>Organization</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              {data.recent_invoices.map((invoice) => (
                <tr key={invoice.id}>
                  <td data-label="Invoice #">{invoice.invoice_number}</td>
                  <td data-label="Customer">{invoice.customer_name}</td>
                  <td data-label="Organization">{invoice.organization}</td>
                  <td data-label="Total">NLe {Number(invoice.total).toFixed(2)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );
}

export default Dashboard;
