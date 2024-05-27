<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <script>
        navigator.serviceWorker.register("sw.js");
        function requestPermission(){
            Notification.requestPermission().then((permission) =>{
                if(permission === 'granted'){
                    //TODO
                }
            });
        }
    </script>
    <button onclick="requestPermission()">Enable Notification</button>
</body>
</html>