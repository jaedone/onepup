<div class="mb-12">
    <div class="mb-6 flex items-center gap-4">
        <div class="h-px flex-1 bg-[#800000]/30"></div>
        <h3 class="font-display text-2xl font-bold text-[#800000]">{{ $title }}</h3>
        <div class="h-px flex-1 bg-[#800000]/30"></div>
    </div>

    @if($links->count())
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach($links as $link)
                <a
                    href="{{ $link->url }}"
                    target="_blank"
                    class="group relative rounded-[28px] border p-6 shadow-xl bg-white transition hover:-translate-y-1 hover:shadow-2xl
                    {{ $official
                        ? 'border-[#800000]/40 hover:border-[#800000] hover:bg-[#800000]'
                        : 'border-[#DAA520]/60 hover:border-[#DAA520] hover:bg-[#DAA520]'
                    }}"
                >
                    <div class="mb-2 flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <h4 class="text-lg font-bold text-[#800000] group-hover:text-white break-words [word-break:break-word]">
                                {{ $link->title }}
                        </h4>
                        </div>

                        <span class="text-xs px-2 py-1 rounded bg-gray-200">

                        {{ match($link->type) {
                        'file' => 'File',
                        'website' => 'Website',
                        'facebook_page' => 'Page',
                        'community' => 'Community',
                        'subreddit' => 'Subreddit'
                        } }}
                        </span>
                    </div>

                    <p class="text-sm leading-6 text-neutral-600 group-hover:text-white/90">
                        {{ $link->description }}
                    </p>
                </a>
            @endforeach
        </div>
    @else
        <p class="text-center text-neutral-500">No results found in this section.</p>
    @endif
</div>