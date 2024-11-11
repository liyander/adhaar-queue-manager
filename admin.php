<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Net Cafe</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            color: #333;
        }

        h1 {
            text-align: center;
            color: #2d3b45;
            margin-top: 20px;
            font-size: 2.5rem;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            font-size: 1.2rem; 
            color: #555;
            margin-bottom: 8px;
            display: block;
        }

        input {
            width: 100%;
            padding: 15px; 
            margin-top: 8px;
            font-size: 1.1rem; 
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
        }

        button {
            padding: 15px 25px;
            background-color: #4CAF50;
            color: #fff;
            font-size: 1.2rem; 
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 15px;
            width: 100%;
        }

        button:hover {
            background-color: #45a049;
        }

        #map {
            width: 100%;
            height: 500px; 
            margin-top: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .form-group span {
            font-size: 1rem;
            color: #777;
        }
    </style>
</head>
<body>

    <h1>Add New Esevai maiyam</h1>

    <div class="container">
        <form id="add-cafe-form">
            <div class="form-group">
                <label for="name">Cafe Name:</label>
                <input type="text" id="name" required placeholder="Enter center name">
            </div>

            <div class="form-group">
                <label for="latitude">Latitude:</label>
                <input type="number" step="any" id="latitude" required placeholder="Enter latitude" readonly>
            </div>

            <div class="form-group">
                <label for="longitude">Longitude:</label>
                <input type="number" step="any" id="longitude" required placeholder="Enter longitude" readonly>
            </div>

            <div class="form-group">
                <label for="tokens">Available Tokens:</label>
                <input type="number" id="tokens" min="0" required placeholder="Enter available tokens">
            </div>

            <button type="button" onclick="addCafe()">Add Center</button>
        </form>

        <div id="map"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script>
        let map;
        let marker;
        let userMarker;

        function initMap() {

            map = L.map('map').setView([20.5937, 78.9629], 5);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    const userLatitude = position.coords.latitude;
                    const userLongitude = position.coords.longitude;

                    map.setView([userLatitude, userLongitude], 12);

                    if (userMarker) {
                        map.removeLayer(userMarker);
                    }
                    userMarker = L.marker([userLatitude, userLongitude]).addTo(map)
                        .bindPopup('You are here.')
                        .openPopup();

                    document.getElementById('latitude').value = userLatitude;
                    document.getElementById('longitude').value = userLongitude;
                }, error => {
                    console.error('Error getting location:', error);
                    alert('Unable to retrieve your location.');
                });
            } else {
                alert('Geolocation is not supported by this browser.');
            }

            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lon = e.latlng.lng;

                if (marker) {
                    map.removeLayer(marker); 
                }
                marker = L.marker([lat, lon]).addTo(map);
                marker.bindPopup(`Cafe Location: ${lat}, ${lon}`).openPopup();

                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lon;
            });
        }

        async function addCafe() {
            const name = document.getElementById('name').value;
            const latitude = parseFloat(document.getElementById('latitude').value);
            const longitude = parseFloat(document.getElementById('longitude').value);
            const available_tokens = parseInt(document.getElementById('tokens').value);

            if (!latitude || !longitude) {
                alert('Please click on the map to select a location.');
                return;
            }

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name, latitude, longitude, available_tokens })
                });
                const result = await response.json();
                alert(result.message);
                document.getElementById('add-cafe-form').reset();
                if (marker) {
                    map.removeLayer(marker); 
                }
            } catch (error) {
                console.error('Error adding cafe:', error);
            }
        }

        window.onload = initMap;
    </script>
</body>
</html>
