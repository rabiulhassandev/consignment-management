@props(['icon' => 'inbox', 'title' => 'Nothing here yet', 'description' => null])

<div class="flex flex-col items-center justify-center gap-2 px-6 py-12 text-center">
    <div class="flex size-12 items-center justify-center rounded-full bg-gray-100">
        <x-icon :name="$icon" class="size-6 text-gray-400" />
    </div>
    <p class="text-sm font-semibold text-gray-900">{{ $title }}</p>
    @if ($description)
        <p class="max-w-sm text-sm text-gray-500">{{ $description }}</p>
    @endif
    @if (trim($slot))
        <div class="mt-2">{{ $slot }}</div>
    @endif
</div>
