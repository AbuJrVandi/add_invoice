import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import api from '../services/api';
import PaymentModal from '../components/PaymentModal';

export default function Payments() {
    const [invoices, setInvoices] = useState([]); // Unpaid invoices
    const [payments, setPayments] = useState([]);
    const [activeTab, setActiveTab] = useState('payments'); // 'payments' | 'new'
    const [loading, setLoading] = useState(false);
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [showModal, setShowModal] = useState(false);
    const [selectedInvoice, setSelectedInvoice] = useState(null);

    // Initial load
    useEffect(() => {
        fetchData();
    }, [activeTab, page]);

    // Search logic (debounce)
    useEffect(() => {
        const handler = setTimeout(() => {
            fetchData();
        }, 500);
        return () => clearTimeout(handler);
    }, [search]);

    const fetchData = async () => {
        setLoading(true);
        try {
            const endpoint = activeTab === 'payments' ? '/payments' : '/payments/search-invoices';
            const params = {
                page,
                customer_name: search,
                invoice_number: search,
                per_page: 15
            };
            const res = await api.get(endpoint, { params });

            if (activeTab === 'payments') {
                setPayments(res.data.data);
            } else {
                setInvoices(res.data.data);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const handlePay = (invoice) => {
        setSelectedInvoice(invoice);
        setShowModal(true);
    };

    const handleSuccess = (responseData) => {
        fetchData(); // Refresh list
        // Trigger receipt print
        if (responseData.payment) {
            printReceipt(responseData.payment);
        }
    };

    const printReceipt = (paymentOrId) => {
        // Determine URL to print
        let printUrl;
        if (typeof paymentOrId === 'object' && paymentOrId.receipt_url) {
            printUrl = paymentOrId.receipt_url;
        } else {
            // Find from list if passed as ID (from history tab)
            const p = payments.find(p => p.id === paymentOrId);
            if (p && p.receipt_url) {
                printUrl = p.receipt_url;
            }
        }

        if (!printUrl) {
            console.error('Receipt URL not found for printing');
            return;
        }

        // Create hidden iframe
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = printUrl;
        document.body.appendChild(iframe);

        iframe.onload = () => {
            try {
                iframe.contentWindow.print();
            } catch (e) {
                console.log('Using internal print script due to cross-origin restriction');
            }
            setTimeout(() => document.body.removeChild(iframe), 5000); // Cleanup after print dialog
        };
    };

    return (
        <div className="payments-page stacked">
            <div className="page-header">
                <h1>Payments & Receipts</h1>
                <div className="tabs">
                    <button
                        className={`tab ${activeTab === 'payments' ? 'active' : ''}`}
                        onClick={() => setActiveTab('payments')}
                    >
                        Payment History
                    </button>
                    <button
                        className={`tab ${activeTab === 'new' ? 'active' : ''}`}
                        onClick={() => setActiveTab('new')}
                    >
                        New Payment
                    </button>
                </div>
            </div>

            <div className="toolbar">
                <input
                    type="text"
                    placeholder="Search by Invoice # or Customer..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="search-input"
                />
            </div>

            <div className="table-responsive">
                {loading ? (
                    <div className="loading">Loading records...</div>
                ) : (
                    <table className="table">
                        <thead>
                            {activeTab === 'payments' ? (
                                <tr>
                                    <th>Receipt #</th>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            ) : (
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Action</th>
                                </tr>
                            )}
                        </thead>
                        <tbody>
                            {activeTab === 'payments' ? (
                                payments.length > 0 ? payments.map(p => (
                                    <tr key={p.id}>
                                        <td>{p.receipt_number}</td>
                                        <td>{p.invoice?.invoice_number}</td>
                                        <td>{p.invoice?.customer_name}</td>
                                        <td>{p.payment_method}</td>
                                        <td className="amount">NLe {Number(p.amount_paid).toFixed(2)}</td>
                                        <td>{new Date(p.paid_at).toLocaleDateString()}</td>
                                        <td>
                                            <div className="actions">
                                                <Link className="btn-icon" to={`/invoices/${p.invoice_id}/view`} title="View Invoice">
                                                    📄
                                                </Link>
                                                <Link className="btn-icon" to={`/payments/${p.id}/receipt`} title="View Receipt">
                                                    🧾
                                                </Link>
                                                <button className="btn-icon" onClick={() => printReceipt(p)} title="Reprint Receipt">
                                                    🖨️
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                )) : <tr><td colSpan="7" className="empty">No payments found.</td></tr>
                            ) : (
                                invoices.length > 0 ? invoices.map(inv => (
                                    <tr key={inv.id}>
                                        <td>{inv.invoice_number}</td>
                                        <td>{inv.customer_name}</td>
                                        <td>NLe {Number(inv.total).toFixed(2)}</td>
                                        <td>NLe {Number(inv.amount_paid).toFixed(2)}</td>
                                        <td className="text-danger">NLe {Number(inv.balance_remaining).toFixed(2)}</td>
                                        <td>
                                            <div className="actions">
                                                <Link className="btn-icon" to={`/invoices/${inv.id}/view`} title="View PDF">
                                                    📄
                                                </Link>
                                                <button className="button btn-sm" onClick={() => handlePay(inv)}>
                                                    Pay Now
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                )) : <tr><td colSpan="6" className="empty">No unpaid invoices found.</td></tr>
                            )}
                        </tbody>
                    </table>
                )}
            </div>

            {showModal && selectedInvoice && (
                <PaymentModal
                    invoice={selectedInvoice}
                    onClose={() => setShowModal(false)}
                    onSuccess={handleSuccess}
                />
            )}

        </div>
    );
}
