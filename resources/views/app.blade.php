<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holvek Dashboard</title>
    @if(file_exists(public_path('vendor/holvek/build/manifest.json')))
        @php
            $manifest = json_decode(file_get_contents(public_path('vendor/holvek/build/manifest.json')), true);
        @endphp
        <script type="module" src="{{ asset('vendor/holvek/build/' . $manifest['resources/js/app.jsx']['file']) }}"></script>
    @else
        <script type="module" src="http://localhost:5173/resources/js/app.jsx"></script>
    @endif
</head>
<body>
    <div id="holvek-app"></div>
</body>
</html>
