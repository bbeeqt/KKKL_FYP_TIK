<?php
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KKKL Group - Book Bus Ticket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="bg-[#bc0000] text-white p-6">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 border-2 border-white rounded-full flex items-center justify-center font-bold italic text-xl">K</div>
                <h1 class="text-2xl font-bold tracking-widest uppercase">KKKL Group</h1>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto mt-10 p-8 bg-white shadow-lg rounded-lg border-t-4 border-[#bc0000]">
        <h2 class="text-center text-xl font-bold mb-8 bg-[#bc0000] text-white py-2">Book Bus Ticket</h2>
        
        <form action="search_results.php" method="GET" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Depart From</label>
                    <select name="depart_from" class="w-full border p-3 outline-none focus:border-red-500 rounded">
                        <option value="">Select a Departure Point</option>
                        <option value="Johor Bahru">Johor Bahru</option>
                        <option value="Muar">Muar</option>
                        <option value="Batu Pahat">Batu Pahat</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Arrive To</label>
                    <select name="arrive_to" class="w-full border p-3 outline-none focus:border-red-500 rounded">
                        <option value="">Select Destination</option>
                        <option value="Muar">Muar</option>
                        <option value="Ipoh">Ipoh</option>
                        <option value="Kuala Lumpur (TBS)">Kuala Lumpur (TBS)</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Departure Date</label>
                    <input type="date" name="travel_date" class="w-full border p-3 outline-none focus:border-red-500 rounded" value="2026-05-12">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-[#bc0000] text-white px-10 py-3 font-bold uppercase hover:bg-red-800 transition-all shadow-md flex items-center gap-2">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</body>
</html>