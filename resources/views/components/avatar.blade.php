{{--
    Initials avatar: no photo storage in this app, so every person (employee,
    user) gets a deterministic colored circle instead — same name always
    gets the same color, so it stays recognizable across pages.

    Usage: <x-avatar :name="$employee->name" />
           <x-avatar :name="$employee->name" size="lg" /> (profile headers)
           <x-avatar :name="$employee->name" size="sm" /> (dense tables)
--}}
@props(['name', 'size' => 'md'])
@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');

    $palette = ['#0d9488', '#2563eb', '#7c3aed', '#ea580c', '#db2777', '#0891b2', '#4f46e5', '#16a34a'];
    $color = $palette[crc32((string) $name) % count($palette)];

    $sizeClass = match ($size) {
        'lg' => 'avatar-circle avatar-circle-lg',
        'sm' => 'avatar-circle avatar-circle-sm',
        default => 'avatar-circle',
    };
@endphp
<span {{ $attributes->merge(['class' => $sizeClass]) }} style="background-color: {{ $color }};">{{ $initials !== '' ? $initials : '?' }}</span>
