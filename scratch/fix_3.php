<?php
$files = [
    'resources/views/employees/ajax/shifts.blade.php',
    'resources/views/front/tasks/calendar.blade.php',
    'resources/views/holiday/calendar/index.blade.php'
];

foreach ($files as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        $content = str_replace("left: 'prev,next today'", "start: 'prev,next today'", $content);
        $content = str_replace("right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'", "end: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'", $content);
        $content = preg_replace("/locale:\s*initialLocaleCode,/", "direction: '{{ isRtl() ? \"rtl\" : \"ltr\" }}',\n            locale: '{{ app()->getLocale() }}',", $content);
        
        file_put_contents($file, $content);
    }
}
