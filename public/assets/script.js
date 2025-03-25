const API_URL = "https://hst-api.wialon.com";
const WIALON_TOKEN = "a48df18e04335d64cb11bbb98e0d262695C748821D3DE4CB09D37BC14DDEF3F2CE4F873F";

let map;
let busMarkers = {}; // Har bir avtobusning belgilarini saqlash

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
                defaultMap();
                initWialon();
            }
        );
    } else {
        defaultMap();
        initWialon();
    }
}

// Standart xarita
function defaultMap() {
    map = new ymaps.Map("map", {
        center: [41.556042, 60.620332],
        zoom: 14
    });

    let tatuPlacemark = new ymaps.Placemark(
        [41.556042, 60.620332],
        { balloonContent: "TATU Urganch filiali" },
        { preset: "islands#blueUniversityIcon" }
    );

    map.geoObjects.add(tatuPlacemark);
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
        sendAllBuses();
        setInterval(updateBusPositions, 500); // Har 500ms da marshrutlarni yuborish
    });
}

// Dastlab barcha avtobuslarni PHP-ga yuborish
function sendAllBuses() {
    let flags = wialon.item.Item.dataFlag.base;
    wialon.core.Session.getInstance().updateDataFlags(
        [{ type: "type", data: "avl_unit", flags: flags, mode: 0 }],
        function (code, data) {
            if (code) {
                console.log("Mashinalarni yuklashda xatolik!", code);
                return;
            }

            let units = wialon.core.Session.getInstance().getItems("avl_unit");
            if (!units || units.length === 0) {
                console.log("Hech qanday avtobus topilmadi!");
                return;
            }

            let buses = units.map(unit => ({
                id: unit.getId(),
                name: unit.getName()
            }));

            fetch("http://localhost:63343/untitled2/add_bus.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(buses)
            })
                .then(response => response.text())
                .then(data => console.log("Barcha avtobuslar yuborildi:", data))
                .catch(error => console.error("Xatolik avtobuslarni yuborishda:", error));
        }
    );
}

// Avtobuslarning joylashuvini yangilash va marshrutlarni yuborish
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
                console.log("Hech qanday avtobus topilmadi!");
                return;
            }

            let routes = [];
            for (let busId in busMarkers) {
                map.geoObjects.remove(busMarkers[busId]);
            }
            busMarkers = {};

            units.forEach(unit => {
                let pos = unit.getPosition();
                if (pos) {
                    let busId = unit.getId();
                    let busName = unit.getName();
                    let latitude = pos.y;
                    let longitude = pos.x;

                    // Avtobusning joylashuvi
                    routes.push({ id: busId, lat: latitude, lon: longitude });

                    // Xaritada avtobusni ko'rsatish
                    let placemark = new ymaps.Placemark(
                        [latitude, longitude],
                        { balloonContent: busName },
                        { preset: "islands#blueBusIcon" }
                    );
                    map.geoObjects.add(placemark);
                    busMarkers[busId] = placemark;
                }
            });
        }
    );
}

// Xarita tayyor bo'lganda ishga tushirish
ymaps.ready(initMap);
