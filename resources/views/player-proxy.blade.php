<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        html, body { margin:0; padding:0; width:100%; height:100%; background:#000; overflow:hidden; }
        iframe, video { width:100%; height:100%; border:0; display:block; }
    </style>
</head>
<body>
@if($type === 'html')
    {!! $embed !!}
@else
    <iframe src="{{ $embed }}" allowfullscreen allow="autoplay; fullscreen; picture-in-picture" referrerpolicy="no-referrer"></iframe>
@endif
</body>
</html>
