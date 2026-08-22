<aside class="flex flex-col w-64 h-screen px-4 py-8 overflow-y-auto bg-slate-900 border-r rtl:border-r-0 rtl:border-l dark:bg-gray-900 dark:border-gray-700 transition-all duration-300">
    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-2">
        <svg class="w-8 h-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21l9-5-9-5-9 5 9 5z" />
        </svg>
        <span class="text-2xl font-bold text-white tracking-tight">EduCore</span>
    </a>

    <div class="flex flex-col justify-between flex-1 mt-8">
        <nav x-data="{ academicOpen: {{ request()->routeIs('academic.*') ? 'true' : 'false' }} }">
            
            <!-- Dashboard Link -->
            <a class="flex items-center px-4 py-3 text-gray-300 transition-colors rounded-lg hover:text-white hover:bg-slate-800 {{ request()->routeIs('dashboard') ? 'bg-indigo-600/10 text-indigo-400 border-l-4 border-indigo-500' : '' }}" href="{{ route('dashboard') }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="mx-4 font-medium">Dashboard</span>
            </a>

            <!-- Academic Management Group -->
            <div class="mt-4">
                <button @click="academicOpen = !academicOpen" class="flex items-center justify-between w-full px-4 py-3 text-gray-300 transition-colors rounded-lg hover:text-white hover:bg-slate-800 {{ request()->routeIs('academic.*') ? 'text-white' : '' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span class="mx-4 font-medium">Academic</span>
                    </div>
                    <svg :class="{ 'transform rotate-180': academicOpen }" class="w-4 h-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="academicOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="mt-2 space-y-1 bg-slate-900/50 rounded-lg p-2">
                    
                    <a href="{{ route('academic.sessions.index') }}" class="block px-4 py-2 text-sm text-gray-400 rounded-md hover:bg-slate-800 hover:text-white {{ request()->routeIs('academic.sessions.*') ? 'bg-slate-800 text-white' : '' }}">
                        Sessions
                    </a>
                    
                    <a href="{{ route('academic.classes.index') }}" class="block px-4 py-2 text-sm text-gray-400 rounded-md hover:bg-slate-800 hover:text-white {{ request()->routeIs('academic.classes.*') ? 'bg-slate-800 text-white' : '' }}">
                        Classes
                    </a>
                    
                    <a href="{{ route('academic.sections.index') }}" class="block px-4 py-2 text-sm text-gray-400 rounded-md hover:bg-slate-800 hover:text-white {{ request()->routeIs('academic.sections.*') ? 'bg-slate-800 text-white' : '' }}">
                        Sections
                    </a>
                    
                    <a href="{{ route('academic.subjects.index') }}" class="block px-4 py-2 text-sm text-gray-400 rounded-md hover:bg-slate-800 hover:text-white {{ request()->routeIs('academic.subjects.*') ? 'bg-slate-800 text-white' : '' }}">
                        Subjects
                    </a>
                </div>
            </div>

            <!-- Add more groups here like Student, HR, Finance etc later -->

        </nav>
        
        <!-- School Context Block at bottom of sidebar -->
        <div class="flex items-center px-4 -mx-2 mt-8">
            <div class="px-2">
                <div class="flex items-center gap-x-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-xs">
                        {{ substr(optional(Auth::user()->currentSchool)->name ?? 'SA', 0, 2) }}
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-white truncate w-40">{{ optional(Auth::user()->currentSchool)->name ?? 'Super Admin Mode' }}</h4>
                        <p class="text-xs text-gray-400">{{ Auth::user()->roles->pluck('name')->first() ?? 'User' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>
