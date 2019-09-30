@extends('voyager::master')

@section('page_title', __('voyager::generic.viewing').' '.$dataType->getTranslatedAttribute('display_name_plural'))

@section('page_header')
    <div class="container-fluid">
        <h1 class="page-title">
            <i class="{{ $dataType->icon }}"></i> {{ $dataType->getTranslatedAttribute('display_name_plural') }}
        </h1>
        @can('add', app($dataType->model_name))
            <a href="{{ route('voyager.'.$dataType->slug.'.create') }}" class="btn btn-success btn-add-new">
                <i class="voyager-plus"></i> <span>{{ __('voyager::generic.add_new') }}</span>
            </a>
        @endcan
        @can('delete', app($dataType->model_name))
            @include('voyager::partials.bulk-delete')
        @endcan
        @can('edit', app($dataType->model_name))
            @if(isset($dataType->order_column) && isset($dataType->order_display_column))
                <a href="{{ route('voyager.'.$dataType->slug.'.order') }}" class="btn btn-primary btn-add-new">
                    <i class="voyager-list"></i> <span>{{ __('voyager::bread.order') }}</span>
                </a>
            @endif
        @endcan
        @can('delete', app($dataType->model_name))
            @if($usesSoftDeletes)
                <input type="checkbox" @if ($showSoftDeleted) checked @endif id="show_soft_deletes" data-toggle="toggle" data-on="{{ __('voyager::bread.soft_deletes_off') }}" data-off="{{ __('voyager::bread.soft_deletes_on') }}">
            @endif
        @endcan
        @foreach($actions as $action)
            @if (method_exists($action, 'massAction'))
                @include('voyager::bread.partials.actions', ['action' => $action, 'data' => null])
            @endif
        @endforeach
        @include('voyager::multilingual.language-selector')
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        @if ($isServerSide)
                            <form method="get" class="form-search">
                                <div id="search-input">
                                    <div class="col-2">
                                        <select id="search_key" name="key">
                                            @foreach($searchNames as $key => $name)
                                                <option value="{{ $key }}" @if($search->key == $key || (empty($search->key) && $key == $defaultSearchKey)) selected @endif>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-2">
                                        <select id="filter" name="filter">
                                            <option value="contains" @if($search->filter == "contains") selected @endif>contains</option>
                                            <option value="equals" @if($search->filter == "equals") selected @endif>=</option>
                                        </select>
                                    </div>
                                    <div class="input-group col-md-12">
                                        <input type="text" class="form-control" placeholder="{{ __('voyager::generic.search') }}" name="s" value="{{ $search->value }}">
                                        <span class="input-group-btn">
                                            <button class="btn btn-info btn-lg" type="submit">
                                                <i class="voyager-search"></i>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                                @if (Request::has('sort_order') && Request::has('order_by'))
                                    <input type="hidden" name="sort_order" value="{{ Request::get('sort_order') }}">
                                    <input type="hidden" name="order_by" value="{{ Request::get('order_by') }}">
                                @endif
                            </form>
                        @endif

                        @php
                            $columns = [];
                            $tableData = [];
                            $tableHeader = [];
                            $tableHeader[]['title'] = '';
                            if ($showCheckboxColumn) {
                                $columns[] = [
                                    'className' => 'hidden-xs bulkaction',
                                    'name' => 'bulkdelete',
                                ];
                            }
                            foreach ($dataType->browseRows as $row) {
                                $columns[] = [
                                    'className' => $row->details->display->class->browse ?? null,
                                    'name' => $row->field,
                                ];
                            }
                            $columns[] = [
                                'className' => 'bread-actions',
                                'name' => 'bread-actions',
                            ];
                            foreach ($dataTypeContent as $data) {
                                $item = [];
                                if ($showCheckboxColumn) {
                                    $item[] = '<input type="checkbox" name="row_id" id="checkbox_' . $data->getKey() . '" value="' . $data->getKey() . '">';
                                }
                                foreach ($dataType->browseRows as $row) {
                                    $field = '';
                                    $filter = false;

                                    if ($data->{$row->field.'_browse'}) {
                                        $data->{$row->field} = $data->{$row->field.'_browse'};
                                    }
                                    $options = $row->details;
                                    $classes = !empty($options->browse->tbody->classes) ? implode(' ', $options->browse->tbody->classes) : '';
                                    if (isset($options->view)) {
                                        $field = getIncludeContent(get_defined_vars(), $options->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $data->{$row->field}, 'action' => 'browse', 'view' => 'browse', 'options' => $row->details]);
                                    } elseif ($row->type == 'image') {
                                        $src = (!filter_var($data->{$row->field}, FILTER_VALIDATE_URL)) ? Voyager::image( $data->{$row->field} ) : $data->{$row->field};
                                        $field = '<img src="'.$src.'" style="max-height:20px">';
                                    } elseif ($row->type == 'relationship') {
                                        $field = getIncludeContent(get_defined_vars(), 'voyager::formfields.relationship', ['view' => 'browse', 'options' => $options]);
                                    } elseif ($row->type == 'select_multiple') {
                                        $filter = true;
                                        if (property_exists($options, 'relationship')) {
                                            foreach ($data->{$row->field} as $item) {
                                                $field .= $item->{$row->field};
                                            }
                                        } elseif (property_exists($options, 'options')) {
                                            if (!empty(json_decode($data->{$row->field}))) {
                                                foreach (json_decode($data->{$row->field}) as $item) {
                                                    if (!empty($options->options->{$item})) {
                                                        $field .= $options->options->{$item} . ', ';
                                                    }
                                                }
                                                $field = rtrim($field, ', ');
                                            } else {
                                                $field = __('voyager::generic.none');
                                            }
                                        }
                                    } elseif ($row->type == 'multiple_checkbox' && property_exists($options, 'options')) {
                                        $field = true;

                                        if (count(json_decode($data->{$row->field})) > 0) {
                                            foreach (json_decode($data->{$row->field}) as $item) {
                                                if ($options->options->{$item}) {
                                                    $field .= $options->options->{$item} . ', ';
                                                }
                                            }
                                            $field = rtrim($field, ', ');
                                        } else {
                                            $field = __('voyager::generic.none');
                                        }
                                    } elseif (($row->type == 'select_dropdown' || $row->type == 'radio_btn') && property_exists($options, 'options')) {
                                        $field = $options->options->{$data->{$row->field}} ?? '';
                                    } elseif ($row->type == 'date' || $row->type == 'timestamp') {
                                        $filter = true;
                                        $field = ($options && property_exists($options, 'format') && !is_null($data->{$row->field})) ? \Carbon\Carbon::parse($data->{$row->field})->formatLocalized($options->format) : $data->{$row->field};
                                    } elseif ($row->type == 'checkbox') {
                                        if ($options && property_exists($options, 'on') && property_exists($options, 'off')) {
                                            if ($data->{$row->field}) {
                                                $field = '<span class="label label-info">' . $options->on . '</span>';
                                            } else {
                                                $field = '<span class="label label-primary">' . $options->off . '</span>';
                                            }
                                        } else {
                                            $filter = true;
                                            $field = $data->{$row->field};
                                        }
                                    } elseif ($row->type == 'color') {
                                        $field = '<span class="badge badge-lg" style="background-color: ' . htmlentities($data->{$row->field}) . '">' . htmlentities($data->{$row->field}) . '</span>';
                                    } elseif ($row->type == 'text') {
                                        $field = getIncludeContent(get_defined_vars(), 'voyager::multilingual.input-hidden-bread-browse');
                                        $field .= '<div>'.htmlentities(mb_strlen( $data->{$row->field} ) > 200 ? mb_substr($data->{$row->field}, 0, 200) . ' ...' : $data->{$row->field}).'</div>';
                                    } elseif ($row->type == 'text_area') {
                                        $field = getIncludeContent(get_defined_vars(), 'voyager::multilingual.input-hidden-bread-browse');
                                        $field .= '<div>'.htmlentities(mb_strlen( $data->{$row->field} ) > 200 ? mb_substr($data->{$row->field}, 0, 200) . ' ...' : $data->{$row->field}).'</div>';
                                    } elseif ($row->type == 'file' && !empty($data->{$row->field}) ) {
                                        $field = getIncludeContent(get_defined_vars(), 'voyager::multilingual.input-hidden-bread-browse');
                                        if (json_decode($data->{$row->field})) {
                                            foreach (json_decode($data->{$row->field}) as $file) {
                                                $href = Storage::disk(config('voyager.storage.disk'))->url($file->download_link) ?? '';
                                                $field .= '<a href="'.$href.'" target="_blank">';
                                                $field .= htmlentities($file->original_name) ?? '';
                                                $field .= '</a><br/>';
                                            }
                                        } else {
                                            $href = Storage::disk(config('voyager.storage.disk'))->url($data->{$row->field});
                                            $field .= '<a href="'.$href.'" target="_blank">Download</a>';
                                        }
                                    } elseif ($row->type == 'rich_text_box') {
                                        $field = getIncludeContent(get_defined_vars(), 'voyager::multilingual.input-hidden-bread-browse');
                                        $field .= mb_strlen( strip_tags($data->{$row->field}, '<b><i><u>') ) > 200 ? mb_substr(strip_tags($data->{$row->field}, '<b><i><u>'), 0, 200) . ' ...' : strip_tags($data->{$row->field}, '<b><i><u>');
                                    } elseif ($row->type == 'coordinates') {
                                        $field = getIncludeContent(get_defined_vars(), 'voyager::partials.coordinates-static-image');
                                    } elseif($row->type == 'multiple_images') {
                                        $images = json_decode($data->{$row->field});
                                        if ($images) {
                                            $images = array_slice($images, 0, 3);
                                            foreach ($images as $image) {
                                                $src = (!filter_var($image, FILTER_VALIDATE_URL)) ? Voyager::image( $image ) : $image;
                                                $field .= '<img src="'.$src.'" style="width:50px">';
                                            }
                                        }
                                    } elseif ($row->type == 'media_picker') {
                                        if (is_array($data->{$row->field})) {
                                            $files = $data->{$row->field};
                                        } else {
                                            $files = json_decode($data->{$row->field});
                                        }

                                        if ($files) {
                                            if (property_exists($row->details, 'show_as_images') && $options->show_as_images) {
                                                foreach (array_slice($files, 0, 3) as $file) {
                                                    $src = ( !filter_var($file, FILTER_VALIDATE_URL)) ? Voyager::image( $file ) : $file;
                                                    $field .= '<img src="'.$src.'" style="width:50px">';
                                                }
                                            } else {
                                                $field = '<ul>';
                                                foreach (array_slice($files, 0, 3) as $file) {
                                                    $field .= '<li>'.htmlentities($file).'</li>';
                                                }
                                                $field .= '</ul>';
                                            }
                                            if (count($files) > 3) {
                                                $field .= __('voyager::media.files_more', ['count' => (count($files) - 3)]);
                                            }
                                        } elseif (is_array($files) && count($files) == 0) {
                                            $field = trans_choice('voyager::media.files', 0);
                                        } elseif ($data->{$row->field} != '') {
                                            if (property_exists($row->details, 'show_as_images') && $row->details->show_as_images) {
                                                $src = ( !filter_var($data->{$row->field}, FILTER_VALIDATE_URL)) ? Voyager::image( $data->{$row->field} ) : $data->{$row->field};
                                                $field = '<img src="'.$src.'" style="width:50px">';
                                            } else {
                                                $field = htmlentities($data->{$row->field});
                                            }
                                        } else {
                                            $field = trans_choice('voyager::media.files', 0);
                                        }
                                    } else {
                                        $field = getIncludeContent(get_defined_vars(), 'voyager::multilingual.input-hidden-bread-browse');
                                        $field .= htmlentities($data->{$row->field});
                                    }

                                    if ($filter) {
                                        $field = htmlentities($field);
                                    }

                                    $item[] = $field;
                                }

                                $field = '';
                                foreach ($actions as $action) {
                                    if (!method_exists($action, 'massAction')) {
                                        $field .= getIncludeContent(get_defined_vars(), 'voyager::bread.partials.actions', ['action' => $action]);
                                    }
                                }

                                $item[] = $field;
                                $tableData[] = $item;
                            }
                        @endphp

                        <div class="table-responsive">
                            <table id="dataTable" class="table table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        @if($showCheckboxColumn)
                                            <th class="dt-not-orderable">
                                                <input type="checkbox" class="select_all">
                                            </th>
                                        @endif
                                        @foreach($dataType->browseRows as $row)
                                        <th class="{{ $row->details->browse->thead->class ?? '' }}">
                                            @if ($isServerSide && $row->type !== 'relationship')
                                                <a href="{{ $row->sortByUrl($orderBy, $sortOrder) }}">
                                            @endif
                                            {{ $row->getTranslatedAttribute('display_name') }}
                                            @if ($isServerSide)
                                                @if ($row->isCurrentSortField($orderBy))
                                                    @if ($sortOrder == 'asc')
                                                        <i class="voyager-angle-up pull-right"></i>
                                                    @else
                                                        <i class="voyager-angle-down pull-right"></i>
                                                    @endif
                                                @endif
                                                </a>
                                            @endif
                                        </th>
                                        @endforeach
                                        <th class="actions text-right dt-not-orderable">{{ __('voyager::generic.actions') }}</th>
                                    </tr>
                                </thead>

                                @if ($isServerSide)
                                <tbody>
                                    @foreach($tableData as $data)
                                    <tr>
                                        @foreach($data as $row)
                                            <td>{!! $row !!}</td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                                @endif
                            </table>
                        </div>
                        @if ($isServerSide)
                            <div class="pull-left">
                                <div role="status" class="show-res" aria-live="polite">{{ trans_choice(
                                    'voyager::generic.showing_entries', $dataTypeContent->total(), [
                                        'from' => $dataTypeContent->firstItem(),
                                        'to' => $dataTypeContent->lastItem(),
                                        'all' => $dataTypeContent->total()
                                    ]) }}</div>
                            </div>
                            <div class="pull-right">
                                {{ $dataTypeContent->appends([
                                    's' => $search->value,
                                    'filter' => $search->filter,
                                    'key' => $search->key,
                                    'order_by' => $orderBy,
                                    'sort_order' => $sortOrder,
                                    'showSoftDeleted' => $showSoftDeleted,
                                ])->links() }}
                            </div>
                        @else
                            <script>
                                var dataTableColumns = {!! json_encode($columns) !!};
                                var dataTableData = {!! json_encode($tableData) !!};
                                var dataTableOrder = {!! json_encode($orderColumn) !!};
                            </script>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Single delete modal --}}
    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i> {{ __('voyager::generic.delete_question') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}?</h4>
                </div>
                <div class="modal-footer">
                    <form action="#" id="delete_form" method="POST">
                        {{ method_field('DELETE') }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-danger pull-right delete-confirm" value="{{ __('voyager::generic.delete_confirm') }}">
                    </form>
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
@stop

@section('css')
@if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
    <link rel="stylesheet" href="{{ voyager_asset('lib/css/responsive.dataTables.min.css') }}">
@endif
@stop

@section('javascript')
    <!-- DataTables -->
    @if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
        <script src="{{ voyager_asset('lib/js/dataTables.responsive.min.js') }}"></script>
    @endif
    <script>
        var table;
        $(document).ready(function () {
            @if (!$dataType->server_side)
                table = $('#dataTable').on('draw.dt', function () {
                    $('td').on('click', '.delete', function (e) {
                        $('#delete_form')[0].action = '{{ route('voyager.'.$dataType->slug.'.destroy', '__id') }}'.replace('__id', $(this).data('id'));
                        $('#delete_modal').modal('show');
                    });
                    $('input[name="row_id"]').on('change', function () {
                        var ids = [];
                        $('input[name="row_id"]').each(function() {
                            if ($(this).is(':checked')) {
                                ids.push($(this).val());
                            }
                        });
                        $('.selected_ids').val(ids);
                    });
                }).DataTable({!! preg_replace("/\"(dataTable(Columns|Data|Order))\"/", "$1", json_encode(
                    array_merge([
                        "columns" => 'dataTableColumns',
                        "data" => 'dataTableData',
                        "order" => 'dataTableOrder',
                        "language" => __('voyager::datatable'),
                        "columnDefs" => [
                            ['targets' => 'dt-not-orderable', 'searchable' =>  false, 'orderable' => false],
                        ],
                    ],
                    config('voyager.dashboard.data_tables', []))
                , true)) !!});
            @else
                $('#search-input select').select2({
                    minimumResultsForSearch: Infinity
                });

                var deleteFormAction;
                $('td').on('click', '.delete', function (e) {
                    $('#delete_form')[0].action = '{{ route('voyager.'.$dataType->slug.'.destroy', '__id') }}'.replace('__id', $(this).data('id'));
                    $('#delete_modal').modal('show');
                });
            @endif

            @if ($isModelTranslatable)
                $('.side-body').multilingual();
                //Reinitialise the multilingual features when they change tab
                $('#dataTable').on('draw.dt', function(){
                    $('.side-body').data('multilingual').init();
                })
            @endif
            $('.select_all').on('click', function(e) {
                $('input[name="row_id"]').prop('checked', $(this).prop('checked')).trigger('change');
            });
        });

        @if($usesSoftDeletes)
            @php
                $params = [
                    's' => $search->value,
                    'filter' => $search->filter,
                    'key' => $search->key,
                    'order_by' => $orderBy,
                    'sort_order' => $sortOrder,
                ];
            @endphp
            $(function() {
                $('#show_soft_deletes').change(function() {
                    if ($(this).prop('checked')) {
                        $('#dataTable').before('<a id="redir" href="{{ (route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 1]), true)) }}"></a>');
                    }else{
                        $('#dataTable').before('<a id="redir" href="{{ (route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 0]), true)) }}"></a>');
                    }

                    $('#redir')[0].click();
                })
            })
        @endif
        $('input[name="row_id"]').on('change', function () {
            var ids = [];
            $('input[name="row_id"]').each(function() {
                if ($(this).is(':checked')) {
                    ids.push($(this).val());
                }
            });
            $('.selected_ids').val(ids);
        });
    </script>
@stop
