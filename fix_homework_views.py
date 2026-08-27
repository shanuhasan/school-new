import os
import glob

views_path = 'resources/views/academic/homework'
for file_path in glob.glob(f'{views_path}/**/*.blade.php', recursive=True):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Replace @extends('layouts.adminhmd') and @section('content') with <x-app-layout> and <x-slot name="header">
    # Actually, let's just replace @extends with <x-app-layout> etc.
    content = content.replace("@extends('layouts.adminhmd')", "<x-app-layout>")
    content = content.replace("@section('content')", "<div class=\"py-12\">\n    <div class=\"max-w-7xl mx-auto sm:px-6 lg:px-8\">\n        <div class=\"bg-white overflow-hidden shadow-sm sm:rounded-lg\">\n            <div class=\"p-6 text-gray-900\">")
    content = content.replace("@endsection", "            </div>\n        </div>\n    </div>\n</div>\n</x-app-layout>")
    
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
print("Done fixing homework views.")
