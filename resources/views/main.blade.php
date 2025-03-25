<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Avtobus Joylashuvi</title>
    <link rel="stylesheet" href="{{asset('assets/styles.css')}}">
    <script src="https://api-maps.yandex.ru/2.1/?lang=uz_UZ" type="text/javascript"></script>
    <script src="https://hst-api.wialon.com/wsdk/script/wialon.js"></script>
</head>
<body>

<h1>Avtobuslarning Joylashuvi</h1>
<div id="map"></div>
<button onclick="initWialon()">Xarita yangilash</button>

<script>
    const API_URL = "https://hst-api.wialon.com";
    const WIALON_TOKEN = "a48df18e04335d64cb11bbb98e0d262695C748821D3DE4CB09D37BC14DDEF3F2CE4F873F";

    let map;
    let busTracks = {}; // Har bir avtobusning trayektoriyasini saqlash
    let busMarkers = {}; // Har bir avtobusning belgilarini saqlash

    // Rangi tasodifiy generatsiya qilish
    function getRandomColor() {
        return '#' + Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0');
    }

    // Xaritani yaratish va foydalanuvchi joylashuvini aniqlash
    function initMap() {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLat = position.coords.latitude;
                    const userLon = position.coords.longitude;

                    map = new ymaps.Map("map", {
                        center: [userLat, userLon],
                        zoom: 14
                    });

                    let userPlacemark = new ymaps.Placemark(
                        [userLat, userLon],
                        { balloonContent: "Sizning joylashuvingiz" },
                        { preset: "islands#redDotIcon" }
                    );

                    map.geoObjects.add(userPlacemark);
                    initWialon();
                },
                () => {
                    initWialon();
                }
            );
        } else {
            initWialon();
        }
    }

    // Wialon API orqali avtobuslarni yuklash va xaritada ko'rsatish
    function initWialon() {
        wialon.core.Session.getInstance().initSession(API_URL);

        wialon.core.Session.getInstance().loginToken(WIALON_TOKEN, function (code) {
            if (code) {
                console.log("Xatolik: Wialon tizimiga kirib bo‘lmadi!", code);
                return;
            }
            console.log("Wialon muvaffaqiyatli ulandi!");
            updateBusPositions();
            setInterval(updateBusPositions, 500); // Har 3 sekundda yangilash
        });
    }

    let asdf = true;

    // Avtobuslarning joylashuvini yangilash
    function updateBusPositions() {
        let flags = wialon.item.Item.dataFlag.base | wialon.item.Unit.dataFlag.lastMessage;
        wialon.core.Session.getInstance().updateDataFlags(
            [{ type: "type", data: "avl_unit", flags: flags, mode: 0 }],
            function (code, data) {
                if (code) {
                    console.log("Mashinalarni yuklashda xatolik!", code);
                    return;
                }

                let units = wialon.core.Session.getInstance().getItems("avl_unit");
                if (!units || units.length === 0) {
                    console.log("Hech qanday mashina topilmadi!");
                    return;
                }

                // Faqat avtobus markerlarini tozalash (trayektoriyani emas)
                for (let busId in busMarkers) {
                    map.geoObjects.remove(busMarkers[busId]);
                }
                busMarkers = {};

                let buses = []; // Global o'zgaruvchi sifatida tashqarida e'lon qilamiz

                units.forEach(unit => {
                    let pos = unit.getPosition();
                    if (pos) {
                        let busId = unit.getId();
                        let busName = unit.getName();
                        let latitude = pos.y;
                        let longitude = pos.x;

                        if(busId !== 600773019){
                            return;
                        }

                        // Avtobus ma'lumotlarini ro‘yxatga qo‘shish
                        buses.push({
                            bus_wialon_id: busId,
                            name: busName,
                            latitude: latitude,
                            longitude: longitude
                        });

                        if (!busTracks[busId]) {
                            busTracks[busId] = {
                                color: "#00FF00", // Trayektoriya rangi
                                points: []
                            };
                        }

                        // Koordinatalarni saqlash
                        busTracks[busId].points.push([latitude, longitude]);

                        // Trayektoriyani chizish
                        drawRoute(busId);

                        // Avtobus markerini yaratish va xaritaga qo‘shish
                        let placemark = new ymaps.Placemark(
                            [latitude, longitude],
                            { balloonContent: busName },
                            { preset: "islands#blueBusIcon" }
                        );
                        map.geoObjects.add(placemark);
                        busMarkers[busId] = placemark;
                    }
                });



                    {{--if (buses.length > 0 && asdf) {--}}
                    {{--    asdf=false;--}}
                    {{--    fetch("{{ route('save_road') }}", {--}}
                    {{--        method: "POST",--}}
                    {{--        headers: {--}}
                    {{--            "Content-Type": "application/json",--}}
                    {{--            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),--}}
                    {{--            "Accept": "application/json"--}}
                    {{--        },--}}
                    {{--        body: JSON.stringify({ buses }) // Ob'ekt sifatida yuborish--}}
                    {{--    })--}}
                    {{--        .then(response => response.json())--}}
                    {{--        .then(data => console.log("Barcha avtobuslar yuborildi:", data))--}}
                    {{--        .catch(error => console.error("Xatolik avtobuslarni yuborishda:", error));--}}
                    {{--}--}}

            }
        );
    }

    // Avtobusning harakat trayektoriyasini chizish
    function drawRoute(busId) {
        if (busTracks[busId].points.length < 2) return;

        let routeLine = new ymaps.Polyline(
            busTracks[busId].points,
            {},
            {
                strokeColor: busTracks[busId].color,
                strokeWidth: 4
            }
        );

        map.geoObjects.add(routeLine);
    }

    // Xarita tayyor bo'lganda ishga tushirish
    ymaps.ready(initMap);

</script>

</body>
</html>
