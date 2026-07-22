<!-- TAB CONTENT START -->
<div class="tab-pane fade show active" role="tabpanel" aria-labelledby="nav-email-tab">
    @php
        $addTaskLinkPermission = user()->permission('add_task_links');
        $editTaskLinkPermission = user()->permission('edit_task_links');
        $deleteTaskLinkPermission = user()->permission('delete_task_links');
        $taskUsers = $task->users->pluck('id')->toArray();
        $userId = user()->id;

        $canAddTaskLink = (
            $addTaskLinkPermission == 'all'
            || ($addTaskLinkPermission == 'added' && $task->added_by == $userId)
            || ($addTaskLinkPermission == 'owned' && in_array($userId, $taskUsers))
            || ($addTaskLinkPermission == 'both' && (in_array($userId, $taskUsers) || $task->added_by == $userId))
        );
    @endphp

    @if ($canAddTaskLink)
        <div class="row p-20">
            <div class="col-md-12">
                <a class="f-15 f-w-500" href="javascript:;" id="add-task-link"><i
                        class="icons icon-plus font-weight-bold mr-1"></i>@lang('modules.tasks.addLink')
                </a>
            </div>
        </div>

        <x-form id="add-task-link-form" class="d-none">
            <div class="col-md-12 p-20">
                <div class="row">
                    <div class="col-md-6">
                        <x-forms.text :fieldLabel="__('modules.tasks.linkName')" fieldName="link_name" fieldId="link_name" fieldRequired="true" />
                    </div>
                    <div class="col-md-6">
                        <x-forms.text :fieldLabel="__('modules.tasks.linkUrl')" fieldName="link_url" fieldId="link_url" fieldRequired="true" />
                    </div>
                    <div class="col-md-12">
                        <div class="form-group my-3">
                            <x-forms.label fieldId="description" :fieldLabel="__('app.description')">
                            </x-forms.label>
                            <div id="link-description"></div>
                            <textarea name="description" id="description-text" class="d-none"></textarea>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="d-flex w-100 justify-content-start">
                            <x-forms.button-primary id="submit-task-link" icon="location-arrow" class="mr-3">
                                @lang('app.submit')
                            </x-forms.button-primary>
                            <x-forms.button-cancel id="cancel-task-link" class="border-0">
                                @lang('app.cancel')
                            </x-forms.button-cancel>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </x-form>
    @endif

    <div class="d-flex flex-wrap p-20" id="task-link-list">
        @forelse ($task->links as $link)
            @php
                $canEditTaskLink = (
                    $editTaskLinkPermission == 'all'
                    || ($editTaskLinkPermission == 'added' && $link->added_by == $userId)
                    || ($editTaskLinkPermission == 'owned' && $link->added_by == $userId && in_array($userId, $taskUsers))
                    || ($editTaskLinkPermission == 'both' && $link->added_by == $userId && (in_array($userId, $taskUsers) || $task->added_by == $userId))
                );

                $canDeleteTaskLink = (
                    $deleteTaskLinkPermission == 'all'
                    || ($deleteTaskLinkPermission == 'added' && $link->added_by == $userId)
                    || ($deleteTaskLinkPermission == 'owned' && $link->added_by == $userId && in_array($userId, $taskUsers))
                    || ($deleteTaskLinkPermission == 'both' && $link->added_by == $userId && (in_array($userId, $taskUsers) || $task->added_by == $userId))
                );
            @endphp
            <div class="card w-100 rounded-1 border-2 mb-3 p-2 comment task-link-card">
                <div class="card-horizontal flex-row flex-wrap">
                    <div class="card-img m-1 ml-0 mr-3">
                        <img src="{{ $link->addedBy->image_url ?? user()->image_url }}" alt="{{ $link->addedBy->name ?? '' }}" class="taskEmployeeImg rounded-circle" style="width: 35px; height: 35px;">
                    </div>
                    <div class="card-body border-0 pl-0 py-1">
                        <div class="row">
                            <div class="col-md-6 d-inline-flex">
                                <h4 class="card-title f-15 f-w-500 text-dark mr-3">{{ $link->addedBy->name ?? __('app.addedBy') }}</h4>
                                <span class="cursor-pointer card-date f-11 text-lightest mb-0 comment-time">
                                    {{ $link->created_at->diffForHumans() }}
                                </span>
                            </div>
                            @if ($canEditTaskLink || $canDeleteTaskLink)
                                <div class="col-md-6 d-inline-flex justify-content-end">
                                    <div class="dropdown ml-auto link-action">
                                        <button class="btn btn-lg f-14 p-0 text-lightest rounded dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-ellipsis-h"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0" aria-labelledby="dropdownMenuLink" tabindex="0">
                                            @if ($canEditTaskLink)
                                                <a class="cursor-pointer d-block text-dark-grey f-13 py-3 px-3 edit-task-link" href="javascript:;" data-row-id="{{ $link->id }}">@lang('app.edit')</a>
                                            @endif
                                            @if ($canDeleteTaskLink)
                                                <a class="cursor-pointer d-block text-dark-grey f-13 pb-3 px-3 delete-task-link" data-row-id="{{ $link->id }}" href="javascript:;">@lang('app.delete')</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <div class="card-text f-14 text-dark-grey mt-3">
                            <a href="{{ $link->link_url }}" target="_blank" class="font-weight-bold text-dark mb-1 d-block">{{ $link->link_name }}</a>
                            <a href="{{ $link->link_url }}" target="_blank" class="text-info f-12 mb-2 d-block" dir="ltr">{{ $link->link_url }}</a>
                            @if ($link->description && $link->description !== '<p><br></p>')
                                <div class="text-justify px-0 mt-2">
                                    {!! $link->description !!}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <x-cards.no-record icon="link" :message="__('modules.tasks.noTaskLinkFound')" />
        @endforelse
    </div>
</div>
<!-- TAB CONTENT END -->

<script>
    $(document).ready(function() {
        var quill;
        if ($('#link-description').length > 0) {
            quill = new Quill('#link-description', {
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['link']
                    ]
                },
                theme: 'snow'
            });
        }

        $('#add-task-link').click(function() {
            $(this).closest('.row').addClass('d-none');
            $('#add-task-link-form').removeClass('d-none');
        });

        $('#cancel-task-link').click(function() {
            $('#add-task-link-form').addClass('d-none');
            $('#add-task-link').closest('.row').removeClass('d-none');
            $('#add-task-link-form')[0].reset();
            if (quill) {
                quill.root.innerHTML = '';
            }
        });

        $('#submit-task-link').click(function() {
            var url = "{{ route('task-links.store', $task->id) }}";
            if (quill) {
                document.getElementById('description-text').value = quill.root.innerHTML;
            }
            $.easyAjax({
                url: url,
                container: '#add-task-link-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#submit-task-link",
                data: $('#add-task-link-form').serialize(),
                success: function(response) {
                    if (response.status == "success") {
                        $('#nav-tabContent').html(response.data.view);
                    }
                }
            })
        });

        $('body').off('click', '.edit-task-link').on('click', '.edit-task-link', function() {
            var id = $(this).data('row-id');
            var url = "{{ route('task-links.edit', [$task->id, ':id']) }}";
            url = url.replace(':id', id);
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('click', '.delete-task-link', function() {
            var id = $(this).data('row-id');
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.recoverRecord')",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "@lang('messages.confirmDelete')",
                cancelButtonText: "@lang('app.cancel')",
                customClass: {
                    confirmButton: 'btn btn-primary mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "{{ route('task-links.destroy', [$task->id, ':id']) }}";
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {
                            '_token': token,
                            '_method': 'DELETE'
                        },
                        success: function(response) {
                            if (response.status == "success") {
                                $('#nav-tabContent').html(response.data.view);
                            }
                        }
                    });
                }
            });
        });
    });
</script>
