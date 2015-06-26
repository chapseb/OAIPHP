$(function() {
    $("#checkAll").click(function() {
        $(".check").prop('checked', $(this).prop('checked'));
    });

    $('#myModal').on('shown.bs.modal', function () {
        var listFiles = [];
        $('input:checked').each(function() {
              listFiles.push($(this).val());
        });
        if( listFiles.indexOf("Tout cocher") != -1 ) {
            $("#listFilesConfirm").html("Vous avez selectionné tous les fichiers");
            $("#confirmButton").removeAttr("disabled");
        }
        else if (listFiles.length < 1 ){
            $("#listFilesConfirm").html("Vous n'avez selectionné aucun fichier");
            $("#confirmButton").attr("disabled", "disabled");
        }
        else {
            var newHTML = [];
            $.each(listFiles, function(index, value) {
                    newHTML.push('<span>' + value + '</span>');
            });
            $("#listFilesConfirm").html("Vous avez sélectionné les fichiers suivants :<br />" + newHTML.join("<br />"));
            $("#confirmButton").removeAttr("disabled");
        }
        $('#myInput').focus();
    });

    $('#myModalDelete').on('shown.bs.modal', function () {
        var listFiles = [];
        $('input:checked').each(function() {
              listFiles.push($(this).val());
        });
        if( listFiles.indexOf("Tout cocher") != -1 ) {
            $("#deleteFilesConfirm").html("Vous avez selectionné tous les élements");
            $("#confirmButton").removeAttr("disabled");
        }
        else if (listFiles.length < 1 ){
            $("#deleteFilesConfirm").html("Vous n'avez selectionné aucun fichier");
            $("#confirmButton").attr("disabled", "disabled");
        }
        else {
            var newHTML = [];
            $.each(listFiles, function(index, value) {
                    newHTML.push('<span>' + value + '</span>');
            });
            $("#deleteFilesConfirm").html("Vous avez sélectionné les fichiers suivants :<br />" + newHTML.join("<br />"));
            $("#confirmButton").removeAttr("disabled");
        }
        $('#myInput').focus();
    });
            $('.btnDelete').on('click', function (e) {
                //e.preventDefault();
                var id = $(this).closest('tr').data('id');
                $('#myremoveModal').data('id', id);
            });
            $('#btnDelteYes').unbind('click').bind('click', function () {
                var nameset = $('#nameset ').text();
                var id = $('#myremoveModal').data('id');
                $.ajax({
                type: "POST",
                data: {id: id},
                url: "/admin/deleteFileById/"+nameset,
                success: function(msg)
                {
                    var message= '';
                    if (msg == "true"){
                        message='<div class="alert alert-success alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Le fichier a bien été supprimé !</div>';
                    $('.answer').html(message);
                    }else{
                        message='<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Erreur lors de la suppression du fichier :( </div>';
                        $('.answer').html(message);
                    }
                }
                });
                $('[data-id=' + id + ']').remove();
            });

});

