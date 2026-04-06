<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnePUP – PUP Resources, Files, and Links</title>
    <meta name="description" content="OnePUP is a centralized directory for Polytechnic University of the Philippines (PUP) resources, including files, websites, Facebook pages, and student communities.">
    <meta name="keywords" content="PUP, Polytechnic University of the Philippines, PUP files, PUP resources, PUP websites, PUP students">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://onepup.up.railway.app/">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Assistant:wght@400;500;600;700;800&family=Arimo:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    {{ $slot }}

    @livewireScripts
</body>
</html>
