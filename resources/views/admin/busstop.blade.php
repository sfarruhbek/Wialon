<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yandex Bus Stop Editor</title>
    <script src="https://api-maps.yandex.ru/2.1/?apikey=YOUR_API_KEY&lang=ru_RU" type="text/javascript"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        #map-container {
            position: relative;
            width: 100%;
            height: 100vh;
        }
        #map {
            width: 100%;
            height: 100%;
        }
        #buttonContainer {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: rgba(255, 255, 255, 0.8);
            padding: 10px;
            border-radius: 5px;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            margin: 5px;
        }
    </style>
    <script>
        let roads = @json($roads);
        let busStops = @json($busstops);
        let myMap;
        let busStopObjects = [];
        let newBusStops=[];

        function init() {
            myMap = new ymaps.Map("map", {
                center: [41.5505, 60.6327],
                zoom: 15
            });

            // 🚏 **Bus Stops** (Bekatlar) - Pushti rangda
            busStops.forEach(function (stop) {
                let placemark = new ymaps.Placemark(
                    [stop.latitude, stop.longitude],
                    { balloonContent: `<strong>Bekat</strong><br>Koordinata: ${stop.latitude}, ${stop.longitude}` },
                    { draggable: true, iconColor: "deeppink" }
                );

                myMap.geoObjects.add(placemark);
                busStopObjects.push({ id: stop.id, placemark: placemark });

                placemark.events.add("dragend", function () {
                    let newCoords = placemark.geometry.getCoordinates();
                });
            });

            // 🛣 **Road** (Yo‘llar) - Tasodifiy rang bilan
            roads.forEach(function (road) {
                let coordinates = road.points.map(point => [point.latitude, point.longitude]);
                let polyline = new ymaps.Polyline(coordinates, {}, {
                    strokeColor: getRandomRGBColor(),
                    strokeWidth: 4
                });

                myMap.geoObjects.add(polyline);

                myMap.geoObjects.add(new ymaps.Placemark(coordinates[0], { balloonContent: "Boshlanish nuqtasi" }, { preset: "islands#greenDotIcon" }));
                myMap.geoObjects.add(new ymaps.Placemark(coordinates[coordinates.length - 1], { balloonContent: "Tugash nuqtasi" }, { preset: "islands#redDotIcon" }));
            });

            // 🖱 Xarita bosilganda yangi bekat qo‘shish
            myMap.events.add('click', function (e) {
                let coords = e.get('coords');

                let placemark = new ymaps.Placemark(
                    coords,
                    { balloonContent: `<strong>Bekat</strong><br>Koordinata: ${coords}` },
                    { draggable: true, iconColor: "#007bff" }
                );

                myMap.geoObjects.add(placemark);
                newBusStops.push({ latitude: coords[0], longitude: coords[1] });

                placemark.events.add("dragend", function () {
                    let newCoords = placemark.geometry.getCoordinates();
                });
            });
        }

        // 🎨 Tasodifiy yo‘l rangini yaratish
        function getRandomRGBColor() {
            let r = Math.floor(Math.random() * 256);
            let g = Math.floor(Math.random() * 256);
            let b = Math.floor(Math.random() * 256);
            return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
        }

        // ✅ **Bekatlarni yangilash**
        function updateBusStops() {
            let updatedBusStops = busStopObjects.map(obj => {
                let newCoords = obj.placemark.geometry.getCoordinates();
                return { id: obj.id, latitude: newCoords[0], longitude: newCoords[1] };
            });

            let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('/update-bus-stops', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ busStops: updatedBusStops })
            })
                .then(response => response.json())
                .then(data => {
                    //
                })
                .catch(error => console.error("Xatolik:", error));
        }

        function saveBusStops() {
            let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('/save-bus-stops', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ busStops: newBusStops })
            })
                .then(response => response.json())
                .then(data => {
                    //
                })
                .catch(error => console.error("Xatolik:", error));
        }

        ymaps.ready(init);
    </script>


</head>
<body>
<div id="map-container">
    <div id="map"></div>
    <div id="buttonContainer">
        <button onclick="saveBusStops()">Saqlash</button>
        <button onclick="updateBusStops()">Yangilash</button>
    </div>
</div>
</body>
</html>
