<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avtobus Joylashuvi</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
</head>
<style>
    body {
        font-family: Arial, sans-serif;
        text-align: center;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
    }

    h1 {
        color: #333;
    }

    #map {
        width: 100%;
        height: 100vh;
        margin: 0;
        padding: 0;
    }

    button {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 16px;
        cursor: pointer;
        border-radius: 5px;
    }

    button:hover {
        background-color: #0056b3;
    }

</style>
<body>
<div id="map"></div>
<script>


    let map;
    let busTracks = {}; // Har bir avtobusning trayektoriyasini saqlash
    let busMarkers = {}; // Har bir avtobusning belgilarini saqlash

    let roads = @json($roads);
    let busStops = @json($busstops);
    let busStopObjects = [];



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

                    setInterval(updateBusPositions, 3000);

                    busStops.forEach(function (stop) {
                        let placemark = new ymaps.Placemark(
                            [stop.latitude, stop.longitude],
                            { balloonContent: `<strong>Bekat</strong><br>ID: ${stop.id}<br>Koordinata: ${stop.latitude}, ${stop.longitude}` },
                            { iconColor: "deeppink" }
                        );

                        map.geoObjects.add(placemark);
                        busStopObjects.push({ id: stop.id, placemark: placemark });

                    });

                    roads.forEach(function (road) {
                        let coordinates = road.points.map(point => [point.latitude, point.longitude]);
                        let polyline = new ymaps.Polyline(coordinates, {}, {
                            strokeColor: getRandomRGBColor(),
                            strokeWidth: 4
                        });

                        map.geoObjects.add(polyline);

                        // Har bir nuqtaga Yandex Map ikonasini qo'shish va Road Point ID ko'rsatish
                        // road.points.forEach(point => {
                        //     let placemark = new ymaps.Placemark([point.latitude, point.longitude], {
                        //         balloonContent: `<strong>Road Point id:</strong> ${point.id}<br>status: ${point.status}` // Road Point ID ni ko'rsatish
                        //     }, {
                        //         preset: "islands#blueCircleIcon"
                        //     });
                        //
                        //     map.geoObjects.add(placemark);
                        // });

                        // Boshlanish va tugash nuqtalariga alohida belgilar qo'shish
                        map.geoObjects.add(new ymaps.Placemark([road.points[0].latitude, road.points[0].longitude], {
                            balloonContent: "<strong>Boshlanish nuqtasi</strong><br><strong>Road Point id:</strong> " + road.points[0].id
                        }, { preset: "islands#greenDotIcon" }));

                        map.geoObjects.add(new ymaps.Placemark([road.points[road.points.length - 1].latitude, road.points[road.points.length - 1].longitude], {
                            balloonContent: "<strong>Tugash nuqtasi</strong><br><strong>Road Point id:</strong> " + road.points[road.points.length - 1].id
                        }, { preset: "islands#redDotIcon" }));
                    });


                },
                () => {
                    defaultMap();
                    setInterval(updateBusPositions, 3000);
                }
            );
        } else {
            defaultMap();
            setInterval(updateBusPositions, 3000);
        }




        let RoadPoints=[];


        roads[0]['points'].forEach(function (val){
            RoadPoints.push({latitude: Number(val.latitude), longitude: Number(val.longitude)});
        });

        console.log("Umumiy yo'l: " + getDistanceBetweenPoints(RoadPoints,RoadPoints[0],RoadPoints[RoadPoints.length-1]).toFixed(2) + " metr");


        RoadPoints = [];
        roads[1]['points'].forEach(function (val){
            RoadPoints.push({latitude: Number(val.latitude), longitude: Number(val.longitude)});
        });

        console.log("Umumiy yo'l: " + getDistanceBetweenPoints(RoadPoints,RoadPoints[0],RoadPoints[RoadPoints.length-1]).toFixed(2) + " metr");
    }
    function getRandomRGBColor() {
        let r = Math.floor(Math.random() * 256);
        let g = Math.floor(Math.random() * 256);
        let b = Math.floor(Math.random() * 256);
        return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
    }

    function defaultMap() {
        // map = new ymaps.Map("map", {
        //     center: [41.556042, 60.620332],
        //     zoom: 14
        // });
        //
        // let tatuPlacemark = new ymaps.Placemark(
        //     [41.556042, 60.620332],
        //     { balloonContent: "TATU Urganch filiali" },
        //     { preset: "islands#blueUniversityIcon" }
        // );
        //
        // map.geoObjects.add(tatuPlacemark);
    }

    function updateBusPositions() {
        fetch(`{{route("all-buses")}}`)
            .then(response => response.json())
            .then(data => {
                if (!data || data.length === 0) {
                    console.log("Hech qanday mashina topilmadi!");
                    return;
                }


                for (let busId in busMarkers) {
                    map.geoObjects.remove(busMarkers[busId]);
                }
                busMarkers = {};

                data.forEach(bus => {


                    let { busId, name, latitude, longitude } = bus;

                    if (!busTracks[busId]) {
                        busTracks[busId] = {
                            color: getRandomColor(),
                            points: []
                        };
                    }

                    // busTracks[busId].points.push([latitude, longitude]);
                    //
                    // drawRoute(busId);


                    let placemark = new ymaps.Placemark(
                        [latitude, longitude],
                        {
                            balloonContent: `ID: ${bus['id']}<br>Name: ${name}`
                        },
                        {
                            iconLayout: 'default#image',
                            iconImageHref: '{{asset('assets/img/bus.png')}}',
                            iconImageSize: [25, 25],
                            iconImageOffset: [-12, -12]
                        }
                    );


                    map.geoObjects.add(placemark);
                    busMarkers[busId] = placemark;
                });
            })
            .catch(error => {
                console.log("Ma'lumotlarni olishda xatolik!", error);
            });
    }

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

    ymaps.ready(initMap);

</script>
<script>

    function findClosestPointOnRoute(routeCoords, station) {
        var minDistance = Infinity;
        var closestPoint = null;

        for (var i = 0; i < routeCoords.length - 1; i++) {
            var projected = getProjectedPoint(routeCoords[i], routeCoords[i + 1], station);
            var distance = ymaps.coordSystem.geo.getDistance([station.latitude, station.longitude], [projected.latitude, projected.longitude]);

            if (distance < minDistance) {
                minDistance = distance;
                closestPoint = {latitude: projected.latitude, longitude: projected.longitude};
            }
        }

        return closestPoint;
    }

    function getProjectedPoint(A, B, P) {
        var Ax = A.latitude, Ay = A.longitude;
        var Bx = B.latitude, By = B.longitude;
        var Px = P.latitude, Py = P.longitude;

        var ABx = Bx - Ax;
        var ABy = By - Ay;
        var APx = Px - Ax;
        var APy = Py - Ay;

        var AB_AB = ABx * ABx + ABy * ABy;
        var AB_AP = ABx * APx + ABy * APy;
        var t = AB_AP / AB_AB;

        if (t < 0) t = 0;
        if (t > 1) t = 1;

        return {latitude: Ax + t * ABx, longitude: Ay + t * ABy};
    }

    function getDistanceBetweenPoints(routeCoords, startPoint, endPoint) {
        var totalDistance = 0;
        var counting = false;

        for (var i = 0; i < routeCoords.length - 1; i++) {
            var segment = [routeCoords[i], routeCoords[i + 1]];
            var projectedStart = findClosestPointOnRoute(segment, startPoint);
            var projectedEnd = findClosestPointOnRoute(segment, endPoint);

            if (!counting && JSON.stringify(projectedStart) === JSON.stringify(startPoint)) {
                counting = true;
                totalDistance += ymaps.coordSystem.geo.getDistance([routeCoords[i].latitude, routeCoords[i].longitude], [startPoint.latitude, startPoint.longitude]);
            }

            if (counting) {
                if (JSON.stringify(projectedEnd) === JSON.stringify(endPoint)) {
                    totalDistance += ymaps.coordSystem.geo.getDistance([routeCoords[i].latitude, routeCoords[i].longitude], [endPoint.latitude, endPoint.longitude]);
                    break;
                } else {
                    totalDistance += ymaps.coordSystem.geo.getDistance([routeCoords[i].latitude, routeCoords[i].longitude], [routeCoords[i + 1].latitude, routeCoords[i + 1].longitude]);
                }
            }
        }

        return totalDistance;
    }
</script>

</body>
</html>
