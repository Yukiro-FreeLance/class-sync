document.addEventListener('alpine:init', () => {
    const destroyChartOnCanvas = (canvas) => {
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        const existing = Chart.getChart(canvas);

        if (existing) {
            existing.destroy();
        }
    };

    Alpine.data('weeklyAttendanceChart', (labels, data) => ({
        chart: null,
        labels,
        data,

        init() {
            this.$nextTick(() => this.render());
        },

        destroy() {
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        },

        render() {
            const canvas = this.$refs.canvas;

            if (!canvas || typeof Chart === 'undefined') {
                return;
            }

            destroyChartOnCanvas(canvas);

            const isDark = document.documentElement.classList.contains('dark');

            this.chart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: this.labels,
                    datasets: [{
                        label: 'Attendance %',
                        data: this.data,
                        borderColor: '#7c3aed',
                        backgroundColor: 'rgba(124, 58, 237, 0.08)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#7c3aed',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: { callback: (value) => `${value}%` },
                            grid: { color: isDark ? '#334155' : '#f1f5f9' },
                        },
                        x: { grid: { display: false } },
                    },
                },
            });
        },
    }));

    Alpine.data('statusBreakdownChart', (labels, data, colors = null) => ({
        chart: null,
        labels,
        data,
        colors,

        init() {
            this.$nextTick(() => this.render());
        },

        destroy() {
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        },

        render() {
            const canvas = this.$refs.canvas;

            if (!canvas || typeof Chart === 'undefined') {
                return;
            }

            destroyChartOnCanvas(canvas);

            const palette = this.colors ?? ['#22c55e', '#f59e0b', '#ef4444', '#3b82f6', '#94a3b8'];
            const total = this.data.reduce((sum, value) => sum + Number(value), 0);
            const isEmpty = total === 0;

            this.chart = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: isEmpty ? ['No data'] : this.labels,
                    datasets: [{
                        data: isEmpty ? [1] : this.data,
                        backgroundColor: isEmpty ? ['#e2e8f0'] : palette.slice(0, this.data.length),
                        borderWidth: 0,
                        hoverOffset: isEmpty ? 0 : 4,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: { legend: { display: false }, tooltip: { enabled: !isEmpty } },
                },
            });
        },
    }));

    Alpine.data('appShell', () => ({
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        sidebarWide: window.matchMedia('(min-width: 1024px)').matches,
        dark: localStorage.getItem('theme') === 'dark',
        commandOpen: false,
        commandQuery: '',
        toasts: [],

        init() {
            const sidebarMedia = window.matchMedia('(min-width: 1024px)');
            sidebarMedia.addEventListener('change', (event) => {
                this.sidebarWide = event.matches;
            });

            if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                this.dark = true;
                document.documentElement.classList.add('dark');
            }

            window.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    this.commandOpen = true;
                }
                if (e.key === 'Escape') {
                    this.commandOpen = false;
                }
            });

            window.addEventListener('toast', (e) => {
                this.addToast(e.detail?.message ?? 'Notification', e.detail?.type ?? 'info');
            });

            document.addEventListener('livewire:init', () => this.registerLivewireToasts());
            document.addEventListener('livewire:init', () => this.registerLayoutColorUpdates());
        },

        registerLayoutColorUpdates() {
            if (typeof Livewire === 'undefined') return;
            Livewire.on('layout-colors-updated', (data) => {
                if (document.documentElement.classList.contains('dark')) return;
                const payload = Array.isArray(data) ? data[0] : data;
                const variables = payload?.variables ?? {};
                Object.entries(variables).forEach(([key, value]) => {
                    document.documentElement.style.setProperty(key, value);
                });
            });
        },

        registerLivewireToasts() {
            if (typeof Livewire === 'undefined') return;
            Livewire.on('toast', (data) => {
                const payload = Array.isArray(data) ? data[0] : data;
                this.addToast(payload?.message ?? 'Done', payload?.type ?? 'success');
            });
        },

        toggleDark() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        },

        toggleSidebarCollapse() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed ? 'true' : 'false');
        },

        get sidebarNarrow() {
            return this.sidebarCollapsed && this.sidebarWide;
        },

        addToast(message, type = 'info') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => this.removeToast(id), 4000);
        },

        removeToast(id) {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        },

        get commands() {
            const items = window.__classSyncCommands ?? [
                { label: 'Dashboard', href: '/dashboard' },
            ];

            if (!this.commandQuery) {
                return items;
            }

            const q = this.commandQuery.toLowerCase();
            return items.filter((item) => item.label.toLowerCase().includes(q));
        },
    }));
});
