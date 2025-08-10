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
            if (!this.container) { 
                console.error('Container not found: ' + this.options.containerId); 
                return; 
            }

            this.initElements();
            this.bindEvents();
            
            // Применяем фильтр по умолчанию, если филиал выбран
            if (this.elements.shopsFilter && this.elements.shopsFilter.value) {
                this.filterDoctors();
            }
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

            // Клик по врачу
            if (this.elements.doctorsList) {
                this.elements.doctorsList.addEventListener('click', function(e) {
                    var doctorItem = e.target.closest('.doctor-item');
                    if (doctorItem) {
                        e.preventDefault();
                        self.selectDoctor(doctorItem);
                    }
                });
            }

            // Поиск с задержкой
            if (this.elements.doctorSearch) {
                this.elements.doctorSearch.addEventListener('input', function() {
                    clearTimeout(self.searchTimeout);
                    self.searchTimeout = setTimeout(function() { 
                        self.filterDoctors(); 
                    }, 300);
                });
            }

            // Фильтр по услугам
            if (this.elements.servicesFilter) {
                this.elements.servicesFilter.addEventListener('change', function() { 
                    self.filterDoctors(); 
                });
            }

            // Фильтр по филиалам
            if (this.elements.shopsFilter) {
                this.elements.shopsFilter.addEventListener('change', function() { 
                    self.filterDoctors(); 
                });
            }

            // Навигация по неделям (делегирование событий)
            if (this.elements.scheduleContent) {
                this.elements.scheduleContent.addEventListener('click', function(e) {
                    var prevWeek = e.target.closest('.prev_week');
                    var nextWeek = e.target.closest('.next_week');
                    
                    if (prevWeek) { 
                        e.preventDefault(); 
                        self.changeWeek(prevWeek.dataset.datefrom, prevWeek.dataset.dateto); 
                    }
                    if (nextWeek) { 
                        e.preventDefault(); 
                        self.changeWeek(nextWeek.dataset.datefrom, nextWeek.dataset.dateto); 
                    }
                });
            }
        },

        selectDoctor: function(doctorItem) {
            if (!doctorItem) return;

            // Убираем выделение с других врачей
            var allDoctors = this.elements.doctorsList.querySelectorAll('.doctor-item');
            allDoctors.forEach(function(item) { 
                item.classList.remove('selected-doctor'); 
            });
            
            // Выделяем выбранного врача
            doctorItem.classList.add('selected-doctor');

            // Сохраняем данные выбранного врача
            this.selectedDoctor = {
                id      : doctorItem.dataset.doctorId,
                name    : doctorItem.dataset.name,
                services: doctorItem.dataset.services || '',
                shops   : doctorItem.dataset.shops || ''
            };
            
            // Загружаем расписание
            this.loadSchedule();
        },

        filterDoctors: function() {
            var searchTerm   = this.elements.doctorSearch ? this.elements.doctorSearch.value.toLowerCase().trim() : '';
            var selectedServ = this.elements.servicesFilter ? this.elements.servicesFilter.value : '';
            var selectedShop = this.elements.shopsFilter ? this.elements.shopsFilter.value : '';

            var items = this.elements.doctorsList.querySelectorAll('.doctor-item');
            var visibleCount = 0;

            items.forEach(function(item) {
                var name     = (item.dataset.name || '').toLowerCase();
                var services = (item.dataset.services || '').toLowerCase();
                var shops    = (item.dataset.shops || '').toLowerCase();

                // Проверка поиска
                var matchSearch = !searchTerm || 
                    name.includes(searchTerm) || 
                    services.includes(searchTerm);

                // Проверка фильтра услуг
                var matchServ = !selectedServ || 
                    services.includes(selectedServ.toLowerCase()) ||
                    (item.dataset.services && item.dataset.services.split(',').includes(selectedServ));

                // Проверка фильтра филиалов
                var matchShop = !selectedShop || 
                    shops.includes(selectedShop.toLowerCase()) ||
                    (item.dataset.shops && item.dataset.shops.split(',').includes(selectedShop));

                var wrapper = item.closest('.staff-list-inner__wrapper');
                if (wrapper) {
                    var isVisible = matchSearch && matchServ && matchShop;
                    wrapper.style.display = isVisible ? 'block' : 'none';
                    if (isVisible) visibleCount++;
                }
            });

            // Обновляем видимость разделов
            this.updateSectionsVisibility();
            
            // Показываем сообщение, если ничего не найдено
            this.showNoResultsMessage(visibleCount === 0);
        },

        updateSectionsVisibility: function() {
            var sections = this.elements.doctorsList.querySelectorAll('.staff-list-inner__section');
            sections.forEach(function(section) {
                var visibleItems = section.querySelectorAll('.staff-list-inner__wrapper:not([style*="display:none"])');
                section.style.display = visibleItems.length > 0 ? 'block' : 'none';
            });
        },

        showNoResultsMessage: function(show) {
            var existingMsg = this.container.querySelector('.no-results-message');
            
            if (show) {
                if (!existingMsg) {
                    var msg = document.createElement('div');
                    msg.className = 'no-results-message';
                    msg.style.cssText = 'text-align:center;padding:40px;color:#6c757d;font-style:italic;background:#f8f9fa;border-radius:8px;margin:20px;';
                    msg.innerHTML = '<p>По заданным критериям врачи не найдены</p><p style="font-size:14px;margin-top:10px;">Попробуйте изменить параметры поиска</p>';
                    this.elements.doctorsList.parentNode.appendChild(msg);
                }
            } else if (existingMsg) {
                existingMsg.remove();
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
                try {
                    self.elements.scheduleContent.innerHTML = response;
                    self.isLoading = false;
                } catch (error) {
                    console.error('Error processing schedule response:', error);
                    self.showError('Ошибка обработки ответа сервера');
                    self.isLoading = false;
                }
            };
            
            var onError = function(error) {
                console.error('Schedule loading error:', error);
                self.showError('Ошибка загрузки расписания. Попробуйте позже.');
                self.isLoading = false;
            };

            // Используем BX.ajax если доступен, иначе нативный XMLHttpRequest
            if (this.options.useAjax && typeof BX !== 'undefined' && BX.ajax) {
                BX.ajax({
                    url       : this.options.ajaxUrl,
                    method    : 'POST',
                    data      : data,
                    onsuccess : onSuccess,
                    onfailure : onError
                });
            } else {
                this.nativeAjaxRequest(data, onSuccess, onError);
            }
        },

        nativeAjaxRequest: function(data, onSuccess, onError) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', this.options.ajaxUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        onSuccess(xhr.responseText);
                    } else {
                        onError(xhr.statusText || 'Network error');
                    }
                }
            };
            
            xhr.onerror = function() {
                onError('Network error');
            };

            // Формируем данные для отправки
            var formData = Object.keys(data).map(function(key) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(data[key]);
            }).join('&');
            
            xhr.send(formData);
        },

        changeWeek: function(datefrom, dateto) {
            if (this.selectedDoctor && datefrom && dateto) {
                this.loadSchedule(datefrom, dateto);
            }
        },

        showLoading: function() {
            this.elements.scheduleContent.innerHTML = 
                '<div class="loading-state" style="text-align:center;padding:40px;color:#6c757d;">' +
                '<div class="loading-spinner" style="margin-bottom:15px;">' +
                '<i class="fa fa-spinner fa-spin" style="font-size:24px;"></i>' +
                '</div>' +
                '<div>Загрузка расписания...</div>' +
                '</div>';
        },

        showError: function(message) {
            this.elements.scheduleContent.innerHTML = 
                '<div class="schedule-error" style="text-align:center;padding:40px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:8px;color:#721c24;margin:20px;">' +
                '<div style="margin-bottom:10px;"><i class="fa fa-exclamation-triangle" style="font-size:24px;"></i></div>' +
                '<div style="font-weight:500;margin-bottom:5px;">Ошибка загрузки</div>' +
                '<div style="font-size:14px;">' + (message || 'Произошла ошибка при загрузке расписания') + '</div>' +
                '<div style="margin-top:15px;"><button onclick="location.reload()" class="btn btn-sm" style="padding:5px 15px;background:#dc3545;color:white;border:none;border-radius:4px;cursor:pointer;">Обновить страницу</button></div>' +
                '</div>';
        }
    };

    // Полифилл для closest (для старых браузеров)
    if (!Element.prototype.closest) {
        Element.prototype.closest = function(selector) {
            var element = this;
            while (element && element.nodeType === 1) {
                if (element.matches && element.matches(selector)) {
                    return element;
                }
                element = element.parentNode;
            }
            return null;
        };
    }

    // Полифилл для matches
    if (!Element.prototype.matches) {
        Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
    }
})();