/**
 * Dashboard Charts Initialization
 */

function initDashboardCharts(data) {
    const isDarkMode = document.documentElement.classList.contains('dark');
    const chartColors = {
        primary: isDarkMode ? 'rgb(96, 165, 250)' : 'rgb(59, 130, 246)',
        success: isDarkMode ? 'rgb(74, 222, 128)' : 'rgb(34, 197, 94)',
        danger: isDarkMode ? 'rgb(248, 113, 113)' : 'rgb(239, 68, 68)',
        warning: isDarkMode ? 'rgb(251, 191, 36)' : 'rgb(245, 158, 11)',
        info: isDarkMode ? 'rgb(129, 140, 248)' : 'rgb(99, 102, 241)',
        light: isDarkMode ? 'rgb(51, 65, 85)' : 'rgb(241, 245, 249)',
        dark: isDarkMode ? 'rgb(203, 213, 225)' : 'rgb(15, 23, 42)',
        purple: isDarkMode ? 'rgb(196, 181, 253)' : 'rgb(139, 92, 246)',
        orange: isDarkMode ? 'rgb(251, 146, 60)' : 'rgb(249, 115, 22)'
    };

    // Chart.js default configuration
    Chart.defaults.color = isDarkMode ? 'rgb(203, 213, 225)' : 'rgb(15, 23, 42)';
    Chart.defaults.borderColor = isDarkMode ? 'rgb(51, 65, 85)' : 'rgb(226, 232, 240)';
    Chart.defaults.backgroundColor = isDarkMode ? 'rgba(51, 65, 85, 0.1)' : 'rgba(241, 245, 249, 0.1)';
    Chart.defaults.animation.duration = 1000;
    Chart.defaults.animation.easing = 'easeOutQuart';

    // 0. Yearly Sales vs Expenses Chart (Jan to Dec)
    const yearlyCtx = document.getElementById('yearlyFinanceChart');
    if (yearlyCtx && data.yearlyRevenueData) {
        const monthsNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        
        new Chart(yearlyCtx, {
            type: 'bar', // Using bar for categorical monthly comparison
            data: {
                labels: monthsNames,
                datasets: [
                    {
                        label: 'Ventas (Ingresos)',
                        data: data.yearlyRevenueData.map(item => item.total),
                        backgroundColor: chartColors.success,
                        borderRadius: 6,
                        borderWidth: 0,
                    },
                    {
                        label: 'Gastos',
                        data: data.yearlyExpensesData.map(item => item.total),
                        backgroundColor: chartColors.danger,
                        borderRadius: 6,
                        borderWidth: 0,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: (context) => context.dataset.label + ': $' + context.parsed.y.toLocaleString('es-MX')
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => '$' + (value >= 1000 ? (value / 1000).toFixed(0) + 'k' : value)
                        }
                    }
                }
            }
        });
    }

    // 1. Income vs Expenses Chart
    const incomeExpensesCtx = document.getElementById('incomeExpensesChart');
    if (incomeExpensesCtx) {
        new Chart(incomeExpensesCtx, {
            type: 'bar',
            data: {
                labels: [data.monthLabel],
                datasets: [
                    {
                        label: 'Ingresos',
                        data: [data.monthlyRevenue],
                        backgroundColor: chartColors.success,
                        borderRadius: 8
                    },
                    {
                        label: 'Ingresos Esperados',
                        data: [data.monthlyExpectedIncome],
                        backgroundColor: chartColors.info,
                        borderRadius: 8
                    },
                    {
                        label: 'Gastos del Mes',
                        data: [data.monthlyActualExpenses],
                        backgroundColor: chartColors.danger,
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: (context) => context.dataset.label + ': $' + context.parsed.y.toLocaleString('es-MX')
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => '$' + (value >= 1000 ? (value / 1000).toFixed(0) + 'k' : value)
                        }
                    }
                }
            }
        });
    }

    // 2. Financial Trend Chart
    const trendCtx = document.getElementById('revenueExpensesTrendChart');
    if (trendCtx && data.revenueByMonth) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: data.revenueByMonth.map(item => item.month),
                datasets: [
                    {
                        label: 'Ingresos',
                        data: data.revenueByMonth.map(item => item.total),
                        borderColor: chartColors.success,
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Gastos',
                        data: data.expensesByMonth ? data.expensesByMonth.map(item => item.total) : [],
                        borderColor: chartColors.danger,
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });
    }

    // 3. Leads per Month Chart
    const leadsCtx = document.getElementById('leadsChart');
    if (leadsCtx && data.leadsByMonth) {
        new Chart(leadsCtx, {
            type: 'line',
            data: {
                labels: data.leadsByMonth.map(item => item.month),
                datasets: [{
                    label: 'Leads',
                    data: data.leadsByMonth.map(item => item.total),
                    borderColor: chartColors.purple,
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // 4. Lead Conversion Chart
    const conversionCtx = document.getElementById('conversionChart');
    if (conversionCtx && data.leadsConversionData) {
        new Chart(conversionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Convertidos', 'Otros'],
                datasets: [{
                    data: [data.leadsConversionData.converted, data.leadsConversionData.others],
                    backgroundColor: [chartColors.success, chartColors.light],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // 5. Services Distribution Chart
    const servicesCtx = document.getElementById('servicesDistributionChart');
    if (servicesCtx && data.servicesByType) {
        new Chart(servicesCtx, {
            type: 'pie',
            data: {
                labels: Object.keys(data.servicesByType),
                datasets: [{
                    data: Object.values(data.servicesByType),
                    backgroundColor: [
                        chartColors.primary, chartColors.purple, chartColors.orange,
                        chartColors.info, chartColors.success, chartColors.warning
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    }

    // 6. Payments Status Chart
    const paymentsCtx = document.getElementById('paymentsStatusChart');
    if (paymentsCtx && data.paymentsStatus) {
        new Chart(paymentsCtx, {
            type: 'bar',
            data: {
                labels: ['Pagado', 'Pendiente', 'Vencido'],
                datasets: [{
                    label: 'Pagos',
                    data: [data.paymentsStatus.paid, data.paymentsStatus.pending, data.paymentsStatus.overdue],
                    backgroundColor: [chartColors.success, chartColors.warning, chartColors.danger],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
}
