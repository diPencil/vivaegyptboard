@php
    $editInterviewSchedulePermission = user()->permission('edit_interview_schedule');
    $deleteInterviewSchedulePermission = user()->permission('delete_interview_schedule');
    $viewInterviewSchedulePermission = user()->permission('view_interview_schedule');
    $reschedulePermission = user()->permission('reschedule_interview');
@endphp

<div id="task-detail-section">
    <h3 class="heading-h1 mb-3">{{ ($interview->jobApplication->full_name) }}</h3>
    <div class="d-flex project-header bg-white">

        <div class="mobile-close-overlay w-100 h-100" id="close-client-overlay"></div>
        <div class="project-menu d-lg-flex table-responsive" id="mob-client-detail">

            <a class="d-none close-it" href="javascript:;" id="close-client-detail">
                <i class="fa fa-times"></i>
            </a>
            @if ($interview->parent_id == null)
                <x-tab :href="route('interview-schedule.show', $interview->id)"
                       :text="$interview->stage ? $interview->stage->name : ''"
                       class="active"/>
            @else
                <x-tab :href="route('interview-schedule.show', $interview->parent_id)"
                       :text="$parentStage->stage ? $parentStage->stage->name : ''"
                       class="stage"/>
            @endif
            @foreach($childInterviews as $childInterview)
                @if ($interview->id == $childInterview->id)
                    <a href="{{ route('interview-schedule.show', $childInterview->id) }}"
                       class='text-dark-grey text-capitalize border-right-grey p-sub-menu active'><span>{{ $childInterview->stage->name }}</span></a>
                @else
                    <a href="{{ route('interview-schedule.show', $childInterview->id) }}"
                       class='text-dark-grey text-capitalize border-right-grey p-sub-menu'><span>{{ $childInterview->stage->name }}</span></a>
                @endif
            @endforeach

        </div>

        <a class="mb-0 d-block d-lg-none text-dark-grey ml-auto mr-2 border-left-grey"
           onclick="openClientDetailSidebar()"><i class="fa fa-ellipsis-v "></i></a>

    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card bg-white border-0 b-shadow-4">
                <div class="card-header bg-white  border-bottom-grey text-capitalize justify-content-between p-20">
                    <div class="row">
                        <div class="col-lg-10 col-10">
                            @if($attendees->status == 'hired' || $attendees->status == 'completed')
                                @foreach ($selected_employees as $attendee)
                                    @if (!in_array($attendee, $submitted) && $attendee == user()->id)
                                        <div class="d-flex flex-wrap">
                                            <x-forms.link-primary
                                                :link="route('evaluation.create', ['id' => $interview_schedule_id])"
                                                class="mr-3 openRightModal" icon="plus">
                                                @lang('add') @lang('recruit::modules.interviewSchedule.evaluation')
                                            </x-forms.link-primary>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                            <div class="row">
                                @if (in_array('Zoom', $worksuitePlugins))
                                    @if ($interview->video_type == 'zoom')
                                        @php
                                            if ($zoom_setting->meeting_app == 'in_app') {
                                                $url = route('zoom-meetings.start_meeting', $interview->meeting->id);
                                            } else {
                                                $url = user()->id == $interview->meeting->created_by ? $interview->meeting->start_link : $interview->meeting->join_link;
                                            }
                                            $nowDate = now(company()->timezone)->toDateString();
                                        @endphp
                                        <div class="col-md-2">
                                            @if (user()->id == $interview->meeting->created_by)
                                                @if ($interview->meeting->status == 'waiting')
                                                    @php
                                                        $nowDate = now(company()->timezone)->toDateString();
                                                        $meetingDate = $interview->meeting->start_date_time->toDateString();
                                                    @endphp

                                                    @if (isset($url) && (is_null($interview->meeting->occurrence_id) || $nowDate == $meetingDate))
                                                        <x-forms.link-primary target="_blank" :link="$url" icon="play">
                                                            @lang('recruit::modules.interviewSchedule.startInterview')
                                                        </x-forms.link-primary>
                                                    @endif

                                                @endif
                                            @else
                                                @if ($interview->meeting->status == 'waiting' || $interview->meeting->status == 'live')
                                                    @php
                                                        $nowDate = now(company()->timezone)->toDateString();
                                                        $meetingDate = $interview->meeting->start_date_time->toDateString();
                                                    @endphp

                                                    @if (isset($url) && (is_null($interview->meeting->occurrence_id) || $nowDate == $meetingDate))
                                                        <x-forms.link-primary target="_blank" :link="$url" icon="play">
                                                            @lang('recruit::modules.interviewSchedule.joinUrl')
                                                        </x-forms.link-primary>
                                                    @endif

                                                @endif

                                            @endif
                                        </div>
                                    @endif
                                @endif
                                @if ($editInterviewSchedulePermission == 'all'
                                    || ($editInterviewSchedulePermission == 'added' && $interview->added_by == user()->id)
                                    || ($editInterviewSchedulePermission == 'owned' && in_array(user()->id, $selected_employees))
                                    || ($editInterviewSchedulePermission == 'both' && (in_array(user()->id, $selected_employees) || $interview->added_by == user()->id)))
                                    @if ($interview->status != 'completed')
                                        <x-forms.button-secondary icon="check" data-status="completed"
                                                                class="change-interview-status mr-2 ml-2">
                                            @lang('recruit::modules.interviewSchedule.markStatusComplete')
                                        </x-forms.button-secondary>
                                        @endif
                                    @php
                                        $secEmp = [];
                                            foreach($interview->employees as $usrdt){
                                                $secEmp[] = $usrdt->id;
                                            }

                                        $employeeStatus = $recruit_employees->filter(function ($value, $key) use ($loggedEmployee)  {
                                            return $value->user_id == $loggedEmployee->id;
                                        })->first();
                                    @endphp

                                    @if (in_array($loggedEmployee->id, $secEmp) && $employeeStatus->user_accept_status == 'waiting' && $interview->status == 'pending')
                                        <button class="btn-primary rounded f-14 p-2 employeeResponse mr-2"
                                            data-response-id={{ $employeeStatus->id }}
                                                data-response-action="accept" href="javascript:;"><i class="fa fa-check mr-1"></i>@lang('recruit::modules.interviewSchedule.acceptInterview')
                                        </button>
                                        <button class="btn-secondary rounded f-14 p-2 employeeResponse"
                                            data-response-id={{ $employeeStatus->id }}
                                                data-response-action="refuse" href="javascript:;"><i class="fa fa-times mr-1"></i>@lang('recruit::modules.interviewSchedule.rejectInterview')
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-2 col-2 text-right">
                            @if ( $editInterviewSchedulePermission == 'all'
                                || ($editInterviewSchedulePermission == 'added' && $interview->added_by == user()->id)
                                || ($editInterviewSchedulePermission == 'owned' && in_array(user()->id, $selected_employees))
                                || ($editInterviewSchedulePermission == 'both' && (in_array(user()->id, $selected_employees) || $interview->added_by == user()->id))
                                ||($reschedulePermission == 'all'
                                || ($reschedulePermission == 'added' && $interview->added_by == user()->id)
                                || ($reschedulePermission == 'owned' && in_array(user()->id, $selected_employees))
                                || ($reschedulePermission == 'both' && (in_array(user()->id, $selected_employees) || $interview->added_by == user()->id)))
                                || ($deleteInterviewSchedulePermission == 'all'
                                || ($deleteInterviewSchedulePermission == 'added' && $interview->added_by == user()->id)
                                || ($deleteInterviewSchedulePermission == 'owned' && in_array(user()->id, $selected_employees))
                                || ($deleteInterviewSchedulePermission == 'both' && (in_array(user()->id, $selected_employees) || $interview->added_by == user()->id))))
                                <div class="dropdown">
                                    <button
                                        class="btn btn-lg f-14 px-2 py-1 text-dark-grey text-capitalize rounded  dropdown-toggle"
                                        type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-ellipsis-h"></i>
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                                         aria-labelledby="dropdownMenuLink" tabindex="0">
                                        @if ( $editInterviewSchedulePermission == 'all'
                                        || ($editInterviewSchedulePermission == 'added' && $interview->added_by == user()->id)
                                        || ($editInterviewSchedulePermission == 'owned' && in_array(user()->id, $selected_employees))
                                        || ($editInterviewSchedulePermission == 'both' && (in_array(user()->id, $selected_employees) || $interview->added_by == user()->id)))
                                            <a class="dropdown-item openRightModal"
                                               href="{{ route('interview-schedule.edit', $interview->id) }}">@lang('app.edit')</a>
                                        @endif

                                        @php
                                            $secEmp = [];
                                                foreach($interview->employees as $usrdt){
                                                    $secEmp[] = $usrdt->id;
                                                }

                                            $employeeStatus = $recruit_employees->filter(function ($value, $key) use ($loggedEmployee)  {
                                                return $value->user_id == $loggedEmployee->id;
                                            })->first();
                                        @endphp

                                        @if (in_array($loggedEmployee->id, $secEmp) && $employeeStatus->user_accept_status == 'waiting' && $interview->status == 'pending')
                                            <a class="dropdown-item employeeResponse"
                                               data-response-id={{ $employeeStatus->id }}
                                                   data-response-action="accept" href="javascript:;">@lang('recruit::modules.interviewSchedule.acceptInterview')
                                            </a>
                                            <a class="dropdown-item employeeResponse"
                                               data-response-id={{ $employeeStatus->id }}
                                                   data-response-action="reject" href="javascript:;">@lang('recruit::modules.interviewSchedule.rejectInterview')
                                            </a>
                                        @endif

                                        @if ($reschedulePermission == 'all'
                                        || ($reschedulePermission == 'added' && $interview->added_by == user()->id)
                                        || ($reschedulePermission == 'owned' && in_array(user()->id, $selected_employees))
                                        || ($reschedulePermission == 'both' && (in_array(user()->id, $selected_employees) || $interview->added_by == user()->id)))
                                            @if ($interview->status == 'pending')
                                                <a class="dropdown-item reschedule-interview"
                                                   data-user-id="{{ $interview->id }}">@lang('recruit::modules.interviewSchedule.reSchedule')</a>
                                            @endif
                                        @endif

                                        @if ($deleteInterviewSchedulePermission == 'all'
                                        || ($deleteInterviewSchedulePermission == 'added' && $interview->added_by == user()->id)
                                        || ($deleteInterviewSchedulePermission == 'owned' && in_array(user()->id, $selected_employees))
                                        || ($deleteInterviewSchedulePermission == 'both' && (in_array(user()->id, $selected_employees) || $interview->added_by == user()->id)))
                                            <a class="dropdown-item delete-table-row">@lang('app.delete')</a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-body">

                    <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                        <p class="mb-0 text-lightest f-14 w-30 text-capitalize">
                            @lang('recruit::modules.job.job')</p>
                        <p class="mb-0 text-dark-grey f-14 w-70 text-wrap p-0">
                            <a href="{{ route('jobs.show', [$interview->jobApplication->job->id])}}"
                               class="text-dark-grey openRightModal">{{ ($interview->jobApplication->job->title) }}</a>
                        </p>
                    </div>

                    <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                        <p class="mb-0 text-lightest f-14 w-30 text-capitalize">
                            @lang('recruit::modules.interviewSchedule.candidateName')</p>
                        <p class="mb-0 text-dark-grey f-14 w-70 text-wrap p-0">
                            <a href="{{ route('job-applications.show', [$interview->jobApplication->id])}}"
                               class="text-dark-grey openRightModal">{{ ($interview->jobApplication->full_name) }}</a>
                        </p>
                    </div>

                    <x-cards.data-row :label="__('recruit::modules.interviewSchedule.candidateEmail')"
                                      :value="$interview->jobApplication->email ?? '--'"/>
                    <x-cards.data-row :label="__('app.phone')"
                                      :value="$interview->jobApplication->phone ?? '--'"/>
                    <x-cards.data-row :label="__('recruit::modules.interviewSchedule.interviewType')"
                                      :value="ucwords($interview->interview_type) ?? '--'"/>

                    @if ($interview->interview_type == 'phone')
                        <x-cards.data-row :label="__('recruit::modules.interviewSchedule.interviewerPhone')"
                                          :value="$interview->phone ?? '--'"/>
                    @endif
                    
                    <x-cards.data-row :label="__('recruit::modules.interviewSchedule.startOn')"
                                      :value="$interview->schedule_date->setTimeZone(company()->timezone)->format($company->date_format. ' - ' . $company->time_format)"/>

                    <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                        <p class="mb-0 text-lightest f-14 w-30 text-capitalize">
                            @lang('recruit::modules.jobApplication.status')</p>
                        <p class="mb-0 text-dark-grey f-14 w-70 text-wrap p-0">
                            @if ($interview->status == 'pending')
                                <i class="fa fa-circle mr-1 text-yellow f-14"></i> {{ ($interview->status) }}
                            @elseif ($interview->status == 'completed')
                                <i class="fa fa-circle mr-1 text-blue f-14"></i>{{ ($interview->status) }}
                            @elseif ($interview->status == 'hired')
                                <i class="fa fa-circle mr-1 text-light-green f-14"></i>{{ ($interview->status) }}
                            @elseif ($interview->status == 'rejected')
                                <i class="fa fa-circle mr-1 text-brown f-14"></i>{{ ($interview->status) }}
                            @else
                                <i class="fa fa-circle mr-1 text-red f-14"></i>{{ ($interview->status) }}
                            @endif
                        </p>
                    </div>

                    <x-cards.data-row :label="__('recruit::modules.interviewSchedule.comment')"
                                      :value="$comments->comment ?? '--'" :html="true"/>

                    <div class="col-12 px-0 pb-3 d-lg-flex d-lg-flex d-block">
                        <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">
                            @lang('recruit::modules.interviewSchedule.assignedEmployee')</p>
                        <div class="row w-70">
                            @foreach ($recruit_employees as $item)
                                <div class="col-12">
                                    <div class="row mt-1">
                                        <div class="col-1">
                                            <p class="mb-0 text-dark-grey f-14">
                                            <div class="taskEmployeeImg rounded-circle">
                                                <img data-toggle="tooltip"
                                                     data-original-title="{{ $item->user->name }}"
                                                     src="{{ $item->user->image_url }}">
                                            </div>
                                            </p>
                                        </div>

                                        <div class="col-10">
                                            <p class="mb-0 text-dark-grey f-14">
                                                @if ($item->user_accept_status == 'waiting')
                                                    <x-status :value="__('recruit::modules.interviewSchedule.awaiting')"
                                                              color="yellow"/>
                                                @elseif ($item->user_accept_status == 'refuse')
                                                    <x-status :value="__('recruit::modules.interviewSchedule.refused')"
                                                              color="red"/>
                                                @elseif ($item->user_accept_status == 'accept')
                                                    <x-status :value="__('recruit::modules.interviewSchedule.accepted')"
                                                              color="dark-green"/>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>


                    @if ($interview->interview_type == 'video')
                        @if ($interview->video_type == 'other')
                            <x-cards.data-row :label="__('recruit::modules.interviewSchedule.link')"
                                              :value="$interview->other_link ?? '--'"/>
                        @endif

                        @if (in_array('Zoom', $worksuitePlugins))

                            @if ($interview->video_type == 'zoom')
                                <x-cards.data-row :label="__('recruit::modules.interviewSchedule.meetingName')"
                                                  :value="$interview->meeting->meeting_name ?? '--'"/>

                                <x-cards.data-row :label="__('recruit::modules.interviewSchedule.meetingStatus')"
                                                  :value="$interview->meeting->status ?? '--'"/>

                                <x-cards.data-row :label="__('modules.employees.employeePassword')"
                                                  :value="$interview->meeting->password ?? '--'"/>

                                <x-cards.data-row :label="__('recruit::modules.interviewSchedule.startOn')"
                                                  :value="$interview->schedule_date->setTimeZone(company()->timezone)->format($company->date_format. ' - ' . $company->time_format)"/>
                                                  
                                <x-cards.data-row :label="__('zoom::modules.zoommeeting.endOn')"
                                                  :value="$interview->meeting->end_date_time->format($company->date_format. ' - ' . $company->time_format)"/>

                                <x-cards.data-row :label="__('zoom::modules.zoommeeting.hostVideoStatus')"
                                                  :value="$interview->meeting->host_video ? __('app.enabled') : __('app.disabled')"/>

                                <div class="col-12 px-0 pb-3 d-flex">
                                    <p class="mb-0 text-lightest f-14 w-30 d-inline-block text-capitalize">
                                        @lang('recruit::modules.interviewSchedule.meetingHost')</p>
                                    <p class="mb-0 text-dark-grey f-14">
                                    <div class="taskEmployeeImg rounded-circle mr-1">
                                        <img data-toggle="tooltip"
                                             data-original-title="{{ $interview->meeting->host->name }}"
                                             src="{{ $interview->meeting->host->image_url }}">
                                    </div>
                                    </p>
                                </div>
                            @endif
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {

        $('body').on('click', '.stage', function () {
            event.preventDefault();

            $('.project-menu .p-sub-menu').removeClass('active');
            $(this).addClass('active');

            const requestUrl = this.href;
            console.log(requestUrl);

            $.easyAjax({
                url: requestUrl,
                blockUI: true,
                container: ".content-wrapper",
                historyPush: true,
                blockUI: true,
                success: function (response) {
                    if (response.status == "success") {
                        $('.content-wrapper').html(response.html);
                        init('.content-wrapper');
                    }
                }
            });
        });


        $('body').on('click', '.delete-table-row', function () {
            var id = $(this).data('user-id');
            var parentId = "{{ $interview->parent_id }}";
            if (parentId == '') {
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "@lang('recruit::messages.relatedInterviewdelete')",
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
                        var url = "{{ route('interview-schedule.destroy', $interview->id) }}";
                        url = url.replace(':id', id);
                        var token = "{{ csrf_token() }}";
                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            blockUI: true,
                            data: {
                                '_token': token,
                                '_method': 'DELETE'
                            },
                            success: function (response) {
                                if (response.status == 'success') {
                                    if ($(MODAL_XL).hasClass('show')) {
                                        $(MODAL_XL).modal('hide');
                                        window.location.reload();
                                    } else if ($(RIGHT_MODAL).hasClass('in')) {
                                        document.getElementById('close-task-detail').click();
                                        if ($('#interview-schedule-table').length) {
                                            window.LaravelDataTables["interview-schedule-table"].draw(false);
                                        } else {
                                            window.location.href = response.redirectUrl;
                                        }
                                    } else {
                                        window.location.href = response.redirectUrl;
                                    }
                                }
                            }
                        });
                    }
                });
            } else {
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
                        var url = "{{ route('interview-schedule.destroy', $interview->id) }}";
                        url = url.replace(':id', id);
                        var token = "{{ csrf_token() }}";
                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            blockUI: true,
                            data: {
                                '_token': token,
                                '_method': 'DELETE'
                            },
                            success: function (response) {
                                if (response.status == 'success') {
                                    if ($(MODAL_XL).hasClass('show')) {
                                        $(MODAL_XL).modal('hide');
                                        window.location.reload();
                                    } else if ($(RIGHT_MODAL).hasClass('in')) {
                                        document.getElementById('close-task-detail').click();
                                        if ($('#interview-schedule-table').length) {
                                            window.LaravelDataTables["interview-schedule-table"].draw(false);
                                        } else {
                                            window.location.href = response.redirectUrl;
                                        }
                                    } else {
                                        window.location.href = response.redirectUrl;
                                    }
                                }
                            }
                        });
                    }
                });
            }
        });

        $('body').on('click', '.employeeResponse', function () {

            var action = $(this).data('response-action');
            var responseId = $(this).data('response-id');
            var url = "{{ route('interview-schedule.employee_response') }}";

            if(action == 'accept'){
                var msg = "@lang('recruit::messages.acceptanceConfirm')";
            } else{
                var msg = "@lang('recruit::messages.rejectConfirm')";
            }

            Swal.fire({
                text: msg,
                title: "@lang('messages.sweetAlertTitle')",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "@lang('messages.confirm')",
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
                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        blockUI: true,
                        data: {
                            'action': action,
                            'responseId': responseId,
                            '_token': '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.status == 'success') {
                                window.location.reload();
                            }
                        }
                    });
                }
            });
        });

        //    change status
        $('body').on('click', '.change-interview-status', function () {
            var status = $(this).data('status');
            var id = '{{ $interview->id }}';
            var url = "{{ route('interview-schedule.change_interview_status') }}";
            var token = "{{ csrf_token() }}";
            $.easyAjax({
                url: url,
                type: "POST",
                async: false,
                data: {
                    '_token': token,
                    interviewId: id,
                    status: status,
                    sortBy: 'id'
                },
                success: function (data) {
                    window.location.reload();
                }
            })
        });

        $('body').on('click', '.delete-file', function () {
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
                    var url = "{{ route('interview-schedule.destroy', ':id') }}";
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {
                            '_token': token,
                            '_method': 'DELETE'
                        },
                        success: function (response) {
                            if (response.status == "success") {
                                $('#task-file-list').html(response.view);
                            }
                        }
                    });
                }
            });
        });
        init(RIGHT_MODAL);
    });

    $('body').off('click', ".reschedule-interview").on('click', '.reschedule-interview', function () {
        var id = $(this).data('user-id');
        const url = "{{ route('interview-schedule.reschedule') }}?id=" + id;
        $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
        $.ajaxModal(MODAL_LG, url);
    });

</script>
