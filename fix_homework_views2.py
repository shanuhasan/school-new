import glob

views_path = 'resources/views/academic/homework'
for file_path in glob.glob(f'{views_path}/**/*.blade.php', recursive=True):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Remove tailwind wrappers
    content = content.replace('<div class="py-12">\n    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">\n        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">\n            <div class="p-6 text-gray-900">\n', '')
    content = content.replace('            </div>\n        </div>\n    </div>\n</div>\n</x-app-layout>', '</x-app-layout>')
    content = content.replace('@section(\'title\', \'Homework\')\n', '<x-slot name="header">\n    <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __(\'Homework\') }}</h1></div></div></div>\n</x-slot>\n')
    content = content.replace('@section(\'title\', \'Create Homework\')\n', '<x-slot name="header">\n    <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __(\'Create Homework\') }}</h1></div></div></div>\n</x-slot>\n')
    content = content.replace('@section(\'title\', \'Homework Details\')\n', '<x-slot name="header">\n    <div class="page-heading"><div class="page-heading-copy"><div><h1 class="h3 mb-1">{{ __(\'Homework Details\') }}</h1></div></div></div>\n</x-slot>\n')
    
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
print('Fixed homework views theme layout')
