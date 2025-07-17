<x-app-layout>
    @php
        $currentPage = Route::currentRouteName() == 'jobpostings' ? 'HR' : '';
    @endphp
    <div class="max-w-9xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
        <div class="grid-col-1 grid gap-6 xl:grid-cols-5 xl:grid-rows-1">
            {{-- All Status --}}
            <button>
                <a href="#" class="status-filter" data-status="">
                    <div
                        class="flex items-center gap-4 rounded-lg border border-orange-700 bg-orange-200/20 p-3 text-orange-600">
                        <span class="text-xl">📄</span>
                        <div class="flex flex-grow items-center justify-between">
                            <p class="text-lg font-medium">All</p>
                            <p class="text-right text-xl font-extrabold">{{ $all }}</p>
                        </div>
                    </div>
                </a>
            </button>

            {{-- On Progress Status --}}
            <button>
                <a href="#" class="status-filter" data-status="P">
                    <div
                        class="flex items-center gap-4 rounded-lg border border-blue-700 bg-blue-200/20 p-3 text-blue-600">
                        <span class="text-xl">⏳</span>
                        <div class="flex flex-grow items-center justify-between">
                            <p class="text-lg font-medium">On Progress</p>
                            <p class="text-right text-xl font-extrabold">{{ $onProgress }}</p>
                        </div>
                    </div>
                </a>
            </button>

            {{-- Reject Status --}}
            <button>
                <a href="#" class="status-filter" data-status="R">
                    <div
                        class="flex items-center gap-4 rounded-lg border border-red-700 bg-red-200/20 p-3 text-red-600">
                        <span class="text-xl">⛔️</span>
                        <div class="flex flex-grow items-center justify-between">
                            <p class="text-lg font-medium">Reject</p>
                            <p class="text-right text-xl font-extrabold">{{ $reject }}</p>
                        </div>
                    </div>
                </a>
            </button>

            {{-- Revise / Draft Status --}}
            <button>
                <a href="#" class="status-filter" data-status="D">
                    <div
                        class="flex items-center gap-4 rounded-lg border border-gray-700 bg-gray-200/20 p-3 text-gray-600 dark:border-white dark:text-white">
                        <span class="text-xl">✏️</span>
                        <div class="flex flex-grow items-center justify-between">
                            <p class="text-lg font-medium">Revise / Draft</p>
                            <p class="text-right text-xl font-extrabold">{{ $revise }}</p>
                        </div>
                    </div>
                </a>
            </button>

            {{-- Completed Status --}}
            <button>
                <a href="#" class="status-filter" data-status="C">
                    <div
                        class="flex items-center gap-4 rounded-lg border border-green-700 bg-green-200/20 p-3 text-green-600">
                        <span class="text-xl">✅</span>
                        <div class="flex flex-grow items-center justify-between">
                            <p class="text-lg font-medium">Completed</p>
                            <p class="text-right text-xl font-extrabold">{{ $completed }}</p>
                        </div>
                    </div>
                </a>
            </button>
        </div>
            <!-- External CSS for jobapplicant styles -->
            <link rel="stylesheet" href="{{ asset('css/jobapplicant.css') }}">
            <div id="container" class="mt-2 grid grid-cols-1 gap-4 xl:grid-cols-1">
                <!-- TABEL: Applicants Only -->
                <div id="applicantsContainer" class="overflow-x-auto rounded-xl bg-white p-4">
                    <div class="flex items-center justify-between">
                        <h1 class="text-2xl font-bold">Applicants</h1>
                    </div>
                    <div id="applicantsTableWrapper" class="overflow-x-auto rounded-xl bg-white">
                        <table id="applicantsTable" class="min-w-full rounded">
                            <thead>
                                <tr>
                                    <th>Docid</th>
                                    <th>Date</th>
                                    <th>Name</th>
                                    <th>Education</th>
                                    <th>Religion</th>
                                    <th>Height</th>
                                    <th>Weight</th>
                                    <th>Last Working</th>
                                    <th>Score</th>
                                    <th>Step</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>


                <!-- TABEL KANAN: Applicant -->
                <div id="applicantsContainer" class="overflow-x-auto rounded-xl bg-white p-4" style="display:none;">

                    <div class="flex items-center justify-between">
                        <h1 class="text-2xl font-bold">Applicants</h1>
                        <div class="flex flex-row-reverse items-center justify-end gap-4 text-xl">
                            <button id="detailApplicantsBtn" class="font-semibold text-blue-500 hover:text-blue-700">See
                                Detail</button>
                            <button id="closeApplicantsBtn"
                                class="font-semibold text-red-500 hover:text-red-700">Close</button>

                        </div>

                    </div>
                    <div id="applicantsTableWrapper" class="overflow-x-auto rounded-xl bg-white">
                        <table id="applicantsTable" class="min-w-full rounded">
                            <thead>
                                <tr>
                                    <th>Docid</th>
                                    <th>Date</th>
                                    <th>Name</th>
                                    <th>Education</th>
                                    <th>Religion</th>
                                    <th>Height</th>
                                    <th>Weight</th>
                                    <th>Last Working</th>
                                    <th>Score</th>
                                    <th>Step</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>


            <script>
                var currentUser = "{{ auth()->user()->username }}";
            </script>


            <script>
                $(document).ready(function() {
                    let currentStatus = '';
                    let applicantTable = $('#applicantsTable').DataTable({
                        responsive: true,
                        processing: true,
                        serverSide: true,
                        searching: true,
                        paging: true,
                        info: true,
                        lengthChange: true,
                        pageLength: 10,
                        ajax: {
                            url: "{{ route('jobapplicant.json') }}",
                            type: 'GET',
                            data: function(d) {
                                d.status = currentStatus;
                            }
                        },
                        order: [
                            [8, 'desc']
                        ],
                        columns: [
                            {
                                data: 'docid',
                                render: function(data, type, row) {
                                    return `<a href="/showcareers/${row.id}" target="_blank" class="px-4 py-2.5 bg-indigo-500 text-white rounded hover:bg-indigo-700">${data}</a>`;
                                }
                            },
                            { data: 'apply_date' },
                            { data: 'fullname' },
                            { data: 'education_name' },
                            { data: 'religion' },
                            { data: 'height', className: 'small-col' },
                            { data: 'weight', className: 'small-col' },
                            { data: 'company_name' },
                            { data: 'match_score_percentage', className: 'small-col' },
                            {
                                data: 'prev_apply_step',
                                render: function(data) {
                                    const labelMap = {
                                        'JOAPHC': 'Job Apply HC',
                                        'JOAPUS': 'Job Apply User',
                                        'WIHC': 'Create Schedule Interview HC',
                                        'IHC': 'Interview HC',
                                        'WIU': 'Create Schedule Interview User',
                                        'IU': 'Interview User',
                                        'WPT': 'Waiting Psycho Test',
                                        'PT': 'Psycho Test',
                                        'OFF': 'Offering',
                                        'JOIN': 'Join'
                                    };
                                    return `<span class=\"w-32 bg-blue-300/30 text-blue-600 text-base font-semibold px-4 py-2 text-center rounded\">${labelMap[data] || data}</span>`;
                                }
                            }
                        ],
                        rowCallback: function(row, data, index) {
                            if (data.is_read === 'N') {
                                $(row).css('color', 'blue');
                            } else {
                                $(row).css('color', 'black');
                            }
                        }
                    });
                    $('#applicantsTable thead th').eq(5).addClass('small-col');
                    $('#applicantsTable thead th').eq(6).addClass('small-col');
                    $('#applicantsTable thead th').eq(8).addClass('small-col');

                    // Event handler status-filter
                    $('.status-filter').on('click', function(e) {
                        e.preventDefault();
                        $('.status-filter').removeClass('active');
                        $(this).addClass('active');
                        currentStatus = $(this).data('status');
                        applicantTable.ajax.reload();
                    });
                });
            </script>

        </div>
    </div>
</x-app-layout>
