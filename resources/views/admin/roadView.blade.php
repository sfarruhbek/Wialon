<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yandex Marshrut Chizish</title>

    <!-- Yandex Maps API -->
    <script src="https://api-maps.yandex.ru/2.1/?apikey=YOUR_API_KEY&lang=ru_RU" type="text/javascript"></script>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        #map {
            width: 100%;
            height: 100vh;
        }
    </style>
</head>
<body>

<div id="map"></div>

<script>
    ymaps.ready(init);
    let roads = @json($roads);

    function init() {
        let myMap = new ymaps.Map("map", {
            center: [41.5610, 60.6205],
            zoom: 14
        });

        roads.forEach(function (road) {
            let coordinates = road.points.map(point => [point.latitude, point.longitude]);
            let polyline = new ymaps.Polyline(coordinates, {}, {
                strokeColor: getRandomRGBColor(), // Rangni yangiladik
                strokeWidth: 4
            });

            myMap.geoObjects.add(polyline);

            myMap.geoObjects.add(new ymaps.Placemark(coordinates[0], { balloonContent: "Boshlanish nuqtasi" }, { preset: "islands#redDotIcon" }));
            myMap.geoObjects.add(new ymaps.Placemark(coordinates[coordinates.length - 1], { balloonContent: "Tugash nuqtasi" }, { preset: "islands#greenDotIcon" }));
        });
    }

    function getRandomRGBColor() {
        let r = Math.floor(Math.random() * 256);
        let g = Math.floor(Math.random() * 256);
        let b = Math.floor(Math.random() * 256);
        return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
    }
</script>


</body>
</html>
