<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Marshrutni chizish</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Turf.js/6.5.0/turf.min.js"></script>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
        }
        #map {
            height: 100vh;
            width: 100vw;
        }
        .btn {
            position: absolute;
            top: 10px;
            background: blue;
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 16px;
            cursor: pointer;
            z-index: 1000;
        }
        #saveButton {
            right: 10px;
        }
        #undoButton {
            right: 130px;
            background: red;
        }
    </style>
</head>
<body>
<button id="undoButton" class="btn" onclick="undoLastPoint()">Bekor qilish</button>
<button id="saveButton" class="btn" onclick="saveRoute()">Marshrutni saqlash</button>
<div id="map"></div>

<script>
    var map = L.map('map').setView([41.5610, 60.6205], 16); // TATU UF koordinatasi

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    var routeCoordinates = [];
    var polyline = L.polyline([], { color: 'blue' }).addTo(map);
    var markers = [];

    map.on('click', function (e) {
        var latlng = e.latlng;
        routeCoordinates.push([latlng.lat, latlng.lng]);
        polyline.addLatLng(latlng);

        // Marker qo'shish
        let marker = L.circleMarker(latlng, { radius: 4, color: 'red' }).addTo(map);
        markers.push(marker);
    });

    function undoLastPoint() {
        if (routeCoordinates.length > 0) {
            routeCoordinates.pop(); // Oxirgi nuqtani o'chiramiz
            markers.pop()?.remove(); // Oxirgi markerni o‘chiramiz

            // Yangi polyline yaratish
            polyline.setLatLngs(routeCoordinates);
        }
    }

    function generatePoints() {
        if (routeCoordinates.length < 2) {
            alert("Marshrutni saqlash uchun kamida 2 nuqta qo'shing.");
            return [];
        }

        let line = turf.lineString(routeCoordinates);
        let distance = turf.length(line, { units: 'meters' });
        let numPoints = Math.floor(distance / 3); // Har 3 metrda
        let spacedPoints = [];

        for (let i = 0; i <= numPoints; i++) {
            let pt = turf.along(line, i * 3, { units: 'meters' });
            spacedPoints.push({
                latitude: pt.geometry.coordinates[0],
                longitude: pt.geometry.coordinates[1]
            });
        }

        return spacedPoints;
    }

    function saveRoute() {
        let points = generatePoints();
        if (points.length === 0) {
            alert("Marshrut yo'q, avval nuqtalar qo'shing!");
            return;
        }

        let road_id = Math.floor(Math.random() * 1000) + 1; // Tasodifiy ID yaratish
        let csrfToken = "tM5gIpPgh5C21ZmHhRrPnUZ7aiS8PhaHLesVNbNF"; // CSRF tokenni olish

        fetch('/save-route', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken // CSRF token qo'shildi
            },
            body: JSON.stringify({ road_id: road_id, points })
        })
            .then(res => res.json())
            .then(data => {
                console.log(data); // Consolga chiqarish
                alert("Serverdan javob: " + JSON.stringify(data)); // Ekranga chiqarish
            })
            .catch(err => {
                console.error(err);
                alert("Xatolik yuz berdi. Qaytadan urinib ko'ring.");
            });
    }


</script>
</body>
</html>
