<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('app.edit') @lang('modules.tasks.links')</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-form id="edit-task-link-form">
        <div class="row">
            <div class="col-md-6">
                <x-forms.text :fieldLabel="__('modules.tasks.linkName')" fieldName="link_name" fieldId="link_name_edit" :fieldValue="$link->link_name" fieldRequired="true" />
            </div>
            <div class="col-md-6">
                <x-forms.text :fieldLabel="__('modules.tasks.linkUrl')" fieldName="link_url" fieldId="link_url_edit" :fieldValue="$link->link_url" fieldRequired="true" />
            </div>
            <div class="col-md-12">
                <div class="form-group my-3">
                    <x-forms.label fieldId="description_edit" :fieldLabel="__('app.description')">
                    </x-forms.label>
                    <div id="link-description-edit">{!! $link->description !!}</div>
                    <textarea name="description" id="description-text-edit" class="d-none"></textarea>
                </div>
            </div>
        </div>
    </x-form>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-task-link" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $(document).ready(function() {
        var quillEdit;
        if ($('#link-description-edit').length > 0) {
            quillEdit = new Quill('#link-description-edit', {
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

        $('#save-task-link').click(function() {
            var url = "{{ route('task-links.update', [$task->id, $link->id]) }}";
            if (quillEdit) {
                document.getElementById('description-text-edit').value = quillEdit.root.innerHTML;
            }
            $.easyAjax({
                url: url,
                container: '#edit-task-link-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-task-link",
                data: $('#edit-task-link-form').serialize() + '&_method=PUT',
                success: function(response) {
                    if (response.status == "success") {
                        $('#nav-tabContent').html(response.data.view);
                        $(MODAL_LG).modal('hide');
                    }
                }
            })
        });
    });
</script>
