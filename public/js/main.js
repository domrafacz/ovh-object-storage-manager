$(document).ready(function () {
    $('#container-url-modal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var row = button.parent().parent();
        var url = row.data('url');
        var modal = $(this);

        modal.find('#container-url-input').val(url);
        modal.find('#open-container-url').attr('href', url);
    });

    $('#container-url-modal').on('show.bs.modal', function (event) {
        $('#url-copied').addClass('d-none');
    });

    $('#copy-container-url').click(function () {
        var inputValue = document.getElementById('container-url-input');
        inputValue.select();
        document.execCommand('copy');

        $('#url-copied').removeClass('d-none');
    });

    $('#delete-container-modal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var row = button.parent().parent();
        var modal = $(this);

        modal.find('.region').text($('#region').text());
        modal.find('.container-name').text(row.data('container-name'));
        modal.find('#delete-container-name').val(row.data('container-name'));
        modal.find('.object-count').text(row.data('object-count'));
    });

    $('#edit-object-modal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var row = button.parent().parent();
        var modal = $(this);

        modal.find('#modify-object-name').val(row.data('object-name'));
    });

    $('#edit-container-modal, #edit-object-modal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var row = button.parent().parent();
        var modal = $(this);
        var metadataWrapper = modal.find('.metadata-wrapper');
        metadataWrapper.empty();
        $('#container-name2').val(row.data('container-name'));
        $('#container-name2').val(row.data('container-name'));
        modal.find("input[name=container_type2][value='" + row.data('type') + "']").prop("checked", true);

        var metadata = row.data('metadata');
        if (metadata.length > 2) {
            if (metadata.charAt(metadata.length - 2) == ",") {
                metadata = metadata.substring(0, metadata.length - 2) + metadata.substring(metadata.length - 1, metadata.length);
                metadata = JSON.parse(metadata);
                var metaIndex = 0;

                $.each(metadata, function (key, val) {
                    var formRow = $('<div class="form-row"></div>').attr('data-id', 'meta_' + metaIndex);
                    var formGroup1 = $('<div class="input-group col-6"></div>');
                    var formGroup2 = $('<div class="input-group col-6"></div>');
                    var deleteIcon = $('<span class="input-group-append"><i class="far fa-trash-alt delete-meta"></i></span>').attr('data-id', 'meta_' + metaIndex);

                    $('<input>').attr('type', 'text').attr('class', 'form-control').attr('name', 'meta_key_' + metaIndex).val(key).appendTo(formGroup1);
                    $('<input>').attr('type', 'text').attr('class', 'form-control').attr('name', 'meta_value_' + metaIndex).val(val).appendTo(formGroup2);
                    deleteIcon.appendTo(formGroup2);

                    formGroup1.appendTo(formRow);
                    formGroup2.appendTo(formRow);


                    formRow.appendTo(metadataWrapper);

                    ++metaIndex;
                });
            }
        }
    });

    $(document).on('click', '.delete-meta', function () {
        var metaId = $(this).parent().data('id');
        $(document).find(".form-row[data-id='" + metaId + "']").remove();

        //reassign meta ids
        var newIndex = 0;
        $('.metadata-wrapper').find('.form-row').each(function () {
            $(this).attr('data-id', 'meta_' + newIndex);
            $(this).find('.input-group-append').attr('data-id', 'meta_' + newIndex);
            ++newIndex;
        });
    });

    $('#add-meta').on('click', function () {
        var metadataWrapper = $('.metadata-wrapper');
        var metaIndex = metadataWrapper.find('.form-row').length;

        var formRow = $('<div class="form-row"></div>').attr('data-id', 'meta_' + metaIndex);
        var formGroup1 = $('<div class="input-group col-6"></div>');
        var formGroup2 = $('<div class="input-group col-6"></div>');
        var deleteIcon = $('<span class="input-group-append"><i class="far fa-trash-alt delete-meta"></i></span>').attr('data-id', 'meta_' + metaIndex);

        $('<input>').attr('type', 'text').attr('class', 'form-control').attr('name', 'meta_key_' + metaIndex).appendTo(formGroup1);
        $('<input>').attr('type', 'text').attr('class', 'form-control').attr('name', 'meta_value_' + metaIndex).appendTo(formGroup2);
        deleteIcon.appendTo(formGroup2);

        formGroup1.appendTo(formRow);
        formGroup2.appendTo(formRow);


        formRow.appendTo(metadataWrapper);
    });

    $('#delete-object-modal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var row = button.parent().parent();
        var modal = $(this);

        modal.find('.region').text($('#region').text());
        modal.find('.container-name').text(row.data('container-name'));
        modal.find('#delete-object-name').val(row.data('object-name'));
        modal.find('.object-name').text(row.data('object-name'));
    });

    $('#upload-submit-classic').on('click', function () {
        $('#upload-spinner').show();
        $('#upload-submit-classic').hide();
    });

    $(function () {
        $('#upload-submit').on('click', function () {
            $("#upload_form").ajaxSubmit({
                url: $('#container-url').val(),
                type: 'post',
                uploadProgress: function (event, position, total, percentComplete) {
                    var percentVal = percentComplete + '%';
                    $('#progress').text(percentVal);
                },
                complete: function (xhr) {
                    $('#progress').text('finished');
                }
            })
        });
    });
});