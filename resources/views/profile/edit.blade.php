<x-app-layout>
    <x-slot name="header">
        <div class="page-heading">
            <div class="page-heading-copy">
                <div><h1 class="h3 mb-1">{{ __('Profile') }}</h1></div>
            </div>
        </div>
    </x-slot>

    <div class="row g-4">
        <div class="col-12 col-md-8 mx-auto">
            <div class="panel mb-4">
                <div class="panel-body p-4">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="panel mb-4">
                <div class="panel-body p-4">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="panel">
                <div class="panel-body p-4">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
