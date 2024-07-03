<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="{{route('file.store')}}" method="post">
        @csrf
        <input type="file" name="image" id="">
        <input type="submit" value="Valider">
    </form>
</body>
</html>