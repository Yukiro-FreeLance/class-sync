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

    Alpine.data('scheduleTutorial', () => ({
        active: false,
        stepIndex: 0,
        highlight: { top: 0, left: 0, width: 0, height: 0 },
        popover: { top: 0, left: 0, width: 360 },
        storageKey: 'class-sync_schedule_tutorial_v2',

        steps: [
            {
                target: null,
                title: 'Welcome to Class Schedules',
                body: 'This tour walks you through building a weekly schedule—filters, class details, multiple time slots, and the grouped overview table.',
                centered: true,
            },
            {
                target: '[data-schedule-tour="filters"]',
                title: 'Step 1 · Set your filters',
                body: 'Start with the academic year, department, semester, and grade. For Senior High School, pick a strand to narrow sections.',
                placement: 'bottom',
            },
            {
                target: '[data-schedule-tour="class-details"]',
                title: 'Step 2 · Choose class details',
                body: 'Select the section, subject, teacher, and optional room. Use the + buttons to quickly add missing sections, subjects, teachers, or rooms.',
                placement: 'right',
            },
            {
                target: '[data-schedule-tour="time-days"]',
                title: 'Step 3 · Set times and days',
                body: 'Add one or more time slots—use Add time slot for morning and afternoon periods. Toggle the days this class meets, or switch to custom times per day.',
                placement: 'right',
            },
            {
                target: '[data-schedule-tour="submit"]',
                title: 'Step 4 · Save to schedule',
                body: 'Click Add to Schedule when ready. Each day and time slot becomes its own entry. Conflict warnings appear if the section, teacher, or room overlaps.',
                placement: 'top',
            },
            {
                target: '[data-schedule-tour="overview"]',
                title: 'Step 5 · Review the overview',
                body: 'Saved classes appear in this table, grouped when subject, time, room, and teacher match—e.g. Mon–Sat in one row. Use Edit or Delete on single entries, or Manage to update individual days in a group.',
                placement: 'left',
            },
            {
                target: null,
                title: "You're all set!",
                body: 'Replay this tour anytime with the Tutorial button. Happy scheduling!',
                centered: true,
            },
        ],

        init() {
            this._onResize = () => this.active && this.positionStep();
            window.addEventListener('resize', this._onResize);

            if (! localStorage.getItem(this.storageKey)) {
                setTimeout(() => this.start(false), 900);
            }
        },

        destroy() {
            window.removeEventListener('resize', this._onResize);
            document.body.classList.remove('overflow-hidden');
        },

        get step() {
            return this.steps[this.stepIndex] ?? this.steps[0];
        },

        get isCentered() {
            return ! this.step.target || this.step.centered;
        },

        get progress() {
            return ((this.stepIndex + 1) / this.steps.length) * 100;
        },

        get isFirst() {
            return this.stepIndex === 0;
        },

        get isLast() {
            return this.stepIndex === this.steps.length - 1;
        },

        start(force = true) {
            if (! force && localStorage.getItem(this.storageKey)) {
                return;
            }

            this.stepIndex = 0;
            this.active = true;
            document.body.classList.add('overflow-hidden');
            this.$nextTick(() => this.positionStep());
        },

        finish() {
            this.active = false;
            document.body.classList.remove('overflow-hidden');
            localStorage.setItem(this.storageKey, '1');
        },

        skip() {
            this.finish();
        },

        next() {
            if (this.isLast) {
                this.finish();

                return;
            }

            this.stepIndex++;
            this.$nextTick(() => this.positionStep());
        },

        prev() {
            if (this.isFirst) {
                return;
            }

            this.stepIndex--;
            this.$nextTick(() => this.positionStep());
        },

        positionStep() {
            const step = this.step;

            if (! step.target) {
                return;
            }

            const element = document.querySelector(step.target);

            if (! element) {
                return;
            }

            element.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });

            window.setTimeout(() => {
                const rect = element.getBoundingClientRect();
                const padding = 10;

                this.highlight = {
                    top: Math.max(rect.top - padding, 8),
                    left: Math.max(rect.left - padding, 8),
                    width: Math.min(rect.width + padding * 2, window.innerWidth - 16),
                    height: rect.height + padding * 2,
                };

                this.positionPopover(rect, step.placement ?? 'bottom');
            }, 320);
        },

        positionPopover(rect, placement) {
            const width = Math.min(360, window.innerWidth - 32);
            const height = 240;
            const gap = 14;
            let top = 16;
            let left = 16;

            switch (placement) {
                case 'top':
                    top = rect.top - height - gap;
                    left = this.clampHorizontal(rect.left, width);
                    break;
                case 'left':
                    top = this.clampVertical(rect.top, height);
                    left = rect.left - width - gap;
                    break;
                case 'right':
                    top = this.clampVertical(rect.top, height);
                    left = rect.right + gap;
                    break;
                default:
                    top = rect.bottom + gap;
                    left = this.clampHorizontal(rect.left, width);
            }

            this.popover = {
                top: Math.max(16, Math.min(top, window.innerHeight - height - 16)),
                left: Math.max(16, Math.min(left, window.innerWidth - width - 16)),
                width,
            };
        },

        clampHorizontal(value, width) {
            return Math.max(16, Math.min(value, window.innerWidth - width - 16));
        },

        clampVertical(value, height) {
            return Math.max(16, Math.min(value, window.innerHeight - height - 16));
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
