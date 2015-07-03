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
        var row = $(this).closest('tr').html();
        $('#myremoveModal').data('id', id);
        $('#myremoveModal').data('row', row);
    });


    $('#btnDelteYes').unbind('click').bind('click', function () {
        var nameset = $('#nameset ').text();
        var id = $('#myremoveModal').data('id');
        var row = $('#myremoveModal').data('row');
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
            var dtdd = $(row).find('dl').html();
            var newdeletedcell= '<td><dl>'+dtdd+'</dl></td><td><h4><span class="label label-danger">Supprimé</span></h4></td>';
            $('#removelistFilestable tbody').append('<tr>'+newdeletedcell+'</tr>');
            }else{
                message='<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>Erreur lors de la suppression du fichier :( </div>';
                $('.answer').html(message);
            }
        }
        });
        $('[data-id=' + id + ']').remove();
    });

    $('#myTabs a').click(function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        e.target; // newly activated tab
        e.relatedTarget; // previous active tab
    });
    $('#listsettable').dataTable();


    var uploadObj = $("#fileuploader").uploadFile({
        url:"/public/admin/uploadsFiles",
        method:"POST",
        allowedTypes:"xml",
        multiple:true,
        autoSubmit:false,
        fileName:"myfile",
        formData: {"setname":$("#setname").val(),"format":$("#format").val()},
        //maxFileCount:2,
        /*dynamicFormData: function()
        {
            var data ={ location:"INDIA"}
            return data;
        },*/
        //showStatusAfterSuccess:false,
        dragDropStr: "<span><b>Faites glisser et déposez les fichiers</b></span>",
        abortStr:"abandonner",
        cancelStr:"résilier",
        doneStr:"fait",
        multiDragErrorStr: "Plusieurs Drag & Drop de fichiers ne sont pas autorisés.",
        extErrorStr:"n'est pas autorisé. Extensions autorisées:",
        sizeErrorStr:"n'est pas autorisé. Admis taille max:",
        uploadErrorStr:"Upload n'est pas autorisé",
        onSuccess:function(files,data,xhr){
            $("#status").html("<font color='green'>L'upload est un succès.</font>");
        },
        onError: function(files,status,errMsg){
            $("#status").html("<font color='red'>L'upload is n\'a pas fonctionné</font>");
        }
    });

    $("#startUpload").click(function()
    {
        uploadObj.startUpload();
    });

   //$("#fileuploader").uploadFile(uploadObj);

/*    $("#fileuploader").uploadFile({
        url:"/public/admin/uploadsFiles",
        multiple: true,
        fileName:"myfile"
    });
*/
});

