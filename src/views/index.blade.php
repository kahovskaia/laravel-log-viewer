<x-admin-layout>
    <x-slot:pre-title>{{ __('menu.administration.title') }}</x-slot:pre-title>
    <x-slot:title>{{ __('menu.administration.logs') }}</x-slot:title>
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @foreach($breadcrumbs as $key => $breadcrumb)
                        <li class="breadcrumb-item"><a href="{{ route('admin.logs.index', array_merge(request()->query(), ['folder' => $breadcrumb])) }}">{{ $key }}</a></li>
                    @endforeach
                </ol>
            </nav>
        </div>
    </div>
    <div class="row">
        <div class="col-2">
            <div class="list-group div-scroll">
                <div class="list-group-item">
                    <div class="list-group folder">
                        @foreach($folders as $folder => $url)
                            <a href="{{ route('admin.logs.index', array_merge(request()->query(), ['folder' => $url])) }}">
                                <i class="ti ti-folder"></i>
                                {{ $folder }}
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="list-group-item  llv-active ">
                    <div class="list-group file">
                        @foreach($files as $file => $url)
                            <a href="{{ route('admin.logs.index', array_merge(request()->query(), ['file' => $url])) }}">
                                <i class="ti ti-file"></i>
                                {{ $file }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-10">
            <div class="table-responsive" style="overflow: hidden;">
                <div class="row">
                    <div class="col-12">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Level</th>
                                <th>Context</th>
                                <th>Date</th>
                                <th class="text">Content</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($logs as $key => $log)
                                <tr @class(['even' => $loop->even, 'odd' => $loop->odd])>
                                    <td class="text-nowrap">
                                        <i class="ti ti-alert-{{ $log['level_img'] }}"></i>
                                        <span class="text-{{ $log['level_class'] }}">{{ $log['level'] }}</span>
                                    </td>
                                    <td class="text-nowrap">
                                        <span>{{ $log['context'] }}</span>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="d-flex flex-column gap-1">
                                            <div>
                                                <i class="ti ti-calendar-plus icon icon-sm"></i>
                                                {{ $log['date'] }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text">
                                        @if ($log['stack'])
                                            <button class="float-right btn btn-primary" type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#stack{{ $key }}" aria-expanded="false"
                                                    aria-controls="stack{{ $key }}">
                                                <i class="ti ti-file-search"></i>
                                            </button>
                                        @endif
                                        <span>{{  $log['text'] }}</span>
                                        @if (isset($log['in_file']))
                                            <br/>{{  $log['in_file'] }}
                                        @endif
                                        @if ($log['stack'])
                                            <div class="collapse" id="stack{{ $key }}">
                                                <div class="collapse-text card card-body">
                                                    {{ trim($log['stack']) }}
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <a href="{{ route('admin.log.download', request()->query()) }}"><i class="ti ti-download"></i>Download file</a>
                        <a href="{{ route('admin.log.delete', request()->query()) }}"><i class="ti ti-trash"></i>Delete file</a>
                        <a href="{{ route('admin.log.clear', request()->query()) }}"><i class="ti ti-file-x"></i>Clear file</a>
                    </div>
                    <div class="col-6 d-flex justify-content-end" id="test">
                        <nav aria-label="nav">
                            <ul class="pagination" id="pagination">
                                <li class="page-item"><a class="page-link">1</a></li>
                                <li class="page-item"><a class="page-link">2</a></li>
                                <li class="page-item"><a class="page-link">3</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <style>
            td, th {
                vertical-align: top !important;
                border-top: 1px solid #dee2e6;
                padding: 8px;
            }

            .float-right {
                float: right;
            }

            .collapse-text {
                white-space: pre-wrap;
                font-size: 10px;
            }

            table {
                border-collapse: collapse;
            }

            .text {
                word-break: break-all;,
            overflow-wrap: break-word;
            }
        </style>
        <script>
            function getParameterByName(name) {
                const url = window.location.href;
                name = name.replace(/[\[\]]/g, '\\$&');
                const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
                const results = regex.exec(url);
                if (!results) return null;
                if (!results[2]) return '';
                return decodeURIComponent(results[2].replace(/\+/g, ' '));
            }

            // Функция для обновления параметра в строке запроса
            function updateQueryStringParameter(uri, key, value) {
                const re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
                const separator = uri.indexOf('?') !== -1 ? "&" : "?";
                if (uri.match(re)) {
                    return uri.replace(re, '$1' + key + "=" + value + '$2');
                } else {
                    return uri + separator + key + "=" + value;
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                const currentPage = parseInt(getParameterByName('page')) || 1;

                const paginationContainer = document.getElementById('pagination');

                function updateActivePage() {
                    const pageLinks = paginationContainer.querySelectorAll('.page-item');
                    pageLinks.forEach(link => {
                        const pageNumber = parseInt(link.querySelector('.page-link').textContent);
                        link.classList.toggle('active', pageNumber === currentPage);
                    });
                }

                paginationContainer.addEventListener('click', function (e) {
                    e.preventDefault();
                    const targetPage = parseInt(e.target.textContent);

                    if (!isNaN(targetPage)) {
                        window.location.href = updateQueryStringParameter(window.location.href, 'page', targetPage);
                    }
                });
                updateActivePage();
            });
        </script>
</x-admin-layout>
