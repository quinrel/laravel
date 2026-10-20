<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quinrel SEO Dashboard</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; background: #f9fafb; color: #111827; }
        .min-h-screen { min-height: 100vh; }
        .bg-gray-50 { background-color: #f9fafb; }
        .bg-white { background-color: #ffffff; }
        .bg-blue-600 { background-color: #2563eb; }
        .bg-gray-200 { background-color: #e5e7eb; }
        .text-white { color: #ffffff; }
        .text-blue-600 { color: #2563eb; }
        .text-gray-900 { color: #111827; }
        .text-gray-700 { color: #374151; }
        .text-gray-500 { color: #6b7280; }
        .shadow { box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .rounded, .rounded-md, .rounded-lg { border-radius: 0.5rem; }
        .border { border: 1px solid #d1d5db; }
        .p-2 { padding: 0.5rem; }
        .p-4 { padding: 1rem; }
        .p-6 { padding: 1.5rem; }
        .p-8 { padding: 2rem; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mt-1 { margin-top: 0.25rem; }
        .mt-6 { margin-top: 1.5rem; }
        .mt-10 { margin-top: 2.5rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .max-w-2xl { max-width: 42rem; }
        .w-full { width: 100%; }
        .flex { display: flex; }
        .flex-col { flex-direction: column; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .justify-center { justify-content: center; }
        .gap-3 { gap: 0.75rem; }
        .gap-6 { gap: 1.5rem; }
        .space-y-4 > * + * { margin-top: 1rem; }
        .space-y-6 > * + * { margin-top: 1.5rem; }
        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 600; }
        .font-medium { font-weight: 500; }
        .text-2xl { font-size: 1.5rem; }
        .text-xl { font-size: 1.25rem; }
        .text-sm { font-size: 0.875rem; }
        input[type="text"], textarea, select { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; }
        button { cursor: pointer; border: none; font-size: 0.875rem; font-weight: 500; }
        button:disabled { opacity: 0.5; cursor: not-allowed; }
        @media (min-width: 768px) {
            .md\:flex-row { flex-direction: row; }
            .md\:w-2\/3 { width: 66.666667%; }
            .md\:w-1\/3 { width: 33.333333%; }
        }
    </style>
    @php
        $packageRoot = dirname((string) (new \ReflectionClass(\Quinrel\Laravel\QuinrelServiceProvider::class))->getFileName(), 2);
        $publishedViteManifest = public_path('vendor/quinrel/build/.vite/manifest.json');
        $publishedLegacyManifest = public_path('vendor/quinrel/build/manifest.json');
        $packageViteManifest = $packageRoot . '/public/build/.vite/manifest.json';

        $manifestPath = file_exists($publishedViteManifest)
            ? $publishedViteManifest
            : (file_exists($publishedLegacyManifest) ? $publishedLegacyManifest : (file_exists($packageViteManifest) ? $packageViteManifest : null));

        $manifest = $manifestPath ? json_decode((string) file_get_contents($manifestPath), true) : null;
        $entryFile = is_array($manifest) && isset($manifest['resources/js/app.jsx']['file'])
            ? $manifest['resources/js/app.jsx']['file']
            : null;
    @endphp
    @if($entryFile && ($manifestPath === $publishedViteManifest || $manifestPath === $publishedLegacyManifest))
        <script type="module" src="{{ asset('vendor/quinrel/build/' . $entryFile) }}"></script>
    @elseif($entryFile && $manifestPath === $packageViteManifest && file_exists($packageRoot . '/public/build/' . $entryFile))
        <script type="module">{!! file_get_contents($packageRoot . '/public/build/' . $entryFile) !!}</script>
    @else
        <script type="module" src="http://localhost:5173/resources/js/app.jsx"></script>
    @endif
</head>
<body>
    <div id="quinrel-app"></div>
</body>
</html>
