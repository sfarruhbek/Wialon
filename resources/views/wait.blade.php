<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avtobus Ma'lumotlari</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        table { width: 50%; margin: 20px auto; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
<h1>Avtobus Ma'lumotlari</h1>
<table>
    <thead>
    <tr>
        <th>Yo'nalish</th>
        <th>Avtobus</th>
        <th>Vaqt (sekund)</th>
        <th>Masofa (metr)</th>
    </tr>
    </thead>
    <tbody id="busData"></tbody>
</table>
<script>
    async function updateData() {
        try {
            let response = await fetch('/api/wait?id=51');
            let jsonData = await response.json();

            let tableBody = document.getElementById("busData");
            tableBody.innerHTML = "";

            jsonData.forEach(data => {
                console.log(data);
                let row = `<tr>
                        <td>${data.route}</td>
                        <td>${data.bus}</td>
                        <td>${data.time}</td>
                        <td>${data.distance.toFixed(2)}</td>
                    </tr>`;
                tableBody.innerHTML += row;
            });
        } catch (error) {
            console.error("Ma'lumotlarni olishda xatolik:", error);
        }
    }

    updateData();
    setInterval(updateData, 1000);
</script>
</body>
</html>
