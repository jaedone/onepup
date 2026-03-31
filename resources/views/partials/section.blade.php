<section id="{{ $id }}" class="scroll-mt-28 mx-auto max-w-7xl px-6 py-1">
    <h2 class="mb-8 text-center font-display text-4xl font-black text-[#800000]">
        {{ $title }}
    </h2>

    @php
        $subsections = [
            'official'        => 'Official ' . $title,
            'unofficial'      => 'Unofficial ' . $title,
            'college'         => 'PUP Colleges',
            'campus'          => 'PUP Campuses & Branches',
            'student_council' => 'Student Councils',
            'org_uwide'       => 'University-Wide Organizations',
            'org_ce'          => 'College of Engineering Organizations',
            'org_ccis'        => 'CCIS Organizations',
            'org_cadbe'       => 'CADBE Organizations',
            'org_caf'         => 'CAF Organizations',
            'org_cba'         => 'CBA Organizations',
            'org_coc'         => 'COC Organizations',
            'org_coed'        => 'COED Organizations',
            'org_cal'         => 'CAL Organizations',
            'org_cs'          => 'CS Organizations',
            'org_cssd'        => 'CSSD Organizations',
            'org_cpspa'       => 'CPSPA Organizations',
            'org_cthtm'       => 'CTHTM Organizations',
            'org_chk'         => 'CHK Organizations',
            'org_cl'          => 'CL Organizations',
            'org_itech'       => 'ITECH Organizations',
            'org_ous'         => 'OUS Organizations',
        ];

        $unofficialCategories = ['unofficial'];
    @endphp

    @foreach($subsections as $categoryKey => $subsectionTitle)
        @php $links = $groups[$categoryKey] ?? collect(); @endphp

        @if($links->count())
            @include('partials.link-group', [
                'title'    => $subsectionTitle,
                'links'    => $links,
                'official' => !in_array($categoryKey, $unofficialCategories),
            ])
        @endif
    @endforeach
</section>