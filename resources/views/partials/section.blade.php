<section id="{{ $id }}" class="scroll-mt-28 mx-auto max-w-7xl px-6 py-1">
    <h2 class="mb-8 text-center font-display text-4xl font-black text-[#800000]">
        {{ $title }}
    </h2>

    @if(($groups['official'] ?? collect())->count())
        @include('partials.link-group', [
            'title' => 'Official ' . $title,
            'links' => $groups['official'],
            'official' => true
        ])
    @endif

    @if(($groups['unofficial'] ?? collect())->count())
        @include('partials.link-group', [
            'title' => 'Unofficial ' . $title,
            'links' => $groups['unofficial'],
            'official' => false
        ])
    @endif
</section>