<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner</title>
    <script src="https://unpkg.com/jsqr/dist/jsQR.js"></script>
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }

        video {
            border: 2px solid black;
            margin-bottom: 20px;
        }

        button {
            margin-bottom: 20px;
            padding: 10px 20px;
            font-size: 16px;
        }

        #result {
            margin-top: 20px;
            font-size: 18px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h1>Scan kenapa lu sakit</h1>
    <video id="video" width="300" height="300" autoplay></video>
    <button id="startScan">Start Scan</button>
    <div id="result"></div>
    <canvas id="canvas" style="display:none;"></canvas>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');
        const resultDiv = document.getElementById('result');

        // Mengakses kamera depan
        navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: "environment",
                width: { ideal: 1280 }, // Mengatur lebar ideal
                height: { ideal: 720 }, // Mengatur tinggi ideal
                // Menambahkan pengaturan autofocus
                advanced: [{ torch: false, focusMode: 'continuous' }] // Jika ada, untuk mode fokus terus menerus
            }
        })
            .then(stream => {
                video.srcObject = stream;
            })
            .catch(err => {
                console.error("Error accessing the camera: ", err);
            });

        document.getElementById('startScan').addEventListener('click', () => {
            scanQRCode();
        });

        function scanQRCode() {
            canvas.width = video.videoWidth; // Set width
            canvas.height = video.videoHeight; // Set height
            context.drawImage(video, 0, 0, canvas.width, canvas.height); // Menggambar video ke canvas
            const imageData = context.getImageData(0, 0, canvas.width, canvas.height); // Mendapatkan data gambar
            const code = jsQR(imageData.data, canvas.width, canvas.height); // Menggunakan jsQR untuk mendeteksi QR Code

            if (code) {
                resultDiv.innerText = `QR Code detected: ${code.data}`; // Menampilkan hasil
            } else {
                requestAnimationFrame(scanQRCode); // Memanggil ulang fungsi scanQRCode
            }
        }
    </script>
</body>

</html>