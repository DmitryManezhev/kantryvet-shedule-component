<!-- DEBUG -->
<pre style="background:#f5f5f5;padding:10px;font-size:12px;overflow:auto;">
$schedule:
<?= htmlspecialchars(print_r($schedule, true)) ?>

$shops:
<?= htmlspecialchars(print_r($shops, true)) ?>

$services:
<?= htmlspecialchars(print_r($services, true)) ?>
</pre>
<!-- /DEBUG --><?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$daysRus = [
    'MONDAY'    => 'Понедельник',
    'TUESDAY'   => 'Вторник',
    'WEDNESDAY' => 'Среда',
    'THURSDAY'  => 'Четверг',
    'FRIDAY'    => 'Пятница',
    'SATURDAY'  => 'Суббота',
    'SUNDAY'    => 'Воскресенье',
];

/* ---------- соберём уникальные филиалы данного врача ---------- */
$doctorShops = [];
foreach ($schedule as $daySlots) {
    foreach ($daySlots as $slot) {
        $shopId = $slot['SHOP_ID'];
        if (!isset($doctorShops[$shopId]) && isset($shops[$shopId])) {
            $doctorShops[$shopId] = $shops[$shopId];
        }
    }
}
?>


<?php if (!empty($schedule)): ?>
    <div class="doctor-schedule" id="doctor-schedule-block">
        <h3>Расписание врача <?=htmlspecialcharsbx($arDoctor['NAME'])?></h3>

        <!-- Выбор филиала -->
        <?php if (count($doctorShops) > 1): ?>
            <div class="doctor-schedule__shop-select-wrapper" style="margin-bottom: 20px;">
                <label for="shopSelect">Филиал:</label>
                <select id="shopSelect" class="form-control" style="max-width: 300px;">
                    <?php foreach ($doctorShops as $shop): ?>
                        <option value="<?=$shop['ID']?>">
                            <?=htmlspecialcharsbx($shop['NAME'])?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <!-- Таблица расписания -->
        <div class="doctor-schedule__table">
            <?php foreach ($schedule as $dayCode => $slots): ?>
                <div class="doctor-schedule__day" data-day="<?=$dayCode?>">
                    <div class="doctor-schedule__day-name"><?=$daysRus[$dayCode] ?? $dayCode?></div>

                    <?php if (!empty($slots)): ?>
                        <ul class="doctor-schedule__slots">
                            <?php foreach ($slots as $slot): ?>
                                <?php
                                $service = $services[$slot['SERVICE_ID']] ?? null;
                                $shop    = $shops[$slot['SHOP_ID']]      ?? null;
                                ?>
                                <li class="doctor-schedule__slot" data-shop-id="<?=$slot['SHOP_ID']?>">
                                    <span class="time"><?=htmlspecialcharsbx($slot['WORK_TIME'])?></span>

                                    <?php if ($service): ?>
                                        <span class="service"><?=htmlspecialcharsbx($service['NAME'])?></span>
                                    <?php endif; ?>

                                    <!-- Название и адрес филиала -->
                                    <?php if ($shop): ?>
                                        <span class="shop">
                                            <?=htmlspecialcharsbx($shop['NAME'])?>
                                            <?php if ($shop['PROPERTY_ADDRESS_VALUE']): ?>
                                                (<?=htmlspecialcharsbx($shop['PROPERTY_ADDRESS_VALUE'])?>)
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="doctor-schedule__no-slots">Нет свободных слотов</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Карта -->
        <?php
        $mapPoints = [];
        foreach ($doctorShops as $shop) {
            if (!empty($shop['PROPERTY_COORDINATES_VALUE'])) {
                $coords = explode(',', $shop['PROPERTY_COORDINATES_VALUE']);
                if (count($coords) === 2) {
                    $mapPoints[$shop['ID']] = [
                        'name' => $shop['NAME'],
                        'addr' => $shop['PROPERTY_ADDRESS_VALUE'] ?? '',
                        'lat'  => trim($coords[0]),
                        'lon'  => trim($coords[1]),
                    ];
                }
            }
        }
        ?>

        <?php if (!empty($mapPoints)): ?>
            <div class="doctor-schedule__map" id="doctor-schedule-map" style="height: 400px; margin-top: 20px;"></div>

            <script>
                ymaps.ready(function () {
                    const points = <?=json_encode(array_values($mapPoints), JSON_UNESCAPED_UNICODE)?>;
                    const map = new ymaps.Map('doctor-schedule-map', {
                        center: [points[0].lat, points[0].lon],
                        zoom: 10,
                        controls: ['zoomControl']
                    });

                    points.forEach(function (pt) {
                        map.geoObjects.add(new ymaps.Placemark(
                            [pt.lat, pt.lon],
                            {
                                balloonContentHeader: pt.name,
                                balloonContentBody: pt.addr
                            },
                            { preset: 'islands#icon', iconColor: '#0095b6' }
                        ));
                    });

                    /* ---------- фильтрация слотов и маркеров по выбору филиала ---------- */
                    const shopSelect = document.getElementById('shopSelect');
                    if (shopSelect) {
                        shopSelect.addEventListener('change', function () {
                            const selectedShop = this.value;
                            // скрываем слоты
                            document.querySelectorAll('.doctor-schedule__slot[data-shop-id]')
                                .forEach(function (li) {
                                    li.style.display = li.dataset.shopId === selectedShop ? '' : 'none';
                                });
                            // переносим центр карты на выбранный филиал
                            const target = points.find(p => p.id == selectedShop);
                            if (target) map.setCenter([target.lat, target.lon], 14);
                        });
                    }
                });
            </script>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="doctor-schedule__empty">Расписание недоступно</div>
<?php endif; ?>

<style>
.doctor-schedule__table {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}
.doctor-schedule__day {
    flex: 1 1 250px;
    border: 1px solid #eee;
    padding: 15px;
    border-radius: 8px;
    background: #fafafa;
}
.doctor-schedule__day-name {
    font-weight: 600;
    margin-bottom: 10px;
    font-size: 16px;
}
.doctor-schedule__slots {
    list-style: none;
    padding: 0;
    margin: 0;
}
.doctor-schedule__slot {
    margin-bottom: 6px;
    font-size: 14px;
}
.doctor-schedule__slot .time { font-weight: 500; }
.doctor-schedule__slot .shop { color: #007bff; margin-left: 6px; }
.doctor-schedule__no-slots { color: #888; }
</style>