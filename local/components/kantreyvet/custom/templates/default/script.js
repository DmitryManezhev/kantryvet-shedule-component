(function() {
    'use strict';

    if (typeof window.DoctorScheduleListComponent !== 'undefined') return;

    window.DoctorScheduleListComponent = function(options) {
        this.options = Object.assign({
            containerId   : '',
            ajaxUrl       : '',
            useAjax       : true,
            componentPath : '',
            services      : {},
            shops         : {},
            siteId        : ''
        }, options);

        this.container      = null;
        this.selectedDoctor = null;
        this.currentWeek    = new Date();
        this.isLoading      = false;

        this.elements = {
            doctorsList   : null,
            doctorSearch  : null,
            servicesFilter: null,
            shopsFilter   : null,
            scheduleContent: null
        };
    };

    window.DoctorScheduleListComponent.prototype = {
        init: function() {
            this.container = document.getElementById(this.options.containerId);
            if (!this.container) { console.error('Container not found'); return; }

            this.initElements();
            this.bindEvents();
            if (this.elements.shopsFilter && this.elements.shopsFilter.value) this.filterDoctors();
        },

        initElements: function() {
            this.elements.doctorsList    = this.container.querySelector('.staff-list-inner__sections');
            this.elements.doctorSearch   = this.container.querySelector('#doctorSearch');
            this.elements.servicesFilter = this.container.querySelector('#servicesFilter');
            this.elements.shopsFilter    = this.container.querySelector('#shopsFilter');
            this.elements.scheduleContent= this.container.querySelector('#scheduleContent');
        },

        bindEvents: function() {
            var self = this;

            // клик по врачу
            if (this.elements.doctorsList) {
                this.elements.doctorsList.addEventListener('click', function(e) {
                    var doctorItem = e.target.closest('.doctor-item');
                    if (doctorItem) self.selectDoctor(doctorItem);
                });
            }

            // строка поиска
            if (this.elements.doctorSearch) {
                this.elements.doctorSearch.addEventListener('input', function() {
                    clearTimeout(self.searchTimeout);
                    self.searchTimeout = setTimeout(function() { self.filterDoctors(); }, 300);
                });
            }

            // фильтр по услугам
            if (this.elements.servicesFilter) {
                this.elements.servicesFilter.addEventListener('change', function() { self.filterDoctors(); });
            }

            // фильтр по филиалам
            if (this.elements.shopsFilter) {
                this.elements.shopsFilter.addEventListener('change', function() { self.filterDoctors(); });
            }

            // навигация по неделям (делегирование)
            if (this.elements.scheduleContent) {
                this.elements.scheduleContent.addEventListener('click', function(e) {
                    var prev = e.target.closest('.prev_week');
                    var next = e.target.closest('.next_week');
                    if (prev) { e.preventDefault(); self.changeWeek(prev.dataset.datefrom, prev.dataset.dateto); }
                    if (next) { e.preventDefault(); self.changeWeek(next.dataset.datefrom, next.dataset.dateto); }
                });
            }
        },

        selectDoctor: function(doctorItem) {
            var allDoctors = this.elements.doctorsList.querySelectorAll('.doctor-item');
            allDoctors.forEach(function(item) { item.classList.remove('selected-doctor'); });
            doctorItem.classList.add('selected-doctor');

            this.selectedDoctor = {
                id      : doctorItem.dataset.doctorId,
                name    : doctorItem.dataset.name,
                services: doctorItem.dataset.services,
                shops   : doctorItem.dataset.shops
            };
            this.loadSchedule();
        },

        filterDoctors: function() {
            var searchTerm   = this.elements.doctorSearch ? this.elements.doctorSearch.value.toLowerCase() : '';
            var selectedServ = this.elements.servicesFilter ? this.elements.servicesFilter.value : '';
            var selectedShop = this.elements.shopsFilter ? this.elements.shopsFilter.value : '';

            var items = this.elements.doctorsList.querySelectorAll('.doctor-item');
            var visibleCount = 0;

            items.forEach(function(item) {
                var name  = item.dataset.name.toLowerCase();
                var servs = (item.dataset.services || '').toLowerCase();
                var shops = (item.dataset.shops || '').toLowerCase();

                var matchSearch = name.includes(searchTerm) || servs.includes(searchTerm);
                var matchServ   = !selectedServ || servs.includes(selectedServ);
                var matchShop   = !selectedShop || shops.includes(selectedShop);

                var wrapper = item.closest('.staff-list-inner__wrapper');
                wrapper.style.display = (matchSearch && matchServ && matchShop) ? 'block' : 'none';
                if (wrapper.style.display === 'block') visibleCount++;
            });

            this.updateSectionsVisibility();
            this.showNoResultsMessage(visibleCount === 0);
        },

        updateSectionsVisibility: function() {
            var sections = this.elements.doctorsList.querySelectorAll('.staff-list-inner__section');
            sections.forEach(function(section) {
                section.style.display = section.querySelectorAll('.staff-list-inner__wrapper:not([style*="display:none"])').length ? 'block' : 'none';
            });
        },

        showNoResultsMessage: function(show) {
            var msg = this.container.querySelector('.no-results-message');
            if (show) {
                if (!msg) {
                    msg = document.createElement('div');
                    msg.className = 'no-results-message';
                    msg.style.cssText = 'text-align:center;padding:40px;color:#6c757d;font-style:italic;';
                    msg.textContent = 'По заданным критериям врачи не найдены';
                    this.elements.doctorsList.parentNode.appendChild(msg);
                }
            } else if (msg) {
                msg.remove();
            }
        },

        loadSchedule: function(datefrom, dateto) {
            if (!this.selectedDoctor || this.isLoading) return;

            this.isLoading = true;
            this.showLoading();

            var self = this;
            var data = {
                action   : 'loadStaffSchedule',
                doctor_id: this.selectedDoctor.id,
                site_id  : this.options.siteId
            };
            if (datefrom && dateto) {
                data.datefrom = datefrom;
                data.dateto   = dateto;
            }

            var onSuccess = function(response) {
                self.elements.scheduleContent.innerHTML = response;
                self.isLoading = false;
            };
            var onError = function() {
                self.showError('Ошибка загрузки расписания');
                self.isLoading = false;
            };

            if (this.options.useAjax && typeof BX !== 'undefined' && BX.ajax) {
                BX.ajax({
                    url       : this.options.ajaxUrl,
                    method    : 'POST',
                    data      : data,
                    onsuccess : onSuccess,
                    onfailure : onError
                });
            } else {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', this.options.ajaxUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) onSuccess(xhr.responseText);
                        else onError();
                    }
                };
                xhr.send(Object.keys(data).map(function(k) {
                    return encodeURIComponent(k) + '=' + encodeURIComponent(data[k]);
                }).join('&'));
            }
        },

        changeWeek: function(datefrom, dateto) {
            if (this.selectedDoctor) this.loadSchedule(datefrom, dateto);
        },

        showLoading: function() {
            this.elements.scheduleContent.innerHTML =
                '<div class="loading-state" style="text-align:center;padding:40px;">' +
                '<i class="fa fa-spinner fa-spin"></i> Загрузка расписания...</div>';
        },

        showError: function(message) {
            this.elements.scheduleContent.innerHTML =
                '<div class="alert alert-danger" style="padding:20px;text-align:center;color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;">' +
                message + '</div>';
        }
    };
})();