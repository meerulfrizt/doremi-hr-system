<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOREMi AI HR</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">

    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-xl p-8">
        <div class="flex items-center space-x-4 mb-6">
            <div class="bg-blue-600 p-3 rounded-lg">
                <span class="text-white text-2xl">🤖</span>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">DOREMi AI Assistant</h1>
                <p class="text-gray-500 text-sm">Automated HR Insights</p>
            </div>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-600 p-6 rounded-r-lg">
            <h3 class="text-blue-800 font-semibold mb-2">Analisis Kehadiran Real-Time:</h3>
            <div class="text-gray-700 leading-relaxed italic">
                {!! nl2br(e($hasilAI)) !!}
            </div>
        </div>

        <div class="mt-8 flex justify-between items-center">
            <p class="text-xs text-gray-400">Data ditarik terus dari Firestore</p>
            <a href="/admin/ai-insights" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-full transition duration-300 shadow-md">
                Refresh Analisis
            </a>
        </div>
    </div>

</body>
</html>