<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Net Cafe Locator</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }

        header {
            background-color: #2d3b45;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
        }

        h1 {
            font-size: 2rem;
            margin: 0;
        }

        .admin-button {
            background-color: #ff6347;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 1rem;
        }

        .admin-button:hover {
            background-color: #e5533c;
        }

        #map {
            width: 100%;
            height: 400px;
            margin-top: 20px;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
        }

        .cafe-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-top: 20px;
        }

        .cafe-item {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            width: 250px;
            text-align: center;
            transition: transform 0.3s;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .cafe-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }

        .cafe-item h3 {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
        }

        .cafe-item p {
            margin: 10px 0;
            color: #777;
        }

        .cafe-item button {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 1rem;
        }

        .cafe-item button:hover {
            background-color: #45a049;
        }

        .no-tokens {
            background-color: #ff4d4d;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .cafe-list p {
            font-size: 1rem;
            color: #555;
        }

        .admin-button-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>

    <header>
        <h1>Aadhaar Queue management System</h1>
        <div class="admin-button-container">
            <button class="admin-button" onclick="window.location.href='admin.php'">Admin Page</button>
        </div>
    </header>

    <div class="container">
        <div id="map"></div>
        <div class="cafe-list" id="cafe-list"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <script>
        let map;
        let markers = [];
        let currentLocationMarker;

        function initMap() {
            map = L.map('map');

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const { latitude, longitude } = position.coords;
                        map.setView([latitude, longitude], 13);
                        addCurrentLocationMarker(latitude, longitude);
                    },
                    (error) => {
                        console.error('Error getting current location:', error);
                        map.setView([40.7128, -74.0060], 5); 
                    }
                );
            } else {
                map.setView([40.7128, -74.0060], 5);
            }

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            fetchCafes();
        }

        function addCurrentLocationMarker(latitude, longitude) {
            currentLocationMarker = L.marker([latitude, longitude], { icon: L.icon({
                iconUrl: 'https://unpkg.com/leaflet@1.9.3/dist/images/marker-icon.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
            }) }).addTo(map);
            currentLocationMarker.bindPopup('Your current location');
        }

        async function fetchCafes() {
            try {
                const response = await fetch('api.php');
                const cafes = await response.json();
                displayCafes(cafes);
                plotCafesOnMap(cafes);
            } catch (error) {
                console.error('Error fetching cafes:', error);
            }
        }

        function displayCafes(cafes) {
            const list = document.getElementById('cafe-list');
            list.innerHTML = '';
            cafes.forEach(cafe => {
                const div = document.createElement('div');
                div.classList.add('cafe-item');
                div.innerHTML = `
                    <h3>${cafe.name}</h3>
                    <p>Available Tokens: <span id="tokens-count-${cafe.id}">${cafe.available_tokens}</span></p>
                    <button onclick="bookToken(${cafe.id}, this)">Book Token</button>
                    ${cafe.available_tokens <= 0 ? '<p class="no-tokens">No tokens available</p>' : ''}
                `;
                list.appendChild(div);
            });
        }

        function plotCafesOnMap(cafes) {
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];

            cafes.forEach(cafe => {
                const marker = L.marker([parseFloat(cafe.latitude), parseFloat(cafe.longitude)]).addTo(map);
                const popupContent = `
                    <b>${cafe.name}</b><br>
                    Available Tokens: <span id="tokens-count-${cafe.id}">${cafe.available_tokens}</span><br>
                    <button onclick="bookToken(${cafe.id}, this)">Book Token</button>
                `;
                marker.bindPopup(popupContent);
                marker.options.cafeId = cafe.id; 
                markers.push(marker);
            });
        }

        async function bookToken(cafeId, buttonElement) {
            try {
                const availableTokensElement = document.getElementById(`tokens-count-${cafeId}`);
                let availableTokens = parseInt(availableTokensElement.innerText);

                if (availableTokens <= 0) {
                    alert('No tokens available for this cafe.');
                    return;
                }

                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cafeId, action: 'book_token' })
                });
                const result = await response.json();

                if (result.status === 'success') {
                    alert('Token booked successfully!');

                    availableTokens--;

                    availableTokensElement.innerText = availableTokens;

                    const cafeListTokenElement = document.querySelector(`#cafe-list button[onclick*="${cafeId}"]`).previousElementSibling;
                    cafeListTokenElement.innerText = `Available Tokens: ${availableTokens}`;

                    fetchCafes();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error booking token:', error);
            }
        }

        initMap();
    </script>
</body>
</html>
