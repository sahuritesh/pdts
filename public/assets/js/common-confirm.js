function confirmUpdate(config) {
    var title = config.title;
    var url = config.url;
    var data = config.data;
    var reloadDatatablesID = config.reloadDatatablesID;
    var content = config.content;
    var confirmType = config.confirmType;
    var selectOptions = config.selectOptions;
    var redirectUrl = config.redirectUrl;
    if (confirmType == 'dropdown' && selectOptions != '') {
        var optionsHtml = '';
        $.each(selectOptions, function (k, v) {
            optionsHtml+= '<option value="'+k+'">'+v+'</option>';
        });
        var confirmContent = '<form action="" class="formName">' +
            '<div class="form-group">' +
            '<label>Please Select Status</label>' +
            '<select class="selectStatus form-control" required >' +
            optionsHtml+'</select>' +
            '</div>' +
            '<div class="form-group">' +
            '<label>Enter Your Comments</label>' +
            '<textarea placeholder="Your Comments" class="comments form-control" required rows="4"/></textarea>' +
            '</div>' +
            '</form>'
    } else if (confirmType == 'textarea') {
        var confirmContent = '' +
            '<form action="" class="formName">' +
            '<div class="form-group">' +
            '<label>Enter Your Comments</label>' +
            '<textarea placeholder="Your Comments" class="comments form-control" required rows="4"/></textarea>' +
            '</div>' +
            '</form>'
    } else {
        var confirmContent = content;
    }
    $.confirm({
        title: title,
        content: confirmContent,
        type: 'blue',
        typeAnimated: true,
        cancelButton: 'Cancel !',
        buttons: {
            confirm: function () {
                if(confirmType == 'dropdown' && selectOptions != ''){
                    var selectStatus = this.$content.find('.selectStatus').val();
                    var comments = this.$content.find('.comments').val();
                    if(!selectStatus){
                        $.alert('Please Select Any');
                        return false;
                    }
                    if(!comments){
                        $.alert('Please Enter Comments');
                        return false;
                    }
                    data.comments = comments;
                    data.selectStatus = selectStatus;
                }else if (confirmType == 'textarea') {
                    var comments = this.$content.find('.comments').val();
                    if(!comments){
                        $.alert('Please Enter Comments');
                        return false;
                    }
                    data.comments = comments;
                }else if (confirmType != ''){
                    var comments = this.$content.find('.remark').val();
                    if(!comments){
                        $.alert('Please Provide Remarks');
                        return false;
                    }
                    data.comments = comments;
                }
                response = ajaxRequestPromise(url, data);
                response.then(function (res) {
                    var resultData = JSON.parse(res);
                    if(resultData.error == 0){
                        //var alertClass = 'success';
                        toastr.success(resultData.msg);
                        location.reload();
                    }else{
                       // var alertClass = 'error';
                        toastr.error(resultData.msg);
                        location.reload();
                    }
                   // parseFormErrors(resultData, alertClass);
                    if (reloadDatatablesID != undefined && reloadDatatablesID != null && reloadDatatablesID != '') {
                        reloadDataTables(reloadDatatablesID);
                    }
                    if(redirectUrl != undefined && redirectUrl != null && redirectUrl !=''){
                       setTimeout(window.location.href = redirectUrl,8000);
                    }
                }, function (e) {
                    var resultData = '';
                   // parseFormErrors(resultData, 'error');
                    if (reloadDatatablesID != undefined && reloadDatatablesID != null && reloadDatatablesID != '') {
                        reloadDataTables(reloadDatatablesID);
                    }
                    if(redirectUrl != undefined && redirectUrl != null && redirectUrl !=''){
                        setTimeout(window.location.href = redirectUrl,8000);
                    }
                });
            },
            cancel: function () {}
        }
    });
}

function reloadDataTables(tableId) {
    $('#' + tableId).DataTable().ajax.reload();
}