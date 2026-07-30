<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">
        @lang('modules.updateEmployeeSchedule')
    </h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-form id="attendance-container">
        <input type="hidden" name="shift_date" value="{{ $date }}">
        <input type="hidden" name="user_id" value="{{ $employee->id }}">
        @if (!is_null($shiftSchedule))
            @method('PUT')
        @endif
        <div class="row">
            <div class="col-sm-12">
                <h3 class="heading-h3 mb-3">@lang('app.date'):
                    {{ \Carbon\Carbon::parse($date)->translatedFormat(company()->date_format) }}
                    ({{ \Carbon\Carbon::parse($date)->translatedFormat('l') }})</h3>
            </div>
            <div class="col-sm-12">
                <x-employee :user="$employee" />
            </div>

            @if (!is_null($shiftSchedule) && !is_null($shiftSchedule->pendingRequestChange))
                <div class="col-sm-12 mt-3">
                    <p class="mb-1">@lang('modules.attendance.requestFor')</p>
                    <span class="badge badge-info" style="background-color: {{ $shiftSchedule->pendingRequestChange->shift->color }}">{{ $shiftSchedule->pendingRequestChange->shift->shift_name }}</span>
                </div>
                <div class="col-sm-12 mt-3">
                    <p class="mb-1">@lang('app.reason')</p>
                    <p>{{ $shiftSchedule->pendingRequestChange->reason ?? '--' }}</p>
                </div>
            @else
                <div class="col-sm-12">
                    <x-forms.select fieldName="status_type" fieldId="status_type" :fieldLabel="__('modules.dayStatus')">
                        <option value="working_shift" {{ !is_null($shiftSchedule) && $shiftSchedule->status_type == 'working_shift' ? 'selected' : '' }}>@lang('modules.workingShift')</option>
                        <option value="day_off" {{ !is_null($shiftSchedule) && $shiftSchedule->status_type == 'day_off' ? 'selected' : '' }}>@lang('modules.dayOff')</option>
                        <option value="rotation_day_off" {{ !is_null($shiftSchedule) && $shiftSchedule->status_type == 'rotation_day_off' ? 'selected' : '' }}>@lang('modules.rotationDayOff')</option>
                        <option value="unauthorized_absence" {{ !is_null($shiftSchedule) && $shiftSchedule->status_type == 'unauthorized_absence' ? 'selected' : '' }}>@lang('modules.unauthorizedAbsence')</option>
                        <option value="half_day" {{ !is_null($shiftSchedule) && $shiftSchedule->status_type == 'half_day' ? 'selected' : '' }}>@lang('modules.halfDay')</option>
                        <option value="early_departure" {{ !is_null($shiftSchedule) && $shiftSchedule->status_type == 'early_departure' ? 'selected' : '' }}>@lang('modules.earlyDeparture')</option>
                        <option value="late_arrival" {{ !is_null($shiftSchedule) && $shiftSchedule->status_type == 'late_arrival' ? 'selected' : '' }}>@lang('modules.lateArrival')</option>
                        <option value="external_assignment" {{ !is_null($shiftSchedule) && $shiftSchedule->status_type == 'external_assignment' ? 'selected' : '' }}>@lang('modules.externalAssignment')</option>
                    </x-forms.select>

                </div>

                <!-- Rotation Block -->
                <div class="col-sm-12 rotation-block" style="display: none;">
                    <div class="row">
                        <div class="col-sm-6">
                            <x-forms.select fieldName="replacement_user_id" fieldId="replacement_user_id" :fieldLabel="__('modules.replacementEmployee')" fieldRequired="true" search="true">
                                <option value="">--</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ (!is_null($shiftSchedule) && $shiftSchedule->replacement_user_id == $user->id) ? 'selected' : '' }} data-content="<x-employee :user='$user' />"></option>
                                @endforeach
                            </x-forms.select>
                        </div>
                        <div class="col-sm-6">
                            <x-forms.select fieldName="replacement_shift_id" fieldId="replacement_shift_id" :fieldLabel="__('modules.replacementShift')" fieldRequired="true">
                                <option value="">--</option>
                                @foreach ($employeeShifts as $item)
                                    @if ($item->shift_name != 'Day Off')
                                        <option value="{{ $item->id }}" {{ (!is_null($shiftSchedule) && $shiftSchedule->replacement_shift_id == $item->id) ? 'selected' : '' }} data-content="<i class='fa fa-circle mr-2' style='color: {{ $item->color }}'></i> {{ $item->shift_name }} [{{$item->office_start_time}} - {{$item->office_end_time}}]"></option>
                                    @endif
                                @endforeach
                            </x-forms.select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 roster-shift-block" style="display: none;">
                    <x-forms.select fieldName="employee_shift_id" fieldId="employee_shift_id" :fieldLabel="__('modules.attendance.shift')">
                        @foreach ($employeeShifts as $item)
                            @if($item->office_open_days == '' || in_array($day, json_decode($item->office_open_days, true) ?? []))
                                @if ($item->shift_type == 'strict')
                                  <option data-is-day-off="{{ $item->shift_name == 'Day Off' ? '1' : '0' }}" data-content="<i class='fa fa-circle mr-2' style='color: {{ $item->color }}'></i> {{ ($item->shift_name != 'Day Off') ? $item->shift_name : __('modules.attendance.' . str($item->shift_name)->camel()) }} {{ ($item->shift_name != 'Day Off') ? ' ['.$item->office_start_time.' - '.$item->office_end_time.']' : ''}}"
                                      {{ !is_null($shiftSchedule) && $shiftSchedule->employee_shift_id == $item->id ? 'selected' : '' }}
                                      value="{{ $item->id }}">{{ ($item->shift_name != 'Day Off') ? $item->shift_name : __('modules.attendance.' . str($item->shift_name)->camel()) }} {{ ($item->shift_name != 'Day Off') ? ' ['.$item->office_start_time.' - '.$item->office_end_time.']' : ''}}</option>                                    
                                @else
                                  <option data-is-day-off="{{ $item->shift_name == 'Day Off' ? '1' : '0' }}" data-content="<i class='fa fa-circle mr-2' style='color: {{ $item->color }}'></i> {{ ($item->shift_name != 'Day Off') ? $item->shift_name : __('modules.attendance.' . str($item->shift_name)->camel()) }} {{ ($item->shift_name != 'Day Off') ? ' ['.$item->flexible_total_hours.' '.__('app.hrs').']' : ''}}"
                                      {{ !is_null($shiftSchedule) && $shiftSchedule->employee_shift_id == $item->id ? 'selected' : '' }}
                                      value="{{ $item->id }}">{{ ($item->shift_name != 'Day Off') ? $item->shift_name : __('modules.attendance.' . str($item->shift_name)->camel()) }} {{ ($item->shift_name != 'Day Off') ? ' ['.$item->office_start_time.' - '.$item->office_end_time.']' : ''}}</option>

                                @endif
                            @endif
                        @endforeach
                    </x-forms.select>
                </div>
                <div class="col-sm-12 roster-details" style="display:none;">
                    <div class="row">
                        <div class="col-sm-12 mt-2">
                            <x-forms.text fieldName="reason" fieldId="reason" :fieldLabel="__('app.reason')" :fieldValue="!is_null($shiftSchedule) ? $shiftSchedule->reason : ''" />
                        </div>
                        <div class="col-sm-6 mt-2">
                            <x-forms.select fieldName="approved_by" fieldId="approved_by" :fieldLabel="__('modules.approvedBy')">
                                <option value="">--</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ !is_null($shiftSchedule) && $shiftSchedule->approved_by == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </x-forms.select>
                        </div>
                        <div class="col-sm-12 mt-2 assignment-location" style="display:none;">
                            <x-forms.text fieldName="assignment_location" fieldId="assignment_location" :fieldLabel="__('modules.assignmentLocation')" :fieldValue="!is_null($shiftSchedule) ? $shiftSchedule->assignment_location : ''" />
                        </div>
                        <div class="col-sm-6 mt-2 assignment-time" style="display:none;">
                            <x-forms.timepicker fieldName="assignment_start_time" fieldId="assignment_start_time" :fieldLabel="__('modules.assignmentStart')" :fieldValue="!is_null($shiftSchedule) ? $shiftSchedule->assignment_start_time : ''" />
                        </div>
                        <div class="col-sm-6 mt-2 assignment-time" style="display:none;">
                            <x-forms.timepicker fieldName="assignment_end_time" fieldId="assignment_end_time" :fieldLabel="__('modules.assignmentEnd')" :fieldValue="!is_null($shiftSchedule) ? $shiftSchedule->assignment_end_time : ''" />
                        </div>
                        <div class="col-sm-6 mt-2 half-day-block" style="display:none;">
                            <x-forms.select fieldName="half_day_period" fieldId="half_day_period" :fieldLabel="__('modules.halfDay')">
                                <option value="first_half">{{ __('modules.halfDayFirst') }}</option>
                                <option value="second_half">{{ __('modules.halfDaySecond') }}</option>
                            </x-forms.select>
                        </div>
                        <div class="col-sm-6 mt-2 permitted-times" style="display:none;">
                            <x-forms.timepicker fieldName="permitted_arrival_time" fieldId="permitted_arrival_time" :fieldLabel="__('modules.permittedArrival')" :fieldValue="!is_null($shiftSchedule) ? $shiftSchedule->permitted_arrival_time : ''" />
                        </div>
                        <div class="col-sm-6 mt-2 permitted-times" style="display:none;">
                            <x-forms.timepicker fieldName="permitted_exit_time" fieldId="permitted_exit_time" :fieldLabel="__('modules.permittedExit')" :fieldValue="!is_null($shiftSchedule) ? $shiftSchedule->permitted_exit_time : ''" />
                        </div>
                        <div class="col-sm-12 mt-2">
                            <x-forms.file class="mr-0 mr-lg-2 mr-md-2 cropper" :fieldLabel="__('app.menu.addFile')" fieldName="file" fieldId="file" :fieldValue="(!is_null($shiftSchedule) && $shiftSchedule->file ? $shiftSchedule->file_url : '')" />
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <x-forms.file class="mr-0 mr-lg-2 mr-md-2 cropper" :fieldLabel="__('app.menu.addFile')" fieldName="file" fieldId="file" :fieldValue="(!is_null($shiftSchedule) && $shiftSchedule->file ? $shiftSchedule->file_url : '')" />
                    @if (!is_null($shiftSchedule) && $shiftSchedule->file)
                        <x-cards.data-row :label="__('app.downloadFile')" :value="'<a href='.$shiftSchedule->download_file_url.' download>'.$shiftSchedule->download_file_url.'</a>'" />
                    @endif
                </div>
            @endif
        </div>
    </x-form>
</div>

<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.close')</x-forms.button-cancel>
    @if (!is_null($shiftSchedule))
        @if (!is_null($shiftSchedule->pendingRequestChange))
            <x-forms.button-secondary class="mr-3 decline-request" icon="times" data-request-id="{{ $shiftSchedule->pendingRequestChange->id }}">@lang('app.decline')</x-forms.button-secondary>
            <x-forms.button-primary icon="check" class="approve-request" icon="check" data-request-id="{{ $shiftSchedule->pendingRequestChange->id }}">@lang('app.approve')</x-forms.button-primary>
        @else
            <x-forms.button-secondary id="delete-shift" class="mr-3" icon="trash">@lang('app.delete')</x-forms.button-secondary>
            <x-forms.button-primary id="save-shift" icon="check">@lang('app.save')</x-forms.button-primary>
        @endif
    @else
        <x-forms.button-primary id="save-shift" icon="check">@lang('app.save')</x-forms.button-primary>
    @endif

</div>

<script>
    $(document).ready(function() {
        function updateRosterVisibility(){
            var status = $('#status_type').val();
            
            if (status == 'rotation_day_off') {
                $('.rotation-block').show();
            } else {
                $('.rotation-block').hide();
            }

            // Handle Employee Shift Dropdown Options
            var shiftSelect = $('#employee_shift_id');
            var hasWorkingShiftSelected = false;

            shiftSelect.find('option').each(function() {
                var isDayOff = $(this).attr('data-is-day-off') === '1';
                if (status === 'working_shift') {
                    if (isDayOff) {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);
                        if ($(this).is(':selected')) {
                            hasWorkingShiftSelected = true;
                        }
                    }
                } else {
                    $(this).prop('disabled', false);
                }
            });

            if (status === 'working_shift' && !hasWorkingShiftSelected) {
                var firstValid = shiftSelect.find('option:not(:disabled):first').val();
                if (firstValid) {
                    shiftSelect.val(firstValid);
                }
            }
            
            if (shiftSelect.hasClass('selectpicker')) {
                shiftSelect.selectpicker('refresh');
            }

            if(status === 'working_shift'){
                $('.roster-shift-block').show();
                $('.roster-details').hide();
            } else if(status === 'day_off'){
                $('.roster-shift-block').hide();
                $('.roster-details').hide();
            } else {
                $('.roster-shift-block').hide();
                $('.roster-details').show();
            }

            if(status === 'half_day'){
                $('.half-day-block').show();
            } else {
                $('.half-day-block').hide();
            }

            if(status === 'early_departure' || status === 'late_arrival'){
                $('.permitted-times').show();
            } else {
                $('.permitted-times').hide();
            }

            if(status === 'external_assignment'){
                $('.assignment-time').show();
                $('.roster-details .assignment-location').show();
            } else {
                $('.assignment-time').hide();
                $('.roster-details .assignment-location').hide();
            }
        }

        $('#status_type').on('change', updateRosterVisibility);
        updateRosterVisibility();

        $('#save-shift').click(function() {
            @if (!is_null($shiftSchedule))
                var url = "{{ route('shifts.update', $shiftSchedule->id) }}";
            @else
                var url = "{{ route('shifts.store') }}";
            @endif

            $.easyAjax({
                url: url,
                type: "POST",
                container: '#attendance-container',
                blockUI: true,
                disableButton: true,
                buttonSelector: "#save-shift",
                data: $('#attendance-container').serialize(),
                file: true,
                success: function(response) {
                    if (response.status == 'success') {
                        if (typeof loadData !== 'undefined' && typeof loadData === 'function') {
                            loadData();
                        } else {
                            showTable();
                        }
                        $(MODAL_DEFAULT).modal('hide');
                    }
                }
            })
        });

        $('#delete-shift').click(function() {
            @if (!is_null($shiftSchedule))
                var url = "{{ route('shifts.destroy', $shiftSchedule->id) }}";
            @else
                var url = "{{ route('shifts.store') }}";
            @endif

            var formData = $('#attendance-container').serialize();
            formData = formData.replace('&_method=PUT', '&_method=DELETE');

            $.easyAjax({
                url: url,
                type: "POST",
                container: '#attendance-container',
                blockUI: true,
                disableButton: true,
                buttonSelector: "#delete-shift",
                data: formData,
                success: function(response) {
                    if (response.status == 'success') {
                        if (typeof loadData !== 'undefined' && typeof loadData === 'function') {
                            loadData();
                        } else {
                            showTable();
                        }
                        $(MODAL_DEFAULT).modal('hide');
                    }
                }
            })
        });

        init(MODAL_DEFAULT);
    });
</script>
