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
            'publication'    => 'Student Publications',
            'org_uwide'       => 'University-Wide Organizations',
            'org_ce'          => 'College of Engineering (CE) Organizations',
            'org_ccis'        => 'College of Computer and Information Sciences (CCIS) Organizations',
            'org_cadbe'       => 'College of Architecture and Design Building Engineering (CADBE) Organizations',
            'org_caf'         => 'College of Accountancy and Finance (CAF) Organizations',
            'org_cba'         => 'College of Business Administration (CBA) Organizations',
            'org_coc'         => 'College of Communication (COC) Organizations',
            'org_coed'        => 'College of Education (COED) Organizations',
            'org_cal'         => 'College of Arts and Letters (CAL) Organizations',
            'org_cs'          => 'College of Science (CS) Organizations',
            'org_cssd'        => 'College of Social Sciences and Development (CSSD) Organizations',
            'org_cpspa'       => 'College of Political Science and Public Administration (CPSPA) Organizations',
            'org_cthtm'       => 'College of Tourism, Hospitality and Transportation Management (CTHTM) Organizations',
            'org_chk'         => 'College of Human Kinetics (CHK) Organizations',
            'org_cl'          => 'College of Law (CL) Organizations',
            'org_itech'       => 'Institute of Technology (ITECH) Organizations',
            'org_ous'         => 'Open University System (OUS) Organizations',
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