<div {{ $attributes->merge(['class' => 'bootstrap-timepicker timepicker form-group my-3']) }}>
    <x-forms.label :fieldId="$fieldId" :fieldLabel="$fieldLabel" :fieldRequired="$fieldRequired" :popover="$popover"></x-forms.label>

    <div class="input-group">
        <input type="text"
               class="form-control height-35 f-14"
               placeholder="{{ $fieldPlaceholder }}"
               value="{{ $fieldValue }}"
               name="{{ $fieldName }}"
               id="{{ $fieldId }}"
               @readonly($fieldReadOnly == 'true')>
        <div class="input-group-append">
            <button class="btn btn-outline-secondary border" type="button"><i class="fa fa-clock"></i></button>
        </div>
    </div>

    @if ($fieldHelp)
        <small id="{{ $fieldId }}Help" class="form-text text-muted">{{ $fieldHelp }}</small>
    @endif
</div>
