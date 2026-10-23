<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-lg font-medium">
                        {{ __('Signed in as :nama', ['nama' => auth()->user()->nama]) }}
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Role: :role', ['role' => ucfirst(auth()->user()->role->value)]) }}
                    </p>

                    <form method="POST" action="{{ route('logout') }}" class="mt-6">
                        @csrf

                        <x-primary-button>
                            {{ __('Log out') }}
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
