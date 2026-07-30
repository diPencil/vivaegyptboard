@php
$addAttendancePermission = user()->permission('add_attendance');
@endphp
<div class="table-responsive">
    <x-table class="table-bordered mt-3 table-hover" headType="thead-light">
        <x-slot name="thead">
            <th class="px-2" style="vertical-align: middle;">@lang('app.employee')</th>
            @for ($i = 1; $i <= $daysInMonth; $i++)
            <th class="pr-2 pl-1 f-11">{{ $i }}
                <br>
                <span class="text-dark-grey f-10">
                    {{ $weekMap[\Carbon\Carbon::parse(\Carbon\Carbon::parse($i . '-' . $month . '-' . $year))->dayOfWeek] }}
                </span></th>
            @endfor
            <th class="text-right px-2">@lang('app.total')</th>
        </x-slot>

        @foreach ($employeeAttendence as $key => $attendance)
            @php
                $totalPresent = 0;
                $userId = explode('#', $key);
                $userId = $userId[0];
            @endphp
            <tr>
                <td class="w-30 px-2"> {!! end($attendance) !!} </td>
                @foreach ($attendance as $key2 => $day)
                    @if ($key2 + 1 <= count($attendance))
                        @php
                            $attendanceDate = \Carbon\Carbon::parse($year.'-'.$month.'-'.$key2);
                            $rotationIcon = '';
                            if (isset($rotationDetails[$userId][$key2])) {
                                if ($rotationDetails[$userId][$key2]['type'] == 'rotation_day_off') {
                                    $rotationIcon = '<span data-toggle="tooltip" data-html="true" data-original-title="'.__('modules.rotationDayOff').'<br>'.__('modules.coveredBy').': '.$rotationDetails[$userId][$key2]['replacement_name'].'<br>'.__('modules.scheduledShift').': '.$rotationDetails[$userId][$key2]['replacement_shift'].'"><i class="fa fa-sync text-primary ml-1"></i></span>';
                                } else {
                                    $rotationIcon = '<span data-toggle="tooltip" data-html="true" data-original-title="'.__('modules.rotationCover').'<br>'.__('modules.coveringFor').': '.$rotationDetails[$userId][$key2]['original_name'].'<br>'.__('modules.scheduledShift').': '.$rotationDetails[$userId][$key2]['shift'].'"><i class="fa fa-user-shield text-info ml-1"></i></span>';
                                }
                            }
                        @endphp
                        <td class="px-1 text-center">
                            @if ($day == 'Leave')
                            <span data-toggle="tooltip" data-html="true"
                            data-original-title="{{ \Illuminate\Support\Str::limit(strip_tags($leaveReasons[$userId][$key2] ?? ''), 50, '...') }}">
                            <i class="fa fa-plane-departure text-red"></i>
                            </span>
                            {!! $rotationIcon !!}
                            @elseif ($day == 'Day Off')
                                <span data-toggle="tooltip" data-original-title="@lang('modules.attendance.dayOff')"><i
                                        class="fa fa-calendar-week text-red"></i></span>
                            @elseif ($day == 'Rotation Day Off')
                                {!! $rotationIcon !!}
                            @elseif ($day == 'Half Day')
                                @if ($attendanceDate->isFuture())
                                    <span data-toggle="tooltip" data-original-title="@lang('modules.attendance.halfDay')"><i
                                        class="fa fa-star-half-alt text-red"></i></span>
                                @else
                                    <a @if ($addAttendancePermission == 'all') href="javascript:;" class="edit-attendance" @endif data-user-id="{{ $userId }}"
                                            data-attendance-date="{{ $key2 }}">
                                        <span data-toggle="tooltip" data-original-title="@lang('modules.attendance.halfDay')"><i
                                                class="fa fa-star-half-alt text-red"></i></span>
                                    </a>
                                @endif
                                {!! $rotationIcon !!}
                            @elseif ($day == 'Absent')
                                <a @if ($addAttendancePermission == 'all') href="javascript:;" class="edit-attendance" @endif data-user-id="{{ $userId }}"
                                    data-attendance-date="{{ $key2 }}"><i
                                        class="fa fa-times text-lightest"></i></a>
                                {!! $rotationIcon !!}
                            @elseif ($day == 'Holiday')
                                <a href="javascript:;" data-toggle="tooltip" @if(user()->permission('add_attendance') == 'all') class="edit-attendance" @endif
                                    data-original-title="{{ $holidayOccasions[$key2] }}"
                                    data-user-id="{{ $userId }}" data-attendance-date="{{ $key2 }}"><i
                                        class="fa fa-star text-warning"></i></a>
                                {!! $rotationIcon !!}
                            @else
                                @if ($day != '-')
                                    @php
                                        $totalPresent = $totalPresent + 1;
                                    @endphp
                                @endif

                                {!! $day !!}
                                {!! $rotationIcon !!}
                            @endif
                        </td>
                    @endif
                @endforeach
                <td class="text-dark f-w-500 text-right attendance-total px-2 w-100">
                    {!! $totalPresent . ' / <span class="text-lightest">' . (count($attendance) - 1) . '</span>' !!}</td>
            </tr>
        @endforeach
    </x-table>
</div>
