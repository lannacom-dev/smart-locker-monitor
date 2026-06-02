@php $current = app()->getLocale(); @endphp
<div class="flex items-center gap-1 mr-2">
    <a href="{{ route('locale.switch', 'en') }}"
        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors
            {{ $current === 'en'
                ? 'bg-primary-600 text-white shadow-sm'
                : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
        🇬🇧 EN
    </a>
    <a href="{{ route('locale.switch', 'th') }}"
        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold transition-colors
            {{ $current === 'th'
                ? 'bg-primary-600 text-white shadow-sm'
                : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
        🇹🇭 TH
    </a>
</div>
