<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Функция для корректировки времени
function formatWorkTime($workTime) {
    if (empty($workTime)) return '';
    
    $timeParts = explode('-', $workTime);
    if (count($timeParts) !== 2) return $workTime;
    
    $startTime = trim($timeParts[0]);
    $endTime = trim($timeParts[1]);
    
    // Исправляем некорректные форматы
    if ($endTime === '00:00' || $endTime === '00:0_' || empty($endTime)) {
        $endTime = '18:00';
    }
    
    // Проверяем корректность формата времени
    if (!preg_match('/^\d{2}:\d{2}$/', $startTime)) {
        $startTime = '09:00';
    }
    if (!preg_match('/^\d{2}:\d{2}$/', $endTime)) {
        $endTime = '18:00';
    }
    
    return $startTime . '-' . $endTime;
}

$daysRus = [
    'MONDAY'    => 'Пн',
    'TUESDAY'   => 'Вт',
    'WEDNESDAY' => 'Ср',
    'THURSDAY'  => 'Чт',
    'FRIDAY'    => 'Пт',
    'SATURDAY'  => 'Сб',
    'SUNDAY'    => 'Вс',
];

/* ---------- соберём уникальные филиалы данного врача ---------- */
$doctorShops = [];
foreach ($schedule as $daySlots) {
    foreach ($daySlots as $slot) {
        $shopId = $slot['SHOP_ID'];
        if (!isset($doctorShops[$shopId])) {
            if (isset($shops[$shopId])) {
                $doctorShops[$shopId] = $shops[$shopId];
            } else {
                // Создаем заглушку если филиал не найден
                $doctorShops[$shopId] = [
                    'ID' => $shopId,
                    'NAME' => 'Филиал #' . $shopId,
                    'PROPERTY_ADDRESS_VALUE' => 'рабочий посёлок Новоивановское, Одинцовский городской округ, Московская область, улица Мичурина, 4',
                    'PROPERTY_COORDINATES_VALUE' => '55.697037,37.129373' // Координаты для примера
                ];
            }
        }
    }
}

// Организуем расписание для табличного вывода
$scheduleByDayAndTime = [];
foreach ($schedule as $dayCode => $slots) {
    if (!isset($scheduleByDayAndTime[$dayCode])) {
        $scheduleByDayAndTime[$dayCode] = [];
    }
    
    foreach ($slots as $slot) {
        $formattedTime = formatWorkTime($slot['WORK_TIME']);
        $service = $services[$slot['SERVICE_ID']] ?? null;
        $shop = $doctorShops[$slot['SHOP_ID']] ?? null;
        
        $scheduleByDayAndTime[$dayCode][] = [
            'time' => $formattedTime,
            'service' => $service,
            'shop' => $shop,
            'slot' => $slot
        ];
    }
    
    // Сортируем по времени
    usort($scheduleByDayAndTime[$dayCode], function($a, $b) {
        return strcmp($a['time'], $b['time']);
    });
}

// Порядок дней недели
$daysOrder = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];

// Подготавливаем данные для карты
$mapPoints = [];
foreach ($doctorShops as $shop) {
    if (!empty($shop['PROPERTY_COORDINATES_VALUE'])) {
        $coords = explode(',', $shop['PROPERTY_COORDINATES_VALUE']);
        if (count($coords) === 2) {
            $mapPoints[$shop['ID']] = [
                'id' => $shop['ID'],
                'name' => $shop['NAME'],
                'addr' => $shop['PROPERTY_ADDRESS_VALUE'] ?? '',
                'lat' => (float)trim($coords[0]),
                'lon' => (float)trim($coords[1]),
            ];
        }
    }
}
?>

<?php if (!empty($schedule)): ?>
    <div class="staff-schedule" id="doctor-schedule-block">
        <div class="staff-schedule__top">
            <div class="doctor-info">
                <h3>Расписание врача <?=htmlspecialcharsbx($arDoctor['NAME'] ?? 'Врач')?></h3>
            </div>
            
            <!-- Выбор филиала -->
            <?php if (count($doctorShops) > 1): ?>
                <div class="shop-filter" style="margin-bottom: 20px;">
                    <label for="shopSelect">Филиал:</label>
                    <select id="shopSelect" class="form-control" style="max-width: 300px; display: inline-block;">
                        <option value="">Все филиалы</option>
                        <?php foreach ($doctorShops as $shop): ?>
                            <option value="<?=$shop['ID']?>">
                                <?=htmlspecialcharsbx($shop['NAME'])?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <div class="staff-schedule__body">
            <table class="schedule-table">
                <thead>
                    <tr class="staff-schedule__head">
                        <th>Дни</th>
                        <th>Время</th>
                        <th>Филиал</th>
                        <th>Услуга</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daysOrder as $dayCode): ?>
                        <?php if (isset($scheduleByDayAndTime[$dayCode]) && !empty($scheduleByDayAndTime[$dayCode])): ?>
                            <?php 
                            $daySlots = $scheduleByDayAndTime[$dayCode];
                            $rowSpan = count($daySlots);
                            ?>
                            <?php foreach ($daySlots as $index => $slotData): ?>
                                <tr class="staff-schedule__row" data-shop-id="<?=$slotData['slot']['SHOP_ID']?>">
                                    <?php if ($index === 0): ?>
                                        <td class="staff-schedule__date" rowspan="<?=$rowSpan?>">
                                            <div class="week_name"><?=$daysRus[$dayCode] ?? $dayCode?></div>
                                        </td>
                                    <?php endif; ?>
                                    
                                    <td class="staff-schedule__time">
                                        <span class="time-badge available"><?=htmlspecialcharsbx($slotData['time'])?></span>
                                    </td>
                                    
                                    <td class="staff-schedule__shop">
                                        <?php if ($slotData['shop']): ?>
                                            <div class="shop-info">
                                                <div class="shop-name">
                                                    <i class="fa fa-map-marker" style="color: #dc3545; margin-right: 5px;"></i>
                                                    <?=htmlspecialcharsbx($slotData['shop']['NAME'])?>
                                                </div>
                                                <?php if (!empty($slotData['shop']['PROPERTY_ADDRESS_VALUE'])): ?>
                                                    <div class="shop-address" style="font-size: 12px; color: #6c757d; margin-top: 2px;">
                                                        <?=htmlspecialcharsbx($slotData['shop']['PROPERTY_ADDRESS_VALUE'])?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Филиал не указан</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="staff-schedule__service">
                                        <?php if ($slotData['service']): ?>
                                            <a href="<?=$slotData['service']['DETAIL_PAGE_URL']?>" class="service-link">
                                                <?=htmlspecialcharsbx($slotData['service']['NAME'])?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Услуга не указана</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="staff-schedule__row staff-schedule__row--empty">
                                <td class="staff-schedule__date">
                                    <div class="week_name"><?=$daysRus[$dayCode] ?? $dayCode?></div>
                                </td>
                                <td colspan="3" class="no_rec">
                                    Нет приёма
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Карта филиалов -->
        <?php if (!empty($mapPoints)): ?>
            <div class="staff-schedule__map-section">
                <h4 style="margin: 20px 0 15px 0; font-size: 16px; font-weight: 600;">Расположение филиалов</h4>
                <div class="staff-schedule__map" id="doctor-schedule-map" style="height: 400px; border-radius: 8px; overflow: hidden;"></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Подключение Яндекс.Карт и инициализация -->
    <?php if (!empty($mapPoints)): ?>
        <script src="https://api-maps.yandex.ru/2.1/?apikey=&lang=ru_RU" type="text/javascript"></script>
        <script>
            ymaps.ready(function () {
                const points = <?=json_encode(array_values($mapPoints), JSON_UNESCAPED_UNICODE)?>;
                if (points.length === 0) return;
                
                // Создаем карту
                const map = new ymaps.Map('doctor-schedule-map', {
                    center: [points[0].lat, points[0].lon],
                    zoom: 12,
                    controls: ['zoomControl', 'fullscreenControl']
                });

                // Добавляем метки для каждого филиала
                const placemarks = [];
                points.forEach(function (point) {
                    const placemark = new ymaps.Placemark(
                        [point.lat, point.lon],
                        {
                            balloonContentHeader: '<strong>' + point.name + '</strong>',
                            balloonContentBody: point.addr,
                            hintContent: point.name
                        },
                        {
                            preset: 'islands#redMedicalIcon',
                            iconColor: '#dc3545'
                        }
                    );
                    
                    placemarks.push(placemark);
                    map.geoObjects.add(placemark);
                });

                // Если точек больше одной, подгоняем масштаб под все точки
                if (points.length > 1) {
                    map.setBounds(map.geoObjects.getBounds(), {
                        checkZoomRange: true,
                        zoomMargin: 50
                    });
                }

                // Фильтрация карты по выбору филиала
                const shopSelect = document.getElementById('shopSelect');
                if (shopSelect) {
                    shopSelect.addEventListener('change', function () {
                        const selectedShop = this.value;
                        
                        // Скрываем/показываем строки таблицы
                        document.querySelectorAll('.staff-schedule__row[data-shop-id]')
                            .forEach(function (row) {
                                if (selectedShop === '' || row.dataset.shopId === selectedShop) {
                                    row.style.display = '';
                                } else {
                                    row.style.display = 'none';
                                }
                            });
                        
                        // Фокусируемся на выбранном филиале на карте
                        if (selectedShop) {
                            const targetPoint = points.find(p => p.id == selectedShop);
                            if (targetPoint) {
                                map.setCenter([targetPoint.lat, targetPoint.lon], 15, {
                                    duration: 500
                                });
                                
                                // Подсвечиваем соответствующую метку
                                placemarks.forEach(function(placemark, index) {
                                    if (points[index].id == selectedShop) {
                                        placemark.options.set('iconColor', '#28a745');
                                        setTimeout(() => placemark.balloon.open(), 300);
                                    } else {
                                        placemark.options.set('iconColor', '#dc3545');
                                        placemark.balloon.close();
                                    }
                                });
                            }
                        } else {
                            // Возвращаем обычный вид карты
                            placemarks.forEach(function(placemark) {
                                placemark.options.set('iconColor', '#dc3545');
                                placemark.balloon.close();
                            });
                            
                            if (points.length > 1) {
                                map.setBounds(map.geoObjects.getBounds(), {
                                    checkZoomRange: true,
                                    zoomMargin: 50
                                });
                            }
                        }
                        
                        // Обновляем rowspan для дней недели после фильтрации
                        updateRowSpans();
                    });
                }
                
                function updateRowSpans() {
                    const days = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];
                    
                    days.forEach(function(day) {
                        const dayRows = document.querySelectorAll('.staff-schedule__row:not([style*="display: none"])');
                        let dayRowsForThisDay = [];
                        let currentDayCell = null;
                        
                        dayRows.forEach(function(row) {
                            const dayCell = row.querySelector('.staff-schedule__date');
                            if (dayCell) {
                                if (currentDayCell) {
                                    currentDayCell.setAttribute('rowspan', dayRowsForThisDay.length);
                                }
                                currentDayCell = dayCell;
                                dayRowsForThisDay = [row];
                            } else if (currentDayCell) {
                                dayRowsForThisDay.push(row);
                            }
                        });
                        
                        if (currentDayCell && dayRowsForThisDay.length > 0) {
                            currentDayCell.setAttribute('rowspan', dayRowsForThisDay.length);
                        }
                    });
                }
            });
        </script>
    <?php endif; ?>

    <style>
        .staff-schedule {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .staff-schedule__top {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        .doctor-info h3 {
            margin: 0 0 15px 0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .schedule-table thead th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .staff-schedule__row {
            border-bottom: 1px solid #f1f3f4;
        }
        
        .staff-schedule__row:hover {
            background-color: #f8f9ff;
        }
        
        .staff-schedule__row--empty {
            background-color: #fafafa;
        }
        
        .staff-schedule__date {
            padding: 15px 20px;
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
            vertical-align: middle;
            text-align: center;
            min-width: 80px;
        }
        
        .week_name {
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        
        .staff-schedule__time,
        .staff-schedule__shop,
        .staff-schedule__service {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .time-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 500;
            font-size: 13px;
        }
        
        .time-badge.available {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .shop-info {
            line-height: 1.4;
        }
        
        .shop-name {
            font-weight: 500;
            color: #333;
        }
        
        .shop-address {
            font-size: 12px;
            color: #6c757d;
            margin-top: 2px;
        }
        
        .service-link {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        
        .service-link:hover {
            text-decoration: underline;
        }
        
        .no_rec {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
        }
        
        .text-muted {
            color: #6c757d;
        }
        
        .form-control {
            padding: 6px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #80bdff;
            box-shadow: 0 0 0 2px rgba(0,123,255,.25);
        }
        
        .staff-schedule__map-section {
            padding: 20px;
            border-top: 1px solid #dee2e6;
            background: #f8f9fa;
        }
        
        .staff-schedule__map-section h4 {
            color: #495057;
            margin-bottom: 15px;
        }
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .schedule-table {
                font-size: 12px;
            }
            
            .staff-schedule__date,
            .staff-schedule__time,
            .staff-schedule__shop,
            .staff-schedule__service {
                padding: 8px 10px;
            }
            
            .schedule-table thead th {
                padding: 10px 8px;
                font-size: 12px;
            }
            
            .shop-address {
                display: none;
            }
            
            .staff-schedule__map {
                height: 300px !important;
            }
        }
    </style>

<?php else: ?>
    <div class="staff-schedule">
        <div class="staff-schedule__top">
            <div class="doctor-info">
                <h3>Расписание врача <?=htmlspecialcharsbx($arDoctor['NAME'] ?? 'Врач')?></h3>
            </div>
        </div>
        <div class="staff-schedule__body">
            <div class="no_rec" style="padding: 40px; text-align: center;">
                <p>Расписание для данного врача не найдено.</p>
                <p style="color: #6c757d; font-size: 14px;">Возможно, врач не работает в текущий период или расписание не настроено.</p>
            </div>
        </div>
    </div>
<?php endif; ?>