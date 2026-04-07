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

};
?>

<div
    x-data="{
        showStickySearch: false,
        activeSection: 'home',
        search: '',
        allLinks: {{ Js::from($this->searchableLinks) }}.map(link => ({
            ...link,
            _title: (link.title || '').toLowerCase(),
            _desc: (link.description || '').toLowerCase(),
            _type: (link.type || '').toLowerCase(),
        })),

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
            const q = this.search.toLowerCase().trim();
            if (!q) return [];

            return this.allLinks
                .map(link => {
                    let score = -1;

                    if (link._title === q) {
                        score = 100;
                    } else if (link._title.startsWith(q)) {
                        score = 90;
                    } else if (link._title.split(/\s+/).some(word => word.startsWith(q))) {
                        score = 80;
                    } else if (link._title.includes(q)) {
                        score = 70;
                    } else if (link._desc === q) {
                        score = 60;
                    } else if (link._desc.startsWith(q)) {
                        score = 50;
                    } else if (link._desc.split(/\s+/).some(word => word.startsWith(q))) {
                        score = 40;
                    } else if (link._desc.includes(q)) {
                        score = 30;
                    } else if (link._type === q) {
                        score = 20;
                    } else if (this.matchesCategory(link, q)) {
                        score = 10;
                    }

                    return { ...link, _score: score };
                })
                .filter(link => link._score >= 0)
                .sort((a, b) => {
                    if (b._score !== a._score) return b._score - a._score;
                    return a._title.localeCompare(b._title);
                })
                .slice(0, 30);
        },

        get groupedResults() {
            return {
                file: this.results.filter(link => link.type === 'file'),
                website: this.results.filter(link => link.type === 'website'),
                facebook_page: this.results.filter(link => link.type === 'facebook_page'),
                community: this.results.filter(link => link.type === 'community'),
                subreddit: this.results.filter(link => link.type === 'subreddit'),
            };
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
    <div
        x-data="{ menuOpen: false }"
        class="relative flex items-center gap-4 rounded-full border border-white/20 bg-[#800000]/100 px-6 py-3 text-white shadow-2xl backdrop-blur-2xl"
        :class="menuOpen ? 'rounded-3xl' : 'rounded-full'"
    >

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
                x-model.debounce.200ms="search"
                placeholder="Looking for something?"
                class="ml-4 rounded-full bg-white/90 px-4 py-2 text-sm text-black outline-none w-72 border-2 border-[#DAA520] focus-within:ring-2 focus-within:ring-[#DAA520]/40"
            >
        </div>

        <button
            @click="menuOpen = !menuOpen"
            class="md:hidden flex flex-col justify-center items-center w-8 h-8 gap-1.5 focus:outline-none"
            aria-label="Toggle menu"
        >
            <span class="block w-5 h-0.5 bg-white transition-all duration-300" :class="menuOpen ? 'rotate-45 translate-y-2' : ''"></span>
            <span class="block w-5 h-0.5 bg-white transition-all duration-300" :class="menuOpen ? 'opacity-0' : ''"></span>
            <span class="block w-5 h-0.5 bg-white transition-all duration-300" :class="menuOpen ? '-rotate-45 -translate-y-2' : ''"></span>
        </button>

        <div
            x-show="menuOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            @click.outside="menuOpen = false"
            class="md:hidden absolute top-full left-0 right-0 mt-2 bg-[#800000] rounded-2xl shadow-2xl border border-white/10 overflow-hidden"
            x-cloak
        >
            <nav class="flex flex-col text-sm py-2">
                <a href="#" @click.prevent="search = ''; scrollToSection('home'); menuOpen = false" class="px-6 py-3 hover:bg-white/10 transition" :class="activeSection === 'home' ? 'text-[#FFDF00] font-semibold' : ''">Home</a>
                <a href="#" @click.prevent="search = ''; scrollToSection('files'); menuOpen = false" class="px-6 py-3 hover:bg-white/10 transition" :class="activeSection === 'files' ? 'text-[#FFDF00] font-semibold' : ''">Files</a>
                <a href="#" @click.prevent="search = ''; scrollToSection('websites'); menuOpen = false" class="px-6 py-3 hover:bg-white/10 transition" :class="activeSection === 'websites' ? 'text-[#FFDF00] font-semibold' : ''">Websites</a>
                <a href="#" @click.prevent="search = ''; scrollToSection('facebook-pages'); menuOpen = false" class="px-6 py-3 hover:bg-white/10 transition" :class="activeSection === 'facebook-pages' ? 'text-[#FFDF00] font-semibold' : ''">Pages</a>
                <a href="#" @click.prevent="search = ''; scrollToSection('communities'); menuOpen = false" class="px-6 py-3 hover:bg-white/10 transition" :class="activeSection === 'communities' ? 'text-[#FFDF00] font-semibold' : ''">Communities</a>
                <a href="#" @click.prevent="search = ''; scrollToSection('subreddits'); menuOpen = false" class="px-6 py-3 hover:bg-white/10 transition" :class="activeSection === 'subreddits' ? 'text-[#FFDF00] font-semibold' : ''">Subreddits</a>
                <a href="#" @click.prevent="search = ''; scrollToSection('suggest'); menuOpen = false" class="px-6 py-3 hover:bg-white/10 transition" :class="activeSection === 'suggest' ? 'text-[#FFDF00] font-semibold' : ''">Contribute</a>
                <a href="#" @click.prevent="search = ''; scrollToSection('about'); menuOpen = false" class="px-6 py-3 hover:bg-white/10 transition" :class="activeSection === 'about' ? 'text-[#FFDF00] font-semibold' : ''">About</a>
            </nav>
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

<div class="flex items-center rounded-full bg-white/80 px-10 py-4 shadow-2xl backdrop-blur-xl gap-2 border-2 border-[#DAA520] focus-within:ring-2 focus-within:ring-[#DAA520]/40">
    <input
        type="text"
        x-model="search"
        @keydown.enter="search = $event.target.value"
        placeholder="Looking for something?"
        class="flex-1 bg-transparent text-base text-black outline-none"
    />
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
            <img src="/images/no-results.gif" class="mx-auto w-72 mb-6" alt="No results found">
            <p class="text-xl text-gray-600 font-semibold">
                No results found for "<span x-text="search"></span>"
            </p>
        </div>

        <div x-show="results.length > 0" class="space-y-12">

            <template x-if="groupedResults.file.length">
                <div>
                    <h2 class="mb-6 text-center font-display text-4xl font-black text-[#800000]">Files</h2>
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <template x-for="link in groupedResults.file" :key="link.id">
                            <a
                                :href="link.url"
                                target="_blank"
                                class="group relative rounded-[28px] border p-6 shadow-xl bg-white transition hover:-translate-y-1 hover:shadow-2xl"
                                :class="link.is_official
                                    ? 'border-[#800000]/40 hover:border-[#800000] hover:bg-[#800000]'
                                    : 'border-[#DAA520]/60 hover:border-[#DAA520] hover:bg-[#DAA520]'"
                            >
                                <div class="mb-2 flex items-start justify-between gap-3">
                                    <h4 class="text-lg font-bold text-[#800000] group-hover:text-white break-words" x-text="link.title"></h4>
                                    <span class="text-xs px-2 py-1 rounded bg-gray-200">File</span>
                                </div>
                                <p class="text-sm leading-6 text-neutral-600 group-hover:text-white/90" x-text="link.description"></p>
                            </a>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="groupedResults.website.length">
                <div>
                    <h2 class="mb-6 text-center font-display text-4xl font-black text-[#800000]">Websites</h2>
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <template x-for="link in groupedResults.website" :key="link.id">
                            <a
                                :href="link.url"
                                target="_blank"
                                class="group relative rounded-[28px] border p-6 shadow-xl bg-white transition hover:-translate-y-1 hover:shadow-2xl"
                                :class="link.is_official
                                    ? 'border-[#800000]/40 hover:border-[#800000] hover:bg-[#800000]'
                                    : 'border-[#DAA520]/60 hover:border-[#DAA520] hover:bg-[#DAA520]'"
                            >
                                <div class="mb-2 flex items-start justify-between gap-3">
                                    <h4 class="text-lg font-bold text-[#800000] group-hover:text-white break-words" x-text="link.title"></h4>
                                    <span class="text-xs px-2 py-1 rounded bg-gray-200">Website</span>
                                </div>
                                <p class="text-sm leading-6 text-neutral-600 group-hover:text-white/90" x-text="link.description"></p>
                            </a>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="groupedResults.facebook_page.length">
                <div>
                    <h2 class="mb-6 text-center font-display text-4xl font-black text-[#800000]">Facebook Pages</h2>
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <template x-for="link in groupedResults.facebook_page" :key="link.id">
                            <a
                                :href="link.url"
                                target="_blank"
                                class="group relative rounded-[28px] border p-6 shadow-xl bg-white transition hover:-translate-y-1 hover:shadow-2xl"
                                :class="link.is_official
                                    ? 'border-[#800000]/40 hover:border-[#800000] hover:bg-[#800000]'
                                    : 'border-[#DAA520]/60 hover:border-[#DAA520] hover:bg-[#DAA520]'"
                            >
                                <div class="mb-2 flex items-start justify-between gap-3">
                                    <h4 class="text-lg font-bold text-[#800000] group-hover:text-white break-words" x-text="link.title"></h4>
                                    <span class="text-xs px-2 py-1 rounded bg-gray-200">Page</span>
                                </div>
                                <p class="text-sm leading-6 text-neutral-600 group-hover:text-white/90" x-text="link.description"></p>
                            </a>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="groupedResults.community.length">
                <div>
                    <h2 class="mb-6 text-center font-display text-4xl font-black text-[#800000]">Communities</h2>
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <template x-for="link in groupedResults.community" :key="link.id">
                            <a
                                :href="link.url"
                                target="_blank"
                                class="group relative rounded-[28px] border p-6 shadow-xl bg-white transition hover:-translate-y-1 hover:shadow-2xl"
                                :class="link.is_official
                                    ? 'border-[#800000]/40 hover:border-[#800000] hover:bg-[#800000]'
                                    : 'border-[#DAA520]/60 hover:border-[#DAA520] hover:bg-[#DAA520]'"
                            >
                                <div class="mb-2 flex items-start justify-between gap-3">
                                    <h4 class="text-lg font-bold text-[#800000] group-hover:text-white break-words" x-text="link.title"></h4>
                                    <span class="text-xs px-2 py-1 rounded bg-gray-200">Community</span>
                                </div>
                                <p class="text-sm leading-6 text-neutral-600 group-hover:text-white/90" x-text="link.description"></p>
                            </a>
                        </template>
                    </div>
                </div>
            </template>

            <template x-if="groupedResults.subreddit.length">
                <div>
                    <h2 class="mb-6 text-center font-display text-4xl font-black text-[#800000]">Subreddits</h2>
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <template x-for="link in groupedResults.subreddit" :key="link.id">
                            <a
                                :href="link.url"
                                target="_blank"
                                class="group relative rounded-[28px] border p-6 shadow-xl bg-white transition hover:-translate-y-1 hover:shadow-2xl"
                                :class="link.is_official
                                    ? 'border-[#800000]/40 hover:border-[#800000] hover:bg-[#800000]'
                                    : 'border-[#DAA520]/60 hover:border-[#DAA520] hover:bg-[#DAA520]'"
                            >
                                <div class="mb-2 flex items-start justify-between gap-3">
                                    <h4 class="text-lg font-bold text-[#800000] group-hover:text-white break-words" x-text="link.title"></h4>
                                    <span class="text-xs px-2 py-1 rounded bg-gray-200">Subreddit</span>
                                </div>
                                <p class="text-sm leading-6 text-neutral-600 group-hover:text-white/90" x-text="link.description"></p>
                            </a>
                        </template>
                    </div>
                </div>
            </template>

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
