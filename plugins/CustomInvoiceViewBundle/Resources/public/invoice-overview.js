// Invoice Overview Vue.js Application
const { createApp } = Vue;

createApp({
    delimiters: ['[[', ']]'], // Use [[ ]] to avoid conflict with Twig {{ }}
    data() {
        return {
            selectedYear: window.currentYear,
            currentYear: new Date().getFullYear(),
            loading: false,
            monthlyData: [],
            totalPaid: 0,
            totalPending: 0,
            invoices: [],
            currency: 'USD',
            chart: null
        };
    },
    mounted() {
        // Load initial data
        this.loadData(window.initialInvoiceData);
    },
    methods: {
        loadData(data) {
            this.selectedYear = data.year;
            this.monthlyData = data.monthlyData;
            this.totalPaid = data.totalPaid;
            this.totalPending = data.totalPending;
            this.invoices = data.invoices;
            this.currency = data.currency;

            // Update or create chart
            this.$nextTick(() => {
                this.updateChart();
            });
        },

        async fetchData(year) {
            this.loading = true;
            try {
                const response = await fetch(`/invoice-overview/data?year=${year}`);
                const data = await response.json();
                this.loadData(data);
            } catch (error) {
                console.error('Error fetching invoice data:', error);
                alert('Failed to load invoice data. Please try again.');
            } finally {
                this.loading = false;
            }
        },

        previousYear() {
            this.fetchData(this.selectedYear - 1);
        },

        nextYear() {
            if (this.selectedYear < this.currentYear) {
                this.fetchData(this.selectedYear + 1);
            }
        },

        updateChart() {
            const ctx = document.getElementById('invoiceChart');
            if (!ctx) return;

            // Destroy existing chart if it exists
            if (this.chart) {
                this.chart.destroy();
            }

            // Prepare data for Chart.js
            const labels = this.monthlyData.map(item => item.month);
            const paidData = this.monthlyData.map(item => item.paid);
            const pendingData = this.monthlyData.map(item => item.pending);

            // Create new chart
            this.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Paid',
                            data: paidData,
                            backgroundColor: '#2C3E50',
                            borderColor: '#2C3E50',
                            borderWidth: 1
                        },
                        {
                            label: 'Pending',
                            data: pendingData,
                            backgroundColor: '#95A5A6',
                            borderColor: '#95A5A6',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => this.formatCurrency(value, false)
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const label = context.dataset.label || '';
                                    const value = this.formatCurrency(context.parsed.y);
                                    return `${label}: ${value}`;
                                }
                            }
                        }
                    }
                }
            });
        },

        formatCurrency(amount, includeSymbol = true) {
            const formatted = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);

            if (!includeSymbol) {
                return formatted;
            }

            // Simple currency symbol mapping
            const symbols = {
                'USD': '$',
                'EUR': '€',
                'GBP': '£',
                'JPY': '¥'
            };

            const symbol = symbols[this.currency] || this.currency;
            return `${symbol}${formatted}`;
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            }).format(date);
        },

        statusBadgeClass(status) {
            const classes = {
                'paid': 'badge badge-success',
                'pending': 'badge badge-warning',
                'new': 'badge badge-info',
                'canceled': 'badge badge-danger'
            };
            return classes[status] || 'badge badge-default';
        }
    }
}).mount('#invoice-app');
