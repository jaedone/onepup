<?php

use App\Models\Link;
use App\Models\Suggestion;
use Livewire\Component;

new class extends Component
{
    public string $message = '';
    public bool $wants_notification = false;
    public string $email = '';

    protected function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:5'],
            'wants_notification' => ['boolean'],
            'email' => [
                $this->wants_notification ? 'required' : 'nullable',
                'nullable',
                'email',
            ],
        ];
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    protected function messages(): array
    {
        return [
            'message.min' => 'Please write more than 4 characters before submitting.',
            'message.required' => 'This field cannot be empty.',
            'email.required' => 'Please enter your email if you want to be notified.',
            'email.email' => 'Please enter a valid email address, like name@example.com.',
        ];
    }

    public function submit()
    {
        $this->validate();

        Suggestion::create([
            'message' => $this->message,
            'wants_notification' => $this->wants_notification,
            'email' => $this->email ?: null,
        ]);

        $this->reset(['message', 'wants_notification', 'email']);

        session()->flash('success', 'Thank you! Your suggestion has been submitted.');
    }

    public function getSearchableLinksProperty()
    {
        return Link::where('is_active', true)
            ->select(['id', 'title', 'description', 'url', 'type', 'is_official', 'category'])
            ->orderBy('title')
            ->get();
    }

    public function getGroupedLinksProperty()
    {
        return Link::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get()
            ->unique('url')
            ->values()
            ->groupBy(['type', 'category']);
    }
    // public function getLinkStatsProperty()
    // {
    //     return Link::where('is_active', true)
    //         ->selectRaw("
    //             COUNT(*) as total,
    //             SUM(CASE WHEN type = 'file' THEN 1 ELSE 0 END) as files,
    //             SUM(CASE WHEN type = 'website' THEN 1 ELSE 0 END) as websites,
    //             SUM(CASE WHEN type = 'facebook_page' THEN 1 ELSE 0 END) as pages,
    //             SUM(CASE WHEN type = 'community' THEN 1 ELSE 0 END) as communities,
    //             SUM(CASE WHEN type = 'subreddit' THEN 1 ELSE 0 END) as subreddits
    //         ")
    //         ->first();
    // }

};
?>

<div
    x-data="{
        showStickySearch: false,
        activeSection: 'home',
        search: '',
        allLinks: {{ Js::from($this->searchableLinks) }},
        showDropdown: false,
        highlightedIndex: -1,
        searchTimeout: null,
        rawSearch: '',

        categoryKeywords: {
            'org_cadbe': ['organization', 'organizations', 'cadbe', 'architecture', 'design', 'built environment', 'interior design', 'environmental planning'],
            'org_caf':   ['organization', 'organizations', 'caf', 'accountancy', 'finance', 'accounting', 'management accounting'],
            'org_cba':   ['organization', 'organizations', 'cba', 'business administration', 'marketing', 'entrepreneurship', 'real estate', 'cooperative', 'office administration', 'hrm'],
            'org_ccis':  ['organization', 'organizations', 'ccis', 'computer science', 'information technology', 'it', 'cs'],
            'org_ce':    ['organization', 'organizations', 'ce', 'engineering', 'civil', 'mechanical', 'electrical', 'electronics', 'industrial', 'computer engineering', 'railway'],
            'org_coc':   ['organization', 'organizations', 'coc', 'communication', 'broadcasting', 'journalism', 'film', 'advertising', 'public relations'],
            'org_coed':  ['organization', 'organizations', 'coed', 'education', 'teaching', 'library', 'early childhood'],
            'org_cal':   ['organization', 'organizations', 'cal', 'arts', 'letters', 'literature', 'filipino', 'english', 'philosophy', 'theater'],
            'org_cs':    ['organization', 'organizations', 'cs', 'science', 'chemistry', 'biology', 'physics', 'mathematics', 'statistics', 'nutrition', 'food technology'],
            'org_cssd':  ['organization', 'organizations', 'cssd', 'social sciences', 'sociology', 'psychology', 'economics', 'history', 'philippine studies', 'social work'],
            'org_cpspa': ['organization', 'organizations', 'cpspa', 'political science', 'public administration', 'international studies'],
            'org_cthtm': ['organization', 'organizations', 'cthtm', 'tourism', 'hospitality', 'transportation', 'culinary'],
            'org_chk':   ['organization', 'organizations','chk', 'human kinetics', 'physical education', 'sports', 'exercise', 'health'],
            'college':   ['college', 'colleges'],
            'campus':    ['campus', 'branch', 'branches'],
            'student_council': ['student council', 'sc', 'council', 'student government'],
        },
        
        matchesCategory(link, q) {
            const keywords = this.categoryKeywords[link.category] ?? [];
            return keywords.some(k => k.includes(q) || q.includes(k));
        },

        get results() {
            if (this.search.trim() === '') return [];

            const q = this.search.toLowerCase().trim();

            return this.allLinks
                .map(link => {
                    const title = link.title?.toLowerCase() ?? '';
                    const desc = link.description?.toLowerCase() ?? '';
                    const type = link.type?.toLowerCase() ?? '';

                    let score = -1;

                    if (title === q) {
                        score = 100;
                    } else if (title.startsWith(q)) {
                        score = 90;
                    } else if (title.split(/\s+/).some(word => word.startsWith(q))) {
                        score = 80;
                    } else if (title.includes(q)) {
                        score = 70;
                    } else if (desc === q) {
                        score = 60;
                    } else if (desc.startsWith(q)) {
                        score = 50;
                    } else if (desc.split(/\s+/).some(word => word.startsWith(q))) {
                        score = 40;
                    } else if (desc.includes(q)) {
                        score = 30;
                    } else if (type === q) {
                        score = 20;
                    } else if (this.matchesCategory(link, q)) {
                        score = 10;
                    }

                    return { ...link, _score: score };
                })
                .filter(link => link._score >= 0)
                .sort((a, b) => {
                    if (b._score !== a._score) return b._score - a._score;

                    const aTitle = a.title?.toLowerCase() ?? '';
                    const bTitle = b.title?.toLowerCase() ?? '';

                    return aTitle.localeCompare(bTitle);
                });
        },

        get suggestions() {
            if (this.search.trim().length < 1) return [];
            const q = this.search.toLowerCase();

            return this.allLinks
                .filter(link =>
                    link.title?.toLowerCase().includes(q) ||
                    link.description?.toLowerCase().includes(q) ||
                    this.matchesCategory(link, q)
                )
                .map(link => {
                    const title = link.title?.toLowerCase() ?? '';
                    const desc = link.description?.toLowerCase() ?? '';
                    let score = 0;

                    if (title === q)                          score = 10;
                    else if (title.startsWith(q))             score = 8;
                    else if (title.includes(' ' + q))         score = 6;
                    else if (title.includes(q))               score = 4;
                    else if (desc === q)                      score = 3;
                    else if (desc.startsWith(q))              score = 2;
                    else if (desc.includes(q))                score = 1;
                    else if (this.matchesCategory(link, q))   score = 0;

                    return { ...link, _score: score };
                })
                .sort((a, b) => b._score - a._score)
                .slice(0, 6);
        },

        get groupedResults() {
            const order = ['file', 'website', 'facebook_page', 'community', 'subreddit'];
            return order
                .map(type => ({
                    type,
                    label: { file:'Files', website:'Websites', facebook_page:'Facebook Pages', community:'Communities', subreddit:'Subreddits' }[type],
                    items: this.results.filter(l => l.type === type)
                }))
                .filter(g => g.items.length > 0);
        },

        typeLabel(type) {
            return { file:'File', website:'Website', facebook_page:'Page', community:'Community', subreddit:'Subreddit' }[type] ?? type;
        },

        setActive(section) {
            this.activeSection = section;
        },

        scrollToSection(section) {
            const el = document.getElementById(section);
            if (!el) return;

            const y = el.getBoundingClientRect().top + window.pageYOffset - 110;

            window.scrollTo({
                top: y,
                behavior: 'smooth'
            });

            this.activeSection = section;
            history.replaceState(null, '', section === 'home' ? window.location.pathname : `#${section}`);
        },

        updateActiveSection() {
            this.showStickySearch = window.scrollY > 320;

            const sections = [...document.querySelectorAll('section[id]')]
                .filter(section => section.offsetParent !== null); // only visible sections

            let current = 'home';
            let closestTop = Infinity;

            sections.forEach(section => {
                const rect = section.getBoundingClientRect();
                const distance = Math.abs(rect.top - 140);

                if (rect.top <= 180 && distance < closestTop) {
                    closestTop = distance;
                    current = section.id;
                }
            });

            if (window.scrollY < 120) {
                current = 'home';
            }

            const atBottom = window.innerHeight + window.scrollY >= document.body.offsetHeight - 10;

            if (atBottom) {
                current = 'about';
            }
            this.activeSection = current;

            history.replaceState(null, '', current === 'home' ? window.location.pathname : `#${current}`);
        }
    }"
    x-init="
        updateActiveSection();
        window.addEventListener('scroll', () => updateActiveSection());
    "
    class="min-h-screen bg-neutral-100 text-black"
>

<header class="fixed top-4 left-1/2 z-50 -translate-x-1/2">
    <div class="flex items-center gap-4 rounded-full border border-white/20 bg-[#800000]/100 px-6 py-3 text-white shadow-2xl backdrop-blur-2xl">

        <a href="#home" class="font-bold whitespace-nowrap">ONE PUP</a>

        <nav class="hidden md:flex gap-4 text-sm">
            <a href="#" @click.prevent="search = ''; scrollToSection('home');" :class="activeSection === 'home' ? 'text-[#FFDF00] font-semibold' : 'transition hover:text-white/80'">Home</a>
            <a href="#" @click.prevent="search = ''; scrollToSection('files');" :class="activeSection === 'files' ? 'text-[#FFDF00] font-semibold' : 'transition hover:text-white/80'">Files</a>
            <a href="#" @click.prevent="search = ''; scrollToSection('websites');" :class="activeSection === 'websites' ? 'text-[#FFDF00] font-semibold' : 'transition hover:text-white/80'">Websites</a>
            <a href="#" @click.prevent="search = ''; scrollToSection('facebook-pages');" :class="activeSection === 'facebook-pages' ? 'text-[#FFDF00] font-semibold' : 'transition hover:text-white/80'">Pages</a>
            <a href="#" @click.prevent="search = ''; scrollToSection('communities');" :class="activeSection === 'communities' ? 'text-[#FFDF00] font-semibold' : 'transition hover:text-white/80'">Communities</a>
             <a href="#" @click.prevent="search = ''; scrollToSection('subreddits');" :class="activeSection === 'subreddits' ? 'text-[#FFDF00] font-semibold' : 'transition hover:text-white/80'">Subreddits</a>
             <a href="#" @click.prevent="search = ''; scrollToSection('suggest');" :class="activeSection === 'suggest' ? 'text-[#FFDF00] font-semibold' : 'transition hover:text-white/80'">Contribute</a>
             <a href="#" @click.prevent="search = ''; scrollToSection('about');" :class="activeSection === 'about' ? 'text-[#FFDF00] font-semibold' : 'transition hover:text-white/80'">About</a>
        </nav>

        <div x-show="showStickySearch" x-transition class="relative">
            <input
                type="text"
                :value="rawSearch"
                @input="
                    $el.value = $event.target.value;
                    rawSearch = $event.target.value;
                    showDropdown = true;
                    highlightedIndex = -1;
                    clearTimeout(searchTimeout);
                    if (rawSearch.trim() === '') {
                        search = '';           // clear immediately
                    } else {
                        searchTimeout = setTimeout(() => { search = rawSearch; }, 300);
                    }
                "
                @focus="showDropdown = true"
                @blur="setTimeout(() => showDropdown = false, 150)"
                @keydown.arrow-down.prevent="highlightedIndex = Math.min(highlightedIndex + 1, suggestions.length - 1)"
                @keydown.arrow-up.prevent="highlightedIndex = Math.max(highlightedIndex - 1, -1)"
                @keydown.enter.prevent="
                    if (highlightedIndex >= 0 && suggestions[highlightedIndex]) {
                        search = suggestions[highlightedIndex].title;
                        rawSearch = suggestions[highlightedIndex].title;
                    }
                    showDropdown = false;
                "
                @keydown.escape="showDropdown = false"
                placeholder="Looking for something?"
                class="ml-4 rounded-full bg-white/90 px-4 py-2 text-sm text-black outline-none w-72"
            >

            <div
                x-show="showDropdown && suggestions.length > 0"
                x-cloak
                class="absolute left-4 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-neutral-200 overflow-y-auto max-h-72 z-50 w-96"
            >
                <template x-for="(suggestion, index) in suggestions" :key="suggestion.id">
                    <div
                        @mousedown.prevent="search = suggestion.title; rawSearch = suggestion.title; showDropdown = false"
                        :class="highlightedIndex === index ? 'bg-[#800000] text-white' : 'hover:bg-neutral-100 text-black'"
                        class="flex items-center justify-between px-5 py-3 cursor-pointer transition"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                            </svg>
                            <div class="min-w-0">
                                <div class="truncate font-medium" x-text="suggestion.title"></div>
                                <div
                                    class="truncate text-xs mt-0.5 opacity-60"
                                    x-text="suggestion.description ? (suggestion.description.length > 60 ? suggestion.description.slice(0, 60) + '...' : suggestion.description) : ''"
                                ></div>
                            </div>
                        </div>
                        <span
                            :class="highlightedIndex === index ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'"
                            class="text-xs px-2 py-1 rounded ml-3 shrink-0"
                            x-text="{ file:'File', website:'Website', facebook_page:'Page', community:'Community', subreddit:'Subreddit' }[suggestion.type] ?? suggestion.type"
                        ></span>
                    </div>
                </template>
            </div>
        </div>

    </div>
</header>

<section id="home"
class="scroll-mt-28 relative flex min-h-[480px] items-center justify-center text-white px-6 pt-32 mb-10"
style="background-image:url('/images/Header.png');background-size:cover;background-position:center;">

<div class="absolute inset-0 bg-black/40"></div>

<div class="relative z-10 max-w-5xl text-center">

<h1 class="mb-6 text-6xl font-display font-black tracking-tight">
ONE PUP
</h1>

<p class="text-xl mb-10 opacity-90">
A centralized website where every essential PUP link is accessible in one place.
</p>

<div class="mx-auto max-w-3xl">

<div class="mx-auto max-w-3xl relative">
    <div class="rounded-full bg-white/85 p-2 shadow-2xl backdrop-blur-xl">
        <input
            type="text"
            :value="rawSearch"
            @input="
                $el.value = $event.target.value;
                rawSearch = $event.target.value;
                showDropdown = true;
                highlightedIndex = -1;
                clearTimeout(searchTimeout);
                if (rawSearch.trim() === '') {
                    search = '';           // clear immediately
                } else {
                    searchTimeout = setTimeout(() => { search = rawSearch; }, 300);
                }
            "
            @focus="showDropdown = true"
            @blur="setTimeout(() => showDropdown = false, 150)"
            @keydown.arrow-down.prevent="highlightedIndex = Math.min(highlightedIndex + 1, suggestions.length - 1)"
            @keydown.arrow-up.prevent="highlightedIndex = Math.max(highlightedIndex - 1, -1)"
            @keydown.enter.prevent="
                if (highlightedIndex >= 0 && suggestions[highlightedIndex]) {
                    search = suggestions[highlightedIndex].title;
                    rawSearch = suggestions[highlightedIndex].title;
                }
                showDropdown = false;
            "
            @keydown.escape="showDropdown = false"
            placeholder="Looking for something?"
            class="w-full rounded-full bg-transparent px-6 py-2 text-lg text-black outline-none"
        >
    </div>

    <div
        x-show="showDropdown && suggestions.length > 0 && !showStickySearch"
        x-cloak
        class="absolute left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-neutral-200 overflow-y-auto max-h-72 z-50"
    >
        <template x-for="(suggestion, index) in suggestions" :key="suggestion.id">
            <div
                @mousedown.prevent="search = suggestion.title; rawSearch = suggestion.title; showDropdown = false"
                :class="highlightedIndex === index ? 'bg-[#800000] text-white' : 'hover:bg-neutral-100 text-black'"
                class="flex items-center justify-between px-5 py-3 cursor-pointer transition"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                    <div class="min-w-0 text-left">
                        <div class="truncate font-medium" x-text="suggestion.title"></div>
                        <div
                            class="truncate text-xs mt-0.5 opacity-60"
                            x-text="suggestion.description ? (suggestion.description.length > 60 ? suggestion.description.slice(0, 60) + '...' : suggestion.description) : ''"
                        ></div>
                    </div>
                </div>
                <span
                    :class="highlightedIndex === index ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'"
                    class="text-xs px-2 py-1 rounded ml-3 shrink-0"
                    x-text="{ file:'File', website:'Website', facebook_page:'Page', community:'Community', subreddit:'Subreddit' }[suggestion.type] ?? suggestion.type"
                ></span>
            </div>
        </template>
    </div>
</div>

</div>

<p class="mt-8 italic text-lg">
Suggestions, errors, or missing links?
<a href="#suggest" class="underline font-semibold">Submit them here.</a>
</p>

</div>

</section>

<div x-show="search.trim() !== ''" x-cloak>
    <section class="scroll-mt-28 mx-auto max-w-7xl px-6 py-1">

        <h2 class="text-center text-4xl font-display font-black text-[#800000] mb-6">
            Search results for "<span x-text="search"></span>"
        </h2>

        <div x-show="results.length === 0" class="text-center py-12">
            <img src="/images/no-results.gif" class="mx-auto w-72 mb-6" alt="I'm sorry, I don't have that :< maybe you could raise it to me?">
            <p class="text-xl text-gray-600 font-semibold">No results found for "<span x-text="search"></span>"</p>
        </div>

        <div x-show="results.length > 0">
            @foreach(['file' => 'Files', 'website' => 'Websites', 'facebook_page' => 'Facebook Pages', 'community' => 'Communities', 'subreddit' => 'Subreddits'] as $type => $label)
                @php
                    $typeLinks = $this->searchableLinks->where('type', $type);
                    $official = $typeLinks->where('is_official', true)->values();
                    $unofficial = $typeLinks->where('is_official', false)->values();
                @endphp

                <div
                    x-show="results.some(r => r.type === '{{ $type }}')"
                    class="mb-10"
                >
                    <h2 class="mb-8 text-center font-display text-4xl font-black text-[#800000]">
                        {{ $label }}
                    </h2>
                    @if($official->count())
                        <div x-show="results.some(r => r.type === '{{ $type }}' && r.is_official)">
                            <div class="mb-12">
                                <div class="mb-6 flex items-center gap-4">
                                    <div class="h-px flex-1 bg-[#800000]/30"></div>
                                    <h3 class="font-display text-2xl font-bold text-[#800000]">Official {{ $label }}</h3>
                                    <div class="h-px flex-1 bg-[#800000]/30"></div>
                                </div>
                                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach($official as $link)
                                        <div x-show="results.some(r => r.id === {{ $link->id }})" class="contents">
                                            <a
                                                href="{{ $link->url }}"
                                                target="_blank"
                                                class="group relative rounded-[28px] border p-6 shadow-xl bg-white transition hover:-translate-y-1 hover:shadow-2xl border-[#800000]/40 hover:border-[#800000] hover:bg-[#800000]"
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
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($unofficial->count())
                        <div x-show="results.some(r => r.type === '{{ $type }}' && !r.is_official)">
                            <div class="mb-12">
                                <div class="mb-6 flex items-center gap-4">
                                    <div class="h-px flex-1 bg-[#DAA520]/30"></div>
                                    <h3 class="font-display text-2xl font-bold text-[#800000]">Unofficial {{ $label }}</h3>
                                    <div class="h-px flex-1 bg-[#DAA520]/30"></div>
                                </div>
                                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach($unofficial as $link)
                                        <div x-show="results.some(r => r.id === {{ $link->id }})" class="contents">
                                            <a
                                                href="{{ $link->url }}"
                                                target="_blank"
                                                class="group relative rounded-[28px] border p-6 shadow-xl bg-white transition hover:-translate-y-1 hover:shadow-2xl border-[#DAA520]/60 hover:border-[#DAA520] hover:bg-[#DAA520]"
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
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            @endforeach
        </div>

    </section>
</div>

<div x-show="search.trim() === ''" x-cloak>

@include('partials.section',[
'id'=>'files',
'title'=>'Files',
'groups'=>$this->groupedLinks['file'] ?? []
])

@include('partials.section',[
'id'=>'websites',
'title'=>'Websites',
'groups'=>$this->groupedLinks['website'] ?? []
])

@include('partials.section',[
'id'=>'facebook-pages',
'title'=>'Facebook Pages',
'groups'=>$this->groupedLinks['facebook_page'] ?? []
])

@include('partials.section',[
'id'=>'communities',
'title'=>'Communities',
'groups'=>$this->groupedLinks['community'] ?? []
])

@include('partials.section',[
'id'=>'subreddits',
'title'=>'Subreddits',
'groups'=>$this->groupedLinks['subreddit'] ?? []
])

</div>

<section id="suggest" class="scroll-mt-28 bg-neutral-100 px-6 py-10">

    <div class="mx-auto max-w-3xl rounded-[32px] border border-neutral-200 bg-white p-8 text-black shadow-xl">

        <h2 class="mb-3 font-display text-4xl font-black text-[#800000]">
            Contribute
        </h2>

        <p class="mb-8 text-neutral-600">
            Share missing links, comments, suggestions, or anything you want to raise.
        </p>

        @if (session()->has('success'))
            <div class="mb-6 rounded-2xl bg-[#DAA520] px-4 py-3 text-white/100 border-[#DAA520]">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit="submit" class="space-y-6">

            <div>
                <label class="mb-2 block font-semibold text-neutral-800">
                    What do you want to comment, suggest, raise, or say?
                </label>

                <textarea
                    wire:model.live.debounce.600ms="message"
                    rows="1"
                    x-data
                    x-init="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                    x-on:input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                    class="w-full min-h-[52px] resize-none overflow-hidden rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-black outline-none transition-all duration-150"
                ></textarea>

                @error('message')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="flex items-center gap-3 text-neutral-800">
                    <input type="checkbox" wire:model.live="wants_notification">
                    <span>Notify me when resolved</span>
                </label>
            </div>

            <div>
                <label class="mb-2 block font-semibold text-neutral-800">
                    Email {{ $this->wants_notification ? '(required)' : '(optional)' }}
                </label>

                <input
                    type="email"
                    wire:model.live.debounce.500ms="email"
                    class="w-full rounded-2xl border border-neutral-300 bg-white px-4 py-3 text-black outline-none"
                    placeholder="example@email.com"
                >

                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="rounded-full bg-[#800000] px-6 py-3 font-bold text-white shadow-lg text-[#800000] hover:bg-[#DAA520] transition"
            >
                Submit
            </button>

        </form>
    </div>

</section>

<section id="about"
class="scroll-mt-28 mx-auto max-w-5xl px-6 py-10 text-center">

<h2 class="text-3xl font-display font-black text-[#800000] mb-4">
About
</h2>

<div class="space-y-4 text-lg text-gray-700">
    <p>OnePUP is a student-driven initiative built to make essential PUP resources easier to access in one place.</p>

    <p>This project was created by a student from Polytechnic University of the Philippines (PUP) Manila to help fellow students quickly find useful links, communities, and tools without the hassle of searching across multiple platforms.</p>

    <p>This is not an official university project and is not affiliated with or supervised by PUP. It is purely a community-driven effort aimed at improving accessibility and student experience.</p>

    <p>If you have suggestions, found errors, or want to contribute, feel free to reach out!</p>
</div>

<p class="mt-6 text-lg text-gray-700">
    Want to connect or collaborate? <span class="font-semibold">Contact me directly!</span>
</p>

<div class="mt-6 flex justify-center gap-6">
    
    <!-- Facebook -->
    <a href="https://www.facebook.com/mak.jowsep" target="_blank" class="text-[#800000] hover:text-[#DAA520] transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-11 w-11" fill="currentColor" viewBox="0 0 24 24">
            <path d="M22 12a10 10 0 10-11.5 9.9v-7h-2.3v-2.9h2.3V9.4c0-2.3 1.4-3.6 3.5-3.6 1 0 2 .2 2 .2v2.2h-1.1c-1.1 0-1.5.7-1.5 1.4v1.7h2.6l-.4 2.9h-2.2v7A10 10 0 0022 12z"/>
        </svg>
    </a>

    <!-- GitHub -->
    <a href="https://github.com/jaedone" target="_blank" class="text-[#800000] hover:text-[#DAA520] transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 .5A12 12 0 000 12.7c0 5.5 3.6 10.1 8.6 11.7.6.1.8-.3.8-.6v-2.2c-3.5.8-4.2-1.7-4.2-1.7-.6-1.5-1.4-1.9-1.4-1.9-1.2-.8.1-.8.1-.8 1.3.1 2 .1 2 .1 1.2 2.1 3.2 1.5 4 .1.1-.9.5-1.5.9-1.9-2.8-.3-5.7-1.4-5.7-6.3 0-1.4.5-2.5 1.3-3.4-.1-.3-.6-1.6.1-3.3 0 0 1-.3 3.4 1.3a11.5 11.5 0 016.2 0c2.4-1.6 3.4-1.3 3.4-1.3.7 1.7.2 3 .1 3.3.8.9 1.3 2 1.3 3.4 0 4.9-2.9 6-5.7 6.3.5.4.9 1.2.9 2.5v3.7c0 .3.2.7.8.6A12 12 0 0024 12.7 12 12 0 0012 .5z"/>
        </svg>
    </a>

    <!-- LinkedIn -->
    <a href="https://www.linkedin.com/in/jaedaaann" target="_blank" class="text-[#800000] hover:text-[#DAA520] transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="currentColor" viewBox="0 0 24 24">
            <path d="M4.98 3.5C4.98 5 3.86 6 2.5 6S0 5 0 3.5 1.12 1 2.5 1 4.98 2 4.98 3.5zM.5 8h4v12h-4V8zm7 0h3.8v1.7h.1c.5-.9 1.7-1.9 3.6-1.9 3.9 0 4.6 2.5 4.6 5.7V20h-4v-5.3c0-1.3 0-3-1.8-3s-2.1 1.4-2.1 2.9V20h-4V8z"/>
        </svg>
    </a>

    <!-- Gmail -->
    <a href="https://mail.google.com/mail/?view=cm&to=neypesmarkjosephb@gmail.com" target="_blank" class="text-[#800000] hover:text-[#DAA520] transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 13.5L0 6.75V18h24V6.75L12 13.5zm12-9H0l12 7.5L24 4.5z"/>
        </svg>
    </a>

</div>

</section>

</div>
