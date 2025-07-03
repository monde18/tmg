<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Progress</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 50px; }
        .progress-container { max-width: 600px; margin: auto; }
        #progressBar { transition: width 0.3s; }
    </style>
</head>
<body>
    <div class="progress-container">
        <h3>Uploading CSV...</h3>
        <div class="progress">
            <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
        </div>
        <p id="status">Processing...</p>
    </div>

    <script>
        function updateProgress(percentage) {
            const progressBar = document.getElementById('progressBar');
            const status = document.getElementById('status');
            progressBar.style.width = percentage + '%';
            progressBar.setAttribute('aria-valuenow', percentage);
            progressBar.innerText = percentage.toFixed(2) + '%';
            if (percentage >= 100) {
                status.innerText = 'Upload complete! Redirecting...';
                setTimeout(() => window.location.href = 'index.php', 1000);
            }
        }

        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = 'upload.php';
        document.body.appendChild(iframe);
    </script>
</body>
</html>